# API Integration Patterns

## Copernicus Data Space (Sentinel Hub)

### Authentication Flow
```
1. POST /oauth/token
   {
     grant_type: "client_credentials",
     client_id: "...",
     client_secret: "..."
   }
   
2. Response: { access_token, expires_in: 3600 }

3. Cache token with expiry timestamp

4. Refresh when expires_at - 300s < now()
```

### Request Structure
```php
POST /api/v1/process
Headers: Authorization: Bearer {token}
Body: {
  input: {
    bounds: { bbox: [west, south, east, north] },
    data: [{
      type: "sentinel-2-l2a",
      dataFilter: {
        timeRange: {
          from: "2025-01-15T00:00:00Z",
          to: "2025-01-15T23:59:59Z"
        }
      }
    }]
  },
  output: {
    width: 50,
    height: 50,
    responses: [{
      identifier: "default",
      format: { type: "image/png" }
    }]
  },
  evalscript: "//VERSION=3\n..."
}
```

### BBox Calculation
```php
function calculateBBox($lat, $lon, $delta = 0.025) {
    return [
        $lon - $delta,  // west
        $lat - $delta,  // south
        $lon + $delta,  // east
        $lat + $delta   // north
    ];
}
// 0.025° ≈ 2.5km at equator
```

### 401 Retry Logic
```php
if ($response->status() === 401 && $retryCount < 2) {
    Cache::forget('copernicus_token_data');
    return $this->getNDVIData($lat, $lon, $date, $retryCount + 1);
}
```

### Response Caching
```php
$cacheKey = "copernicus_ndvi_{$lat}_{$lon}_{$date}";
$wasCached = Cache::has($cacheKey);

$result = Cache::remember($cacheKey, 3600, function() {
    // API call
});

// Track AFTER cache check
if ($result !== null) {
    $this->trackApiCall('analysis', 'ndvi', $lat, $lon, $date, $wasCached);
}
```

---

## OpenWeatherMap - Air Quality

### Endpoint
```
GET https://api.openweathermap.org/data/2.5/air_pollution
Params: lat, lon, appid
```

### Response
```json
{
  "list": [{
    "main": { "aqi": 3 },
    "components": {
      "co": 230.31,
      "no2": 15.89,
      "o3": 68.66,
      "pm2_5": 8.82,
      "pm10": 11.07
    },
    "dt": 1705334400
  }]
}
```

### AQI Scale
- 1 = Good
- 2 = Fair
- 3 = Moderate
- 4 = Poor
- 5 = Very Poor

---

## WAQI - World Air Quality Index

### Endpoint
```
GET https://api.waqi.info/feed/geo:{lat};{lon}/
Params: token
```

### Response
```json
{
  "status": "ok",
  "data": {
    "aqi": 42,
    "city": {
      "name": "Copenhagen",
      "geo": [55.676, 12.568]
    },
    "iaqi": {
      "pm25": { "v": 12 },
      "pm10": { "v": 18 }
    },
    "time": { "v": 1705334400 }
  }
}
```

### Validation Flow
```php
1. Get user reading
2. Find nearest official station
3. Calculate Haversine distance
4. Compare values
5. Calculate variance %: ((user - official) / official) * 100
6. Flag if |variance| > threshold
```

---

## Rate Limiting Strategies

### Token Bucket (Laravel)
```php
RateLimiter::for('satellite-api', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()->id);
});
```

### Backoff on 429
```php
if ($response->status() === 429) {
    $retryAfter = $response->header('Retry-After', 60);
    Log::warning("Rate limited, waiting {$retryAfter}s");
    sleep($retryAfter);
    return $this->retry();
}
```

### Queue Throttling
```php
Redis::throttle('copernicus-api')
    ->allow(30)
    ->every(60)
    ->then(function () {
        // Process job
    }, function () {
        // Release back to queue
        return $this->release(10);
    });
```

---

## Error Handling

### HTTP Status Codes
```php
match($response->status()) {
    200 => $this->processSuccess($response),
    401 => $this->refreshTokenAndRetry(),
    403 => $this->logInsufficientCredits(),
    404 => $this->noDataAvailable(),
    429 => $this->rateLimitBackoff(),
    500, 502, 503 => $this->retryWithBackoff(),
    default => $this->logUnknownError()
}
```

### Timeout Configuration
```php
Http::timeout(15)  // Connection + response
    ->retry(3, 1000)  // 3 retries, 1s delay
    ->withToken($token)
    ->post($url, $data)
```

### Circuit Breaker Pattern
```php
if (Cache::get('copernicus_circuit_open')) {
    Log::warning('Circuit breaker open, skipping API call');
    return null;
}

try {
    $result = $this->callApi();
} catch (Exception $e) {
    $failures = Cache::increment('copernicus_failures');
    if ($failures > 5) {
        Cache::put('copernicus_circuit_open', true, 300);
    }
    throw $e;
}

Cache::forget('copernicus_failures');
```

---

## Usage Tracking

### Cost Calculation
```php
private function getCostForCallType(string $type, bool $cached): float
{
    if ($cached) return 0.0;
    
    return match($type) {
        'enrichment' => 1.0,   // 7 indices
        'overlay' => 0.5,       // Single view
        'analysis' => 0.75,     // Per index
        default => 1.0
    };
}
```

### Billing Flow
```php
1. Check usage limit BEFORE API call
   if (!$usageService->canPerformAction($user, 'satellite_analyses')) {
       return;
   }

2. Make API call (if allowed)

3. DB::transaction(function() {
     SatelliteAnalysis::create([...]);
     $usageService->recordSatelliteAnalysis($user, 'all_indices');
   });
```

### Tracking Schema
```php
SatelliteApiCall {
  data_point_id,
  campaign_id,
  user_id,
  call_type: 'enrichment' | 'overlay' | 'analysis',
  index_type: 'ndvi' | 'ndmi' | ...,
  cached: boolean,
  response_time_ms,
  cost_credits,
  created_at
}
```

---

## Pitfalls

### Copernicus
- Token expires exactly after 3600s → buffer 5min
- BBox order: [lon, lat, lon, lat] not [lat, lon, ...]
- Date format: ISO 8601 with Z timezone
- PNG response needs `imagecreatefromstring()`
- No data ≠ error (clouds, time window)

### WAQI
- Rate limit: 1000 calls/day on free tier
- Returns nearest station (may be far away)
- `geo:lat;lon` format (semicolon separator)
- AQI scale differs by country

### OpenWeatherMap
- Separate endpoint for historical data
- Free tier: 60 calls/min
- Coordinates must be decimal degrees
- AQI vs components: different metrics

### Caching
- Cache BEFORE tracking (to know if cached)
- TTL must balance freshness vs cost
- Invalidate on user corrections
- Different keys for different parameters

### Queue Jobs
- Check limits before queueing
- Use DB transactions for billing atomicity
- Retry on transient failures only
- Log failed jobs for manual review
