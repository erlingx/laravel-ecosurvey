# Performance Optimization

## N+1 Query Problem

### Detection
```php
// Enable query logging
DB::enableQueryLog();

// Run code
$dataPoints = DataPoint::all();
foreach ($dataPoints as $point) {
    echo $point->campaign->name; // N+1!
}

// Check queries
dd(DB::getQueryLog());
// Shows: 1 query for DataPoints + N queries for campaigns
```

### Solution: Eager Loading
```php
// Before (31 queries)
$dataPoints = DataPoint::all();

// After (1 query)
$dataPoints = DataPoint::with('campaign', 'environmentalMetric', 'user')->get();
```

### Complex Relationships
```php
// Load nested relationships
$campaigns = Campaign::with([
    'dataPoints.environmentalMetric',
    'dataPoints.user',
    'surveyZones'
])->get();

// Load counts
$campaigns = Campaign::withCount('dataPoints')->get();

// Conditional loading
$campaigns = Campaign::with([
    'dataPoints' => fn($q) => $q->where('status', 'approved')
])->get();
```

---

## Map GeoJSON Optimization

### Problem
```php
// Bad: N+1 queries for relationships
$dataPoints = DataPoint::all();
foreach ($dataPoints as $point) {
    $campaign = $point->campaign;      // Query 1
    $metric = $point->environmentalMetric; // Query 2
    $user = $point->user;              // Query 3
}
// Total: 1 + (3 × N) queries
```

### Solution: JOIN Query
```php
$dataPoints = DataPoint::query()
    ->select([
        'data_points.id',
        'data_points.value',
        'campaigns.name as campaign_name',
        'environmental_metrics.name as metric_name',
        'users.name as user_name',
        DB::raw('ST_X(data_points.location::geometry) as lon'),
        DB::raw('ST_Y(data_points.location::geometry) as lat'),
    ])
    ->join('campaigns', 'data_points.campaign_id', '=', 'campaigns.id')
    ->join('environmental_metrics', 'data_points.environmental_metric_id', '=', 'environmental_metrics.id')
    ->join('users', 'data_points.user_id', '=', 'users.id')
    ->get();

// Total: 1 query (97% reduction)
```

### Results
- Before: 31 queries, ~606ms
- After: 1 query, ~128ms
- **19.5x faster** on initial load

---

## Caching Strategy

### Cache Layer
```php
$cacheKey = 'survey_map_data_'
    . ($campaignId ?? 'all') . '_'
    . ($metricId ?? 'all');

$geoJSON = Cache::remember($cacheKey, 300, function() {
    return $geospatialService->getDataPointsAsGeoJSON(...);
});
```

### Cache Invalidation
```php
// app/Observers/DataPointObserver.php
class DataPointObserver
{
    public function created(DataPoint $dataPoint): void
    {
        $this->invalidateCache($dataPoint);
    }
    
    public function updated(DataPoint $dataPoint): void
    {
        $this->invalidateCache($dataPoint);
    }
    
    public function deleted(DataPoint $dataPoint): void
    {
        $this->invalidateCache($dataPoint);
    }
    
    private function invalidateCache(DataPoint $dataPoint): void
    {
        // Clear all campaign-related caches
        Cache::forget("survey_map_data_{$dataPoint->campaign_id}_all");
        Cache::forget("survey_map_data_all_all");
        
        // Clear metric-specific cache
        Cache::forget("survey_map_data_{$dataPoint->campaign_id}_{$dataPoint->environmental_metric_id}");
    }
}
```

### Results
- First load: ~128ms (no cache)
- Cached load: ~27ms (4.7x faster)
- Cache hit rate: 75%

---

## Database Indexing

### Spatial Index (Required)
```sql
CREATE INDEX data_points_location_idx 
ON data_points 
USING GIST (location);
```

### Composite Indexes
```sql
-- For filtered queries
CREATE INDEX data_points_campaign_metric_idx 
ON data_points (campaign_id, environmental_metric_id);

-- For status filtering
CREATE INDEX data_points_status_idx 
ON data_points (status);

-- For time-series
CREATE INDEX data_points_collected_at_idx 
ON data_points (collected_at);
```

### Foreign Key Indexes
```sql
-- Automatically created by Laravel
FOREIGN KEY (campaign_id) REFERENCES campaigns(id)
FOREIGN KEY (user_id) REFERENCES users(id)
```

---

## Query Optimization Patterns

### Select Only Needed Columns
```php
// Bad: Fetches all columns + BLOBs
DataPoint::all();

// Good: Specific columns only
DataPoint::select('id', 'value', 'location')->get();
```

### Chunk Large Datasets
```php
// Bad: Load 10,000 records into memory
$points = DataPoint::all();

// Good: Process in chunks
DataPoint::chunk(100, function($points) {
    foreach ($points as $point) {
        // Process
    }
});
```

### Aggregate on Database
```php
// Bad: Fetch all, calculate in PHP
$points = DataPoint::all();
$avg = $points->avg('value');

// Good: Database aggregation
$avg = DataPoint::avg('value');
```

### Pagination
```php
// Bad: Load everything
$points = DataPoint::all();

// Good: Paginate
$points = DataPoint::paginate(50);
```

---

## API Response Optimization

### Minimize Payload
```php
// Only return needed fields
return response()->json([
    'type' => 'FeatureCollection',
    'features' => $dataPoints->map(fn($p) => [
        'type' => 'Feature',
        'geometry' => [
            'type' => 'Point',
            'coordinates' => [$p->lon, $p->lat]
        ],
        'properties' => [
            'id' => $p->id,
            'value' => $p->value,
            // Don't include large fields like photos
        ]
    ])
]);
```

### HTTP Compression
```php
// Enable gzip in middleware
return response($data)
    ->header('Content-Encoding', 'gzip')
    ->setContent(gzencode(json_encode($data)));
```

### ETags
```php
$etag = md5(json_encode($data));

if ($request->header('If-None-Match') === $etag) {
    return response('', 304);
}

return response($data)->header('ETag', $etag);
```

---

## Frontend Optimization

### Debounce User Input
```javascript
let timeout;
function onFilterChange() {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        fetchMapData();
    }, 300); // Wait 300ms after typing stops
}
```

### Lazy Load Markers
```javascript
// Only render markers in viewport
map.on('moveend', function() {
    const bounds = map.getBounds();
    const visible = allMarkers.filter(m => bounds.contains(m.getLatLng()));
    renderMarkers(visible);
});
```

### Virtual Scrolling
```javascript
// For long lists, only render visible items
// Use libraries like react-window or vue-virtual-scroller
```

---

## Monitoring & Profiling

### Laravel Debugbar
```bash
composer require barryvdh/laravel-debugbar --dev
```

### Query Logging
```php
DB::listen(function($query) {
    if ($query->time > 100) {
        Log::warning('Slow query', [
            'sql' => $query->sql,
            'time' => $query->time,
        ]);
    }
});
```

### Telescope
```bash
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

### Metrics to Track
- Query count per request
- Query execution time
- Cache hit ratio
- Memory usage
- Response time

---

## Production Checklist

### Database
- ✅ All spatial columns have GIST indexes
- ✅ Foreign keys indexed
- ✅ Composite indexes for common queries
- ✅ Analyze tables after bulk inserts

### Queries
- ✅ No N+1 queries (test suite validates)
- ✅ Eager load relationships
- ✅ Use JOINs for related data
- ✅ Aggregate on database

### Caching
- ✅ Cache expensive queries
- ✅ Observer-based invalidation
- ✅ Appropriate TTL values
- ✅ Cache driver configured (Redis in prod)

### API
- ✅ Minimize response payload
- ✅ Enable HTTP compression
- ✅ Use ETags for conditional requests
- ✅ Implement rate limiting

### Frontend
- ✅ Debounce user input
- ✅ Lazy load off-screen content
- ✅ Chunk large datasets
- ✅ Optimize bundle size

---

## Pitfalls

### Over-Eager Loading
```php
// Bad: Loading data you don't use
DataPoint::with('campaign', 'user', 'satelliteAnalyses', 'exports')->get();

// Good: Only load what you need
DataPoint::with('campaign')->get();
```

### Cache Key Collisions
```php
// Bad: Same key for different data
Cache::put('map_data', $dataPoints);

// Good: Specific keys
Cache::put("map_data_{$campaignId}_{$metricId}", $dataPoints);
```

### Index Overload
```sql
-- Don't index every column
-- Indexes slow down writes
-- Focus on WHERE/JOIN/ORDER BY columns
```

### Premature Optimization
- Profile first, optimize second
- Focus on biggest bottlenecks
- Measure before and after
- Don't sacrifice readability for micro-optimizations

### Cache Stampede
```php
// Multiple requests try to regenerate cache simultaneously
// Use cache locks
Cache::lock('map_data_generation', 10)->get(function() {
    return Cache::remember('map_data', 300, fn() => expensive());
});
```
