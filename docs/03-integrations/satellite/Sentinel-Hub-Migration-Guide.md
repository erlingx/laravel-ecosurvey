# Migrating from NASA API to Sentinel Hub (ESA Copernicus)

## Why Sentinel Hub?

✅ **European servers** - Works from DDEV/Docker  
✅ **Fast responses** - 2-5 seconds (vs NASA's 60-120s)  
✅ **NDVI built-in** - No manual calculation  
✅ **Free tier** - 1000 requests/month  
✅ **Better for environmental monitoring** - Industry standard  

---

## Step 1: Sign Up

1. Visit: https://www.sentinel-hub.com/
2. Click "Sign up for free"
3. Select "Trial" or "Education/Research" tier
4. Get your **OAuth Client ID** and **Client Secret**

---

## Step 2: Update `.env`

Add these to your `.env`:

```bash
# Sentinel Hub API (ESA Copernicus)
SENTINEL_HUB_CLIENT_ID=your_client_id_here
SENTINEL_HUB_CLIENT_SECRET=your_client_secret_here
SENTINEL_HUB_INSTANCE_ID=your_instance_id_here

# Optional: Disable NASA API
NASA_USE_MOCK=true
```

---

## Step 3: Update `config/services.php`

```php
'sentinel_hub' => [
    'client_id' => env('SENTINEL_HUB_CLIENT_ID'),
    'client_secret' => env('SENTINEL_HUB_CLIENT_SECRET'),
    'instance_id' => env('SENTINEL_HUB_INSTANCE_ID'),
    'token_url' => 'https://services.sentinel-hub.com/oauth/token',
    'process_url' => 'https://services.sentinel-hub.com/api/v1/process',
    'cache_ttl' => env('SENTINEL_CACHE_TTL', 3600),
],
```

---

## Step 4: Create Service

Run:
```bash
ddev artisan make:class Services/SentinelHubService
```

**File:** `app/Services/SentinelHubService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SentinelHubService
{
    private string $clientId;
    private string $clientSecret;
    private string $instanceId;
    private string $tokenUrl;
    private string $processUrl;
    private int $cacheTtl;

    public function __construct()
    {
        $this->clientId = config('services.sentinel_hub.client_id');
        $this->clientSecret = config('services.sentinel_hub.client_secret');
        $this->instanceId = config('services.sentinel_hub.instance_id');
        $this->tokenUrl = config('services.sentinel_hub.token_url');
        $this->processUrl = config('services.sentinel_hub.process_url');
        $this->cacheTtl = config('services.sentinel_hub.cache_ttl', 3600);
    }

    /**
     * Get OAuth access token (cached for 1 hour)
     */
    private function getAccessToken(): ?string
    {
        return Cache::remember('sentinel_hub_token', 3600, function () {
            try {
                $response = Http::asForm()->post($this->tokenUrl, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ]);

                if ($response->successful()) {
                    return $response->json()['access_token'];
                }

                Log::error('Sentinel Hub OAuth failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Sentinel Hub OAuth error', ['message' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Get satellite imagery for coordinates and date
     */
    public function getSatelliteImagery(
        float $latitude,
        float $longitude,
        ?string $date = null,
        int $width = 512,
        int $height = 512
    ): ?array {
        $date = $date ?? now()->subDays(7)->format('Y-m-d');
        $cacheKey = "sentinel_imagery_{$latitude}_{$longitude}_{$date}_{$width}x{$height}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($latitude, $longitude, $date, $width, $height) {
            $token = $this->getAccessToken();
            if (!$token) {
                return null;
            }

            try {
                $bbox = $this->calculateBBox($latitude, $longitude, 0.025);
                
                $response = Http::timeout(15)
                    ->withToken($token)
                    ->post($this->processUrl, [
                        'input' => [
                            'bounds' => [
                                'bbox' => $bbox,
                                'properties' => [
                                    'crs' => 'http://www.opengis.net/def/crs/EPSG/0/4326'
                                ]
                            ],
                            'data' => [
                                [
                                    'type' => 'sentinel-2-l2a',
                                    'dataFilter' => [
                                        'timeRange' => [
                                            'from' => $date . 'T00:00:00Z',
                                            'to' => $date . 'T23:59:59Z',
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'output' => [
                            'width' => $width,
                            'height' => $height,
                            'responses' => [
                                [
                                    'identifier' => 'default',
                                    'format' => ['type' => 'image/png']
                                ]
                            ]
                        ],
                        'evalscript' => $this->getTrueColorScript()
                    ]);

                if ($response->successful()) {
                    $imageData = base64_encode($response->body());
                    
                    return [
                        'url' => "data:image/png;base64,{$imageData}",
                        'date' => $date,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'source' => 'Sentinel-2 (ESA Copernicus)',
                        'resolution' => '10m',
                        'provider' => 'sentinel_hub',
                    ];
                }

                Log::warning('Sentinel Hub request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Sentinel Hub error', [
                    'message' => $e->getMessage(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

                return null;
            }
        });
    }

    /**
     * Get NDVI data (normalized difference vegetation index)
     */
    public function getNDVIData(
        float $latitude,
        float $longitude,
        ?string $date = null
    ): ?array {
        $date = $date ?? now()->subDays(7)->format('Y-m-d');
        $cacheKey = "sentinel_ndvi_{$latitude}_{$longitude}_{$date}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($latitude, $longitude, $date) {
            $token = $this->getAccessToken();
            if (!$token) {
                return null;
            }

            try {
                $bbox = $this->calculateBBox($latitude, $longitude, 0.025);
                
                $response = Http::timeout(15)
                    ->withToken($token)
                    ->post($this->processUrl, [
                        'input' => [
                            'bounds' => [
                                'bbox' => $bbox,
                                'properties' => [
                                    'crs' => 'http://www.opengis.net/def/crs/EPSG/0/4326'
                                ]
                            ],
                            'data' => [
                                [
                                    'type' => 'sentinel-2-l2a',
                                    'dataFilter' => [
                                        'timeRange' => [
                                            'from' => $date . 'T00:00:00Z',
                                            'to' => $date . 'T23:59:59Z',
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'output' => [
                            'width' => 1,
                            'height' => 1,
                            'responses' => [
                                [
                                    'identifier' => 'default',
                                    'format' => ['type' => 'application/json']
                                ]
                            ]
                        ],
                        'evalscript' => $this->getNDVIScript()
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $ndviValue = $data[0]['default']['bands']['NDVI'] ?? null;
                    
                    return [
                        'ndvi_value' => $ndviValue,
                        'interpretation' => $this->interpretNDVI($ndviValue),
                        'date' => $date,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'source' => 'Sentinel-2 (ESA Copernicus)',
                        'provider' => 'sentinel_hub',
                    ];
                }

                return null;
            } catch (\Exception $e) {
                Log::error('Sentinel Hub NDVI error', [
                    'message' => $e->getMessage(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

                return null;
            }
        });
    }

    /**
     * Calculate bounding box around point
     */
    private function calculateBBox(float $lat, float $lon, float $dim): array
    {
        return [
            $lon - $dim,  // minX
            $lat - $dim,  // minY
            $lon + $dim,  // maxX
            $lat + $dim,  // maxY
        ];
    }

    /**
     * Evalscript for true color imagery
     */
    private function getTrueColorScript(): string
    {
        return <<<'SCRIPT'
//VERSION=3
function setup() {
    return {
        input: ["B02", "B03", "B04"],
        output: { bands: 3 }
    };
}

function evaluatePixel(sample) {
    return [2.5 * sample.B04, 2.5 * sample.B03, 2.5 * sample.B02];
}
SCRIPT;
    }

    /**
     * Evalscript for NDVI calculation
     */
    private function getNDVIScript(): string
    {
        return <<<'SCRIPT'
//VERSION=3
function setup() {
    return {
        input: ["B04", "B08"],
        output: {
            bands: 1,
            sampleType: "FLOAT32"
        }
    };
}

function evaluatePixel(sample) {
    let ndvi = (sample.B08 - sample.B04) / (sample.B08 + sample.B04);
    return [ndvi];
}
SCRIPT;
    }

    /**
     * Interpret NDVI value
     */
    private function interpretNDVI(?float $value): string
    {
        if ($value === null) {
            return 'No data';
        }

        if ($value < 0) {
            return 'Water';
        } elseif ($value < 0.1) {
            return 'Barren rock, sand, or snow';
        } elseif ($value < 0.2) {
            return 'Shrub and grassland';
        } elseif ($value < 0.3) {
            return 'Sparse vegetation';
        } elseif ($value < 0.6) {
            return 'Moderate vegetation';
        } else {
            return 'Dense vegetation';
        }
    }
}
```

---

## Step 5: Update Livewire Component

**File:** `resources/views/livewire/maps/satellite-viewer.blade.php`

Replace the computed properties:

```php
use App\Services\SentinelHubService; // Add this

// Replace SatelliteService with SentinelHubService
$satelliteData = computed(function () {
    $service = app(SentinelHubService::class);

    return $service->getSatelliteImagery(
        $this->selectedLat,
        $this->selectedLon,
        $this->selectedDate
    );
});

$ndviData = computed(function () {
    if (!$this->ndviEnabled) {
        return null;
    }

    $service = app(SentinelHubService::class);

    return $service->getNDVIData(
        $this->selectedLat,
        $this->selectedLon,
        $this->selectedDate
    );
});
```

---

## Step 6: Test

```bash
# Clear cache
ddev artisan cache:clear

# Create test script
ddev exec php -r "
\$service = app(\App\Services\SentinelHubService::class);
\$data = \$service->getSatelliteImagery(55.6761, 12.5683, '2024-01-01');
echo json_encode(\$data, JSON_PRETTY_PRINT);
"
```

Expected: Response in 2-5 seconds with real Sentinel-2 imagery!

---

## Benefits After Migration

✅ **Works from DDEV** - No more Docker network issues  
✅ **10x faster** - 2-5s vs 60-120s  
✅ **Better resolution** - 10m vs 30m  
✅ **More frequent updates** - Every 5 days vs 16 days  
✅ **European infrastructure** - Better for EU-based projects  
✅ **Industry standard** - Used by environmental agencies worldwide  

---

## Fallback Strategy

Keep both services and use Sentinel Hub as primary:

```php
$satelliteData = computed(function () {
    // Try Sentinel Hub first
    $sentinelService = app(SentinelHubService::class);
    $data = $sentinelService->getSatelliteImagery(...);
    
    if ($data) {
        return $data;
    }
    
    // Fallback to NASA (or mock)
    $nasaService = app(SatelliteService::class);
    return $nasaService->getSatelliteImagery(...);
});
```

---

**Want me to implement this migration for you?** It would solve the DDEV issue and provide better satellite data!

