<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CopernicusDataSpaceService
{
    private string $clientId;

    private string $clientSecret;

    private string $tokenUrl;

    private string $processUrl;

    private int $cacheTtl;

    public function __construct()
    {
        $this->clientId = config('services.copernicus_dataspace.client_id');
        $this->clientSecret = config('services.copernicus_dataspace.client_secret');
        $this->tokenUrl = config('services.copernicus_dataspace.token_url');
        $this->processUrl = config('services.copernicus_dataspace.process_url');
        $this->cacheTtl = config('services.copernicus_dataspace.cache_ttl', 3600);
    }

    /**
     * Get OAuth access token (cached for 1 hour)
     */
    private function getAccessToken(): ?string
    {
        // Check if we have a valid cached token
        $cachedData = Cache::get('copernicus_dataspace_token_data');

        if ($cachedData && isset($cachedData['token'], $cachedData['expires_at'])) {
            // Check if token is still valid (with 5 minute buffer)
            if (time() < $cachedData['expires_at'] - 300) {
                return $cachedData['token'];
            }

            Log::info('Copernicus token expired or near expiry, refreshing...');
        }

        // Fetch new token
        return $this->refreshAccessToken();
    }

    /**
     * Refresh the access token
     */
    private function refreshAccessToken(): ?string
    {
        try {
            Log::info('Fetching new Copernicus access token...');

            $response = Http::asForm()->post($this->tokenUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 3600; // Default to 1 hour

                // Cache token with expiry time
                Cache::put('copernicus_dataspace_token_data', [
                    'token' => $token,
                    'expires_at' => time() + $expiresIn,
                ], $expiresIn);

                Log::info('✅ New Copernicus token cached', ['expires_in' => $expiresIn.'s']);

                return $token;
            }

            Log::error('Copernicus Data Space OAuth failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Copernicus Data Space OAuth error', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Handle 401 errors by refreshing token and retrying
     */
    private function handleUnauthorized(callable $request, int $retryCount = 0): mixed
    {
        if ($retryCount > 1) {
            Log::warning('Max retry attempts reached for Copernicus request');

            return null;
        }

        Log::info('🔄 Got 401 Unauthorized, refreshing token and retrying...');

        // Clear cached token and get fresh one
        Cache::forget('copernicus_dataspace_token_data');
        $this->refreshAccessToken();

        // Retry the original request
        return $request($retryCount + 1);
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
        $cacheKey = "copernicus_imagery_{$latitude}_{$longitude}_{$date}_{$width}x{$height}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($latitude, $longitude, $date, $width, $height) {
            $token = $this->getAccessToken();
            if (! $token) {
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
                                    'crs' => 'http://www.opengis.net/def/crs/EPSG/0/4326',
                                ],
                            ],
                            'data' => [
                                [
                                    'type' => 'sentinel-2-l2a',
                                    'dataFilter' => [
                                        'timeRange' => [
                                            'from' => $date.'T00:00:00Z',
                                            'to' => $date.'T23:59:59Z',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'output' => [
                            'width' => $width,
                            'height' => $height,
                            'responses' => [
                                [
                                    'identifier' => 'default',
                                    'format' => ['type' => 'image/png'],
                                ],
                            ],
                        ],
                        'evalscript' => $this->getTrueColorRGBScript(),
                    ]);

                if ($response->successful()) {
                    $imageData = base64_encode($response->body());

                    Log::info('Copernicus Data Space imagery loaded successfully', [
                        'image_size' => strlen($imageData),
                        'date' => $date,
                        'location' => "{$latitude},{$longitude}",
                    ]);

                    return [
                        'url' => "data:image/png;base64,{$imageData}",
                        'date' => $date,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'source' => 'Sentinel-2 (Copernicus Data Space)',
                        'resolution' => '10m',
                        'provider' => 'copernicus_dataspace',
                    ];
                }

                Log::warning('Copernicus Data Space request failed', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                    'date' => $date,
                    'location' => "{$latitude},{$longitude}",
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Copernicus Data Space error', [
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
        ?string $date = null,
        int $retryCount = 0
    ): ?array {
        $date = $date ?? now()->subDays(7)->format('Y-m-d');
        $cacheKey = "copernicus_ndvi_{$latitude}_{$longitude}_{$date}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($latitude, $longitude, $date, $retryCount) {
            $token = $this->getAccessToken();
            if (! $token) {
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
                                    'crs' => 'http://www.opengis.net/def/crs/EPSG/0/4326',
                                ],
                            ],
                            'data' => [
                                [
                                    'type' => 'sentinel-2-l2a',
                                    'dataFilter' => [
                                        'timeRange' => [
                                            'from' => $date.'T00:00:00Z',
                                            'to' => $date.'T23:59:59Z',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'output' => [
                            'width' => 50,  // Higher resolution for better averaging
                            'height' => 50,
                            'responses' => [
                                [
                                    'identifier' => 'default',
                                    'format' => ['type' => 'image/png'],
                                ],
                            ],
                        ],
                        'evalscript' => $this->getNDVIScriptSimple(),
                    ]);

                if ($response->successful()) {
                    // The response is a PNG image where pixel values represent NDVI
                    // Each pixel value (0-255) maps to NDVI range (-1 to 1)
                    // We'll decode the image and calculate average

                    $imageData = $response->body();

                    // Decode PNG image using GD
                    $image = @imagecreatefromstring($imageData);

                    if ($image === false) {
                        Log::warning('Failed to decode NDVI PNG image', [
                            'image_size' => strlen($imageData),
                            'date' => $date,
                            'location' => "{$latitude},{$longitude}",
                        ]);

                        return null;
                    }

                    // Get image dimensions
                    $width = imagesx($image);
                    $height = imagesy($image);

                    // Sample pixels and calculate average NDVI
                    $ndviSum = 0;
                    $pixelCount = 0;

                    // Sample every pixel to get accurate average
                    for ($y = 0; $y < $height; $y++) {
                        for ($x = 0; $x < $width; $x++) {
                            $rgb = imagecolorat($image, $x, $y);

                            // Extract the red channel (grayscale TIFF uses same value for R, G, B)
                            $pixelValue = ($rgb >> 16) & 0xFF;

                            // Convert pixel value (0-255) back to NDVI (-1 to 1)
                            // NDVI = (pixelValue / 127.5) - 1
                            $ndvi = ($pixelValue / 127.5) - 1;

                            // Only count valid NDVI values
                            if ($ndvi >= -1 && $ndvi <= 1) {
                                $ndviSum += $ndvi;
                                $pixelCount++;
                            }
                        }
                    }

                    // Free memory
                    imagedestroy($image);

                    // Calculate average NDVI
                    $ndviValue = $pixelCount > 0 ? $ndviSum / $pixelCount : null;

                    if ($ndviValue === null) {
                        Log::warning('No valid NDVI pixels found', [
                            'date' => $date,
                            'location' => "{$latitude},{$longitude}",
                        ]);

                        return null;
                    }

                    Log::info('Copernicus Data Space NDVI calculated from image', [
                        'ndvi_value' => $ndviValue,
                        'pixel_count' => $pixelCount,
                        'image_size' => "{$width}x{$height}",
                        'date' => $date,
                        'location' => "{$latitude},{$longitude}",
                    ]);

                    return [
                        'ndvi_value' => $ndviValue,
                        'interpretation' => $this->interpretNDVI($ndviValue),
                        'date' => $date,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'source' => 'Sentinel-2 (Copernicus Data Space)',
                        'provider' => 'copernicus_dataspace',
                    ];
                }

                Log::warning('Copernicus Data Space NDVI request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'date' => $date,
                    'location' => "{$latitude},{$longitude}",
                ]);

                // Handle 401 Unauthorized by refreshing token and retrying
                if ($response->status() === 401 && $retryCount === 0) {
                    Log::info('🔄 Got 401 Unauthorized, refreshing token and retrying NDVI request...');
                    Cache::forget('copernicus_dataspace_token_data');

                    return $this->getNDVIData($latitude, $longitude, $date, $retryCount + 1);
                }

                return null;
            } catch (\Exception $e) {
                Log::error('Copernicus Data Space NDVI error', [
                    'message' => $e->getMessage(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

                return null;
            }
        });
    }

    /**
     * Get Soil Moisture data
     */
    public function getMoistureData(
        float $latitude,
        float $longitude,
        ?string $date = null,
        int $retryCount = 0
    ): ?array {
        $date = $date ?? now()->subDays(7)->format('Y-m-d');
        $cacheKey = "copernicus_moisture_{$latitude}_{$longitude}_{$date}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($latitude, $longitude, $date, $retryCount) {
            $token = $this->getAccessToken();
            if (! $token) {
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
                                    'crs' => 'http://www.opengis.net/def/crs/EPSG/0/4326',
                                ],
                            ],
                            'data' => [
                                [
                                    'type' => 'sentinel-2-l2a',
                                    'dataFilter' => [
                                        'timeRange' => [
                                            'from' => $date.'T00:00:00Z',
                                            'to' => $date.'T23:59:59Z',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'output' => [
                            'width' => 50,
                            'height' => 50,
                            'responses' => [
                                [
                                    'identifier' => 'default',
                                    'format' => ['type' => 'image/png'],
                                ],
                            ],
                        ],
                        'evalscript' => $this->getMoistureScript(),
                    ]);

                if ($response->successful()) {
                    $imageData = $response->body();
                    $image = @imagecreatefromstring($imageData);

                    if ($image === false) {
                        Log::warning('Failed to decode Moisture PNG image', [
                            'image_size' => strlen($imageData),
                            'date' => $date,
                            'location' => "{$latitude},{$longitude}",
                        ]);

                        return null;
                    }

                    // Get image dimensions
                    $width = imagesx($image);
                    $height = imagesy($image);

                    // Sample pixels and calculate average moisture index
                    $moistureSum = 0;
                    $pixelCount = 0;

                    for ($y = 0; $y < $height; $y++) {
                        for ($x = 0; $x < $width; $x++) {
                            $rgb = imagecolorat($image, $x, $y);
                            $pixelValue = ($rgb >> 16) & 0xFF;

                            // Convert pixel value (0-255) back to moisture index (-1 to 1)
                            $moisture = ($pixelValue / 127.5) - 1;

                            if ($moisture >= -1 && $moisture <= 1) {
                                $moistureSum += $moisture;
                                $pixelCount++;
                            }
                        }
                    }

                    imagedestroy($image);

                    $moistureValue = $pixelCount > 0 ? $moistureSum / $pixelCount : null;

                    if ($moistureValue === null) {
                        Log::warning('No valid moisture pixels found', [
                            'date' => $date,
                            'location' => "{$latitude},{$longitude}",
                        ]);

                        return null;
                    }

                    Log::info('Copernicus Data Space Moisture calculated from image', [
                        'moisture_value' => $moistureValue,
                        'pixel_count' => $pixelCount,
                        'image_size' => "{$width}x{$height}",
                        'date' => $date,
                        'location' => "{$latitude},{$longitude}",
                    ]);

                    return [
                        'moisture_value' => $moistureValue,
                        'interpretation' => $this->interpretMoisture($moistureValue),
                        'date' => $date,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'source' => 'Sentinel-2 (Copernicus Data Space)',
                        'provider' => 'copernicus_dataspace',
                    ];
                }

                Log::warning('Copernicus Data Space Moisture request failed', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                    'date' => $date,
                    'location' => "{$latitude},{$longitude}",
                ]);

                // Handle 401 Unauthorized by refreshing token and retrying
                if ($response->status() === 401 && $retryCount === 0) {
                    Log::info('🔄 Got 401 Unauthorized, refreshing token and retrying Moisture request...');
                    Cache::forget('copernicus_dataspace_token_data');

                    return $this->getMoistureData($latitude, $longitude, $date, $retryCount + 1);
                }

                return null;
            } catch (\Exception $e) {
                Log::error('Copernicus Data Space Moisture error', [
                    'message' => $e->getMessage(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

                return null;
            }
        });
    }

    /**
     * Find nearest date with satellite coverage for a location
     * Searches up to 30 days before and after the requested date
     */
    public function findNearestAvailableDate(
        float $latitude,
        float $longitude,
        string $targetDate
    ): ?string {
        $target = \Carbon\Carbon::parse($targetDate);

        // Try dates within ±15 days
        for ($offset = 0; $offset <= 15; $offset++) {
            // Try before
            if ($offset > 0) {
                $checkDate = $target->copy()->subDays($offset)->format('Y-m-d');
                if ($this->hasImageryForDate($latitude, $longitude, $checkDate)) {
                    Log::info('Found satellite data before target date', [
                        'target' => $targetDate,
                        'found' => $checkDate,
                        'offset_days' => -$offset,
                    ]);

                    return $checkDate;
                }
            }

            // Try after
            $checkDate = $target->copy()->addDays($offset)->format('Y-m-d');
            if ($this->hasImageryForDate($latitude, $longitude, $checkDate)) {
                Log::info('Found satellite data after target date', [
                    'target' => $targetDate,
                    'found' => $checkDate,
                    'offset_days' => $offset,
                ]);

                return $checkDate;
            }
        }

        Log::warning('No satellite data found within 15 days', [
            'target' => $targetDate,
            'location' => "{$latitude},{$longitude}",
        ]);

        return null;
    }

    /**
     * Check if imagery exists for a specific date
     */
    private function hasImageryForDate(
        float $latitude,
        float $longitude,
        string $date
    ): bool {
        // Quick check by trying to fetch imagery
        $imagery = $this->getSatelliteImagery($latitude, $longitude, $date, 10, 10);

        if (! $imagery) {
            return false;
        }

        // Check if image is not just black/empty
        // A real image should be larger than 1200 chars (base64)
        $imageSize = strlen($imagery['url'] ?? '');

        return $imageSize > 1500;
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
     * Evalscript for NDVI visualization with color mapping
     */
    private function getNDVIVisualizationScript(): string
    {
        return <<<'SCRIPT'
//VERSION=3
function setup() {
    return {
        input: ["B04", "B08"],
        output: { bands: 3, sampleType: "AUTO" }
    };
}

function evaluatePixel(sample) {
    // Calculate NDVI
    let ndvi = (sample.B08 - sample.B04) / (sample.B08 + sample.B04);

    // Colorize NDVI for visibility (blue = water, brown = soil, green = vegetation)
    let r, g, b;

    if (ndvi < 0) {
        // Water/Snow: Blue
        r = 0;
        g = 0;
        b = 1;
    } else if (ndvi < 0.2) {
        // Bare soil: Brown
        r = 0.6;
        g = 0.4;
        b = 0.2;
    } else if (ndvi < 0.4) {
        // Sparse vegetation: Yellow-green
        r = 0.8 - (ndvi * 2);
        g = 0.8;
        b = 0;
    } else {
        // Dense vegetation: Green
        r = 0;
        g = 0.6 + (ndvi * 0.4);
        b = 0;
    }

    return [r, g, b];
}
SCRIPT;
    }

    /**
     * Evalscript for NDVI calculation (simplified - outputs RGB image where all channels contain NDVI)
     */
    private function getNDVIScriptSimple(): string
    {
        return <<<'SCRIPT'
//VERSION=3
function setup() {
    return {
        input: ["B04", "B08"],
        output: { bands: 3, sampleType: "UINT8" }
    };
}

function evaluatePixel(sample) {
    let ndvi = (sample.B08 - sample.B04) / (sample.B08 + sample.B04);
    // Convert NDVI from [-1, 1] to [0, 255] for image output
    // NDVI = -1 => 0, NDVI = 0 => 127.5, NDVI = 1 => 255
    let value = (ndvi + 1) * 127.5;
    // Output same value to all RGB channels (grayscale RGB)
    return [value, value, value];
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
        input: ["B04", "B08", "dataMask"],
        output: [
            { id: "default", bands: 1 }
        ]
    };
}

function evaluatePixel(sample) {
    let ndvi = (sample.B08 - sample.B04) / (sample.B08 + sample.B04);
    return {
        default: [ndvi]
    };
}
SCRIPT;
    }

    /**
     * Evalscript for Moisture Index calculation (NDMI - Normalized Difference Moisture Index)
     */
    private function getMoistureScript(): string
    {
        return <<<'SCRIPT'
//VERSION=3
function setup() {
    return {
        input: ["B8A", "B11"],
        output: { bands: 3, sampleType: "UINT8" }
    };
}

function evaluatePixel(sample) {
    // Calculate NDMI (Normalized Difference Moisture Index)
    // NDMI = (NIR - SWIR) / (NIR + SWIR)
    // B8A = NIR (narrow), B11 = SWIR1
    let ndmi = (sample.B8A - sample.B11) / (sample.B8A + sample.B11);

    // Convert NDMI from [-1, 1] to [0, 255] for image output
    let value = (ndmi + 1) * 127.5;

    return [value, value, value];
}
SCRIPT;
    }

    /**
     * Evalscript for Moisture visualization overlay
     */
    private function getMoistureVisualizationScript(): string
    {
        return <<<'SCRIPT'
//VERSION=3
function setup() {
    return {
        input: ["B8A", "B11"],
        output: { bands: 3, sampleType: "AUTO" }
    };
}

function evaluatePixel(sample) {
    // Calculate NDMI
    let ndmi = (sample.B8A - sample.B11) / (sample.B8A + sample.B11);

    // Colorize moisture for visibility
    let r, g, b;

    if (ndmi < -0.4) {
        // Very dry: Red-brown
        r = 0.8;
        g = 0.3;
        b = 0.1;
    } else if (ndmi < -0.2) {
        // Dry: Orange
        r = 0.9;
        g = 0.5;
        b = 0.2;
    } else if (ndmi < 0) {
        // Moderate dry: Yellow
        r = 0.9;
        g = 0.8;
        b = 0.3;
    } else if (ndmi < 0.2) {
        // Moderate wet: Light blue
        r = 0.5;
        g = 0.8;
        b = 0.9;
    } else if (ndmi < 0.4) {
        // Wet: Blue
        r = 0.2;
        g = 0.5;
        b = 0.9;
    } else {
        // Very wet: Dark blue
        r = 0.1;
        g = 0.3;
        b = 0.7;
    }

    return [r, g, b];
}
SCRIPT;
    }

    /**
     * Interpret Moisture Index value
     */
    private function interpretMoisture(?float $value): string
    {
        if ($value === null) {
            return 'No data';
        }

        if ($value < -0.4) {
            return 'Very dry';
        } elseif ($value < -0.2) {
            return 'Dry';
        } elseif ($value < 0) {
            return 'Moderate dry';
        } elseif ($value < 0.2) {
            return 'Moderate wet';
        } elseif ($value < 0.4) {
            return 'Wet';
        } else {
            return 'Very wet / Water bodies';
        }
    }

     /**
     * Evalscript for NDRE visualization overlay
     */
    private function getNDREVisualizationScript(): string
    {
        return <<<'SCRIPT'
//VERSION=3
function setup() {
    return {
        input: ["B05", "B08"],
        output: { bands: 3, sampleType: "AUTO" }
    };
}

function evaluatePixel(sample) {
    // Calculate NDRE
    let ndre = (sample.B08 - sample.B05) / (sample.B08 + sample.B05);

    // Colorize NDRE for visibility (similar to NDVI but more sensitive to chlorophyll)
    let r, g, b;

    if (ndre < 0) {
        // Low chlorophyll: Brown
        r = 0.6;
        g = 0.4;
        b = 0.2;
    } else if (ndre < 0.3) {
        // Moderate chlorophyll: Yellow-green
        r = 0.8 - (ndre * 2);
        g = 0.8;
        b = 0.2;
    } else {
        // High chlorophyll: Green
        r = 0;
        g = 0.5 + (ndre * 0.5);
        b = 0;
    }

    return [r, g, b];
}
SCRIPT;
    }

    /**
     * Evalscript for EVI visualization overlay
     */
    private function getEVIVisualizationScript(): string
    {
        return <<<'SCRIPT'
//VERSION=3
function setup() {
    return {
        input: ["B02", "B04", "B08"],
        output: { bands: 3, sampleType: "AUTO" }
    };
}

function evaluatePixel(sample) {
    // Calculate EVI
    let evi = 2.5 * ((sample.B08 - sample.B04) / (sample.B08 + 6 * sample.B04 - 7.5 * sample.B02 + 1));

    // Colorize EVI for visibility
    let r, g, b;

    if (evi < 0) {
        // Non-vegetated: Brown
        r = 0.6;
        g = 0.4;
        b = 0.2;
    } else if (evi < 0.3) {
        // Sparse vegetation: Yellow-green
        r = 0.7;
        g = 0.7;
        b = 0.1;
    } else if (evi < 0.6) {
        // Moderate vegetation: Light green
        r = 0.2;
        g = 0.7;
        b = 0.1;
    } else {
        // Dense vegetation: Dark green
        r = 0;
        g = 0.4 + (Math.min(evi, 1) * 0.3);
        b = 0;
    }

    return [r, g, b];
}
SCRIPT;
    }

    /**
     * Evalscript for MSI visualization overlay
     */
    private function getMSIVisualizationScript(): string
    {
        return <<<'SCRIPT'
//VERSION=3
function setup() {
    return {
        input: ["B08", "B11"],
        output: { bands: 3, sampleType: "AUTO" }
    };
}

function evaluatePixel(sample) {
    // Calculate MSI (inverse of moisture - higher = more stress)
    let msi = sample.B11 / sample.B08;

    // Colorize MSI for visibility (inverse of moisture)
    let r, g, b;

    if (msi < 0.4) {
        // Low stress (wet): Blue
        r = 0.1;
        g = 0.5;
        b = 0.9;
    } else if (msi < 0.8) {
        // Moderate stress: Light blue
        r = 0.5;
        g = 0.8;
        b = 0.9;
    } else if (msi < 1.2) {
        // Moderate-high stress: Yellow
        r = 0.9;
        g = 0.8;
        b = 0.3;
    } else if (msi < 1.6) {
        // High stress: Orange
        r = 0.9;
        g = 0.5;
        b = 0.2;
    } else {
        // Severe stress (dry): Red-brown
        r = 0.8;
        g = 0.3;
        b = 0.1;
    }

    return [r, g, b];
}
SCRIPT;
    }

    /**
     * Evalscript for SAVI visualization overlay
     */
    private function getSAVIVisualizationScript(): string
    {
        return <<<'SCRIPT'
//VERSION=3
function setup() {
    return {
        input: ["B04", "B08"],
        output: { bands: 3, sampleType: "AUTO" }
    };
}

function evaluatePixel(sample) {
    // Calculate SAVI (Soil-Adjusted Vegetation Index)
    let savi = ((sample.B08 - sample.B04) / (sample.B08 + sample.B04 + 0.5)) * 1.5;

    // Colorize SAVI for visibility
    let r, g, b;

    if (savi < 0) {
        // Bare soil: Brown
        r = 0.7;
        g = 0.5;
        b = 0.3;
    } else if (savi < 0.2) {
        // Sparse vegetation: Beige-yellow
        r = 0.8;
        g = 0.7;
        b = 0.4;
    } else if (savi < 0.4) {
        // Moderate vegetation: Yellow-green
        r = 0.6;
        g = 0.8;
        b = 0.2;
    } else {
        // Dense vegetation: Green
        r = 0;
        g = 0.5 + (Math.min(savi, 1) * 0.3);
        b = 0.1;
    }

    return [r, g, b];
}
SCRIPT;
    }

    /**
     * Evalscript for GNDVI visualization overlay
     */
    private function getGNDVIVisualizationScript(): string
    {
        return <<<'SCRIPT'
//VERSION=3
function setup() {
    return {
        input: ["B03", "B08"],
        output: { bands: 3, sampleType: "AUTO" }
    };
}

function evaluatePixel(sample) {
    // Calculate GNDVI (Green NDVI)
    let gndvi = (sample.B08 - sample.B03) / (sample.B08 + sample.B03);

    // Colorize GNDVI for visibility
    let r, g, b;

    if (gndvi < 0) {
        // Low/no chlorophyll: Brown
        r = 0.6;
        g = 0.4;
        b = 0.2;
    } else if (gndvi < 0.3) {
        // Low chlorophyll: Yellow
        r = 0.8;
        g = 0.8;
        b = 0.1;
    } else if (gndvi < 0.6) {
        // Moderate chlorophyll: Light green
        r = 0.3;
        g = 0.7;
        b = 0.2;
    } else {
        // High chlorophyll: Dark green
        r = 0;
        g = 0.4 + (Math.min(gndvi, 1) * 0.4);
        b = 0;
    }

    return [r, g, b];
}
SCRIPT;
    }

    /**
     * Get overlay visualization for specified type
     */
    public function getOverlayVisualization(
        float $latitude,
        float $longitude,
        ?string $date = null,
        string $overlayType = 'ndvi',
        int $width = 512,
        int $height = 512,
        int $retryCount = 0
    ): ?array {
        $date = $date ?? now()->subDays(7)->format('Y-m-d');

        // Use exact coordinates (don't round - preserve what was passed in)
        $lat = $latitude;
        $lon = $longitude;

        $cacheKey = "copernicus_overlay_{$overlayType}_{$lat}_{$lon}_{$date}_{$width}x{$height}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($lat, $lon, $date, $overlayType, $width, $height, $retryCount) {
            $token = $this->getAccessToken();
            if (! $token) {
                return null;
            }

            // Select appropriate evalscript based on overlay type
            $evalscript = match ($overlayType) {
                'ndvi' => $this->getNDVIVisualizationScript(),
                'moisture' => $this->getMoistureVisualizationScript(),
                'ndre' => $this->getNDREVisualizationScript(),
                'evi' => $this->getEVIVisualizationScript(),
                'msi' => $this->getMSIVisualizationScript(),
                'savi' => $this->getSAVIVisualizationScript(),
                'gndvi' => $this->getGNDVIVisualizationScript(),
                'truecolor' => $this->getTrueColorRGBScript(),
                default => $this->getNDVIVisualizationScript(),
            };

            try {
                $bbox = $this->calculateBBox($lat, $lon, 0.025);

                $response = Http::timeout(15)
                    ->withToken($token)
                    ->post($this->processUrl, [
                        'input' => [
                            'bounds' => [
                                'bbox' => $bbox,
                                'properties' => [
                                    'crs' => 'http://www.opengis.net/def/crs/EPSG/0/4326',
                                ],
                            ],
                            'data' => [
                                [
                                    'type' => 'sentinel-2-l2a',
                                    'dataFilter' => [
                                        'timeRange' => [
                                            'from' => $date.'T00:00:00Z',
                                            'to' => $date.'T23:59:59Z',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'output' => [
                            'width' => $width,
                            'height' => $height,
                            'responses' => [
                                [
                                    'identifier' => 'default',
                                    'format' => ['type' => 'image/png'],
                                ],
                            ],
                        ],
                        'evalscript' => $evalscript,
                    ]);

                if ($response->successful()) {
                    $imageData = base64_encode($response->body());

                    Log::info('Copernicus Data Space overlay loaded successfully', [
                        'overlay_type' => $overlayType,
                        'image_size' => strlen($imageData),
                        'date' => $date,
                        'location' => "{$lat},{$lon}",
                    ]);

                    return [
                        'url' => "data:image/png;base64,{$imageData}",
                        'date' => $date,
                        'latitude' => $lat,
                        'longitude' => $lon,
                        'source' => 'Sentinel-2 (Copernicus Data Space)',
                        'resolution' => '10m',
                        'provider' => 'copernicus_dataspace',
                        'overlay_type' => $overlayType,
                    ];
                }

                // Handle 401 Unauthorized by refreshing token and retrying
                if ($response->status() === 401 && $retryCount === 0) {
                    Log::info('🔄 Got 401 Unauthorized, refreshing token and retrying overlay request...');
                    Cache::forget('copernicus_dataspace_token_data');

                    return $this->getOverlayVisualization($lat, $lon, $date, $overlayType, $width, $height, $retryCount + 1);
                }

                Log::warning('Copernicus Data Space overlay request failed', [
                    'overlay_type' => $overlayType,
                    'status' => $response->status(),
                    'date' => $date,
                    'location' => "{$lat},{$lon}",
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Copernicus Data Space overlay error', [
                    'overlay_type' => $overlayType,
                    'message' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Evalscript for actual RGB true color (not NDVI visualization)
     */
    private function getTrueColorRGBScript(): string
    {
        return <<<'SCRIPT'
//VERSION=3
function setup() {
    return {
        input: ["B02", "B03", "B04"],
        output: { bands: 3, sampleType: "AUTO" }
    };
}

function evaluatePixel(sample) {
    let gain = 3.0;
    return [
        gain * sample.B04,
        gain * sample.B03,
        gain * sample.B02
    ];
}
SCRIPT;
    }

    /**
     * Get NDRE data (Normalized Difference Red Edge) - Best for Chlorophyll Content
     * Validates: Chlorophyll Content (µg/cm²), Canopy Chlorophyll Content (g/m²)
     * R² = 0.80-0.90
     */
    public function getNDREData(
        float $latitude,
        float $longitude,
        ?string $date = null,
        int $retryCount = 0
    ): ?array {
        $date = $date ?? now()->subDays(7)->format('Y-m-d');
        $cacheKey = "copernicus_ndre_{$latitude}_{$longitude}_{$date}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($latitude, $longitude, $date, $retryCount) {
            $token = $this->getAccessToken();
            if (! $token) {
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
                                    'crs' => 'http://www.opengis.net/def/crs/EPSG/0/4326',
                                ],
                            ],
                            'data' => [
                                [
                                    'type' => 'sentinel-2-l2a',
                                    'dataFilter' => [
                                        'timeRange' => [
                                            'from' => $date.'T00:00:00Z',
                                            'to' => $date.'T23:59:59Z',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'output' => [
                            'width' => 50,
                            'height' => 50,
                            'responses' => [
                                [
                                    'identifier' => 'default',
                                    'format' => ['type' => 'image/png'],
                                ],
                            ],
                        ],
                        'evalscript' => $this->getNDREScript(),
                    ]);

                if ($response->successful()) {
                    $imageData = $response->body();
                    $image = @imagecreatefromstring($imageData);

                    if ($image === false) {
                        Log::warning('Failed to decode NDRE PNG image', [
                            'image_size' => strlen($imageData),
                            'date' => $date,
                            'location' => "{$latitude},{$longitude}",
                        ]);

                        return null;
                    }

                    $width = imagesx($image);
                    $height = imagesy($image);

                    $ndreSum = 0;
                    $pixelCount = 0;

                    for ($y = 0; $y < $height; $y++) {
                        for ($x = 0; $x < $width; $x++) {
                            $rgb = imagecolorat($image, $x, $y);
                            $pixelValue = ($rgb >> 16) & 0xFF;

                            // Convert pixel value (0-255) back to NDRE (-1 to 1)
                            $ndre = ($pixelValue / 127.5) - 1;

                            if ($ndre >= -1 && $ndre <= 1) {
                                $ndreSum += $ndre;
                                $pixelCount++;
                            }
                        }
                    }

                    imagedestroy($image);

                    $ndreValue = $pixelCount > 0 ? $ndreSum / $pixelCount : null;

                    if ($ndreValue === null) {
                        Log::warning('No valid NDRE pixels found', [
                            'date' => $date,
                            'location' => "{$latitude},{$longitude}",
                        ]);

                        return null;
                    }

                    Log::info('Copernicus Data Space NDRE calculated', [
                        'ndre_value' => $ndreValue,
                        'pixel_count' => $pixelCount,
                        'date' => $date,
                        'location' => "{$latitude},{$longitude}",
                    ]);

                    return [
                        'value' => $ndreValue,
                        'date' => $date,
                        'cloud_coverage' => null,
                        'metadata' => [
                            'index' => 'NDRE',
                            'description' => 'Normalized Difference Red Edge - Chlorophyll Content',
                            'correlation' => 'R² = 0.80-0.90',
                            'validates' => ['Chlorophyll Content', 'Canopy Chlorophyll Content'],
                        ],
                    ];
                }

                if ($response->status() === 401 && $retryCount < 2) {
                    Cache::forget('copernicus_dataspace_token_data');

                    return $this->getNDREData($latitude, $longitude, $date, $retryCount + 1);
                }

                Log::error('Copernicus Data Space NDRE error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Copernicus Data Space NDRE exception', [
                    'message' => $e->getMessage(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

                return null;
            }
        });
    }

    /**
     * Get EVI data (Enhanced Vegetation Index) - Better than NDVI for dense canopy
     * Validates: LAI (m²/m²), FAPAR
     * R² = 0.75-0.85
     */
    public function getEVIData(
        float $latitude,
        float $longitude,
        ?string $date = null,
        int $retryCount = 0
    ): ?array {
        $date = $date ?? now()->subDays(7)->format('Y-m-d');
        $cacheKey = "copernicus_evi_{$latitude}_{$longitude}_{$date}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($latitude, $longitude, $date, $retryCount) {
            $token = $this->getAccessToken();
            if (! $token) {
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
                                    'crs' => 'http://www.opengis.net/def/crs/EPSG/0/4326',
                                ],
                            ],
                            'data' => [
                                [
                                    'type' => 'sentinel-2-l2a',
                                    'dataFilter' => [
                                        'timeRange' => [
                                            'from' => $date.'T00:00:00Z',
                                            'to' => $date.'T23:59:59Z',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'output' => [
                            'width' => 50,
                            'height' => 50,
                            'responses' => [
                                [
                                    'identifier' => 'default',
                                    'format' => ['type' => 'image/png'],
                                ],
                            ],
                        ],
                        'evalscript' => $this->getEVIScript(),
                    ]);

                if ($response->successful()) {
                    $imageData = $response->body();
                    $image = @imagecreatefromstring($imageData);

                    if ($image === false) {
                        Log::warning('Failed to decode EVI PNG image', [
                            'image_size' => strlen($imageData),
                            'date' => $date,
                            'location' => "{$latitude},{$longitude}",
                        ]);

                        return null;
                    }

                    $width = imagesx($image);
                    $height = imagesy($image);

                    $eviSum = 0;
                    $pixelCount = 0;

                    for ($y = 0; $y < $height; $y++) {
                        for ($x = 0; $x < $width; $x++) {
                            $rgb = imagecolorat($image, $x, $y);
                            $pixelValue = ($rgb >> 16) & 0xFF;

                            // Convert pixel value (0-255) back to EVI (-1 to 1)
                            $evi = ($pixelValue / 127.5) - 1;

                            if ($evi >= -1 && $evi <= 1) {
                                $eviSum += $evi;
                                $pixelCount++;
                            }
                        }
                    }

                    imagedestroy($image);

                    $eviValue = $pixelCount > 0 ? $eviSum / $pixelCount : null;

                    if ($eviValue === null) {
                        Log::warning('No valid EVI pixels found', [
                            'date' => $date,
                            'location' => "{$latitude},{$longitude}",
                        ]);

                        return null;
                    }

                    Log::info('Copernicus Data Space EVI calculated', [
                        'evi_value' => $eviValue,
                        'pixel_count' => $pixelCount,
                        'date' => $date,
                        'location' => "{$latitude},{$longitude}",
                    ]);

                    return [
                        'value' => $eviValue,
                        'date' => $date,
                        'cloud_coverage' => null,
                        'metadata' => [
                            'index' => 'EVI',
                            'description' => 'Enhanced Vegetation Index - Better for dense canopy',
                            'correlation' => 'R² = 0.75-0.85',
                            'validates' => ['Leaf Area Index (LAI)', 'FAPAR'],
                        ],
                    ];
                }

                if ($response->status() === 401 && $retryCount < 2) {
                    Cache::forget('copernicus_dataspace_token_data');

                    return $this->getEVIData($latitude, $longitude, $date, $retryCount + 1);
                }

                Log::error('Copernicus Data Space EVI error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Copernicus Data Space EVI exception', [
                    'message' => $e->getMessage(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

                return null;
            }
        });
    }

    /**
     * Get MSI data (Moisture Stress Index) - Complements NDMI for soil moisture
     * Validates: Soil Moisture (% VWC)
     * R² = 0.70-0.80
     */
    public function getMSIData(
        float $latitude,
        float $longitude,
        ?string $date = null,
        int $retryCount = 0
    ): ?array {
        $date = $date ?? now()->subDays(7)->format('Y-m-d');
        $cacheKey = "copernicus_msi_{$latitude}_{$longitude}_{$date}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($latitude, $longitude, $date, $retryCount) {
            $token = $this->getAccessToken();
            if (! $token) {
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
                                    'crs' => 'http://www.opengis.net/def/crs/EPSG/0/4326',
                                ],
                            ],
                            'data' => [
                                [
                                    'type' => 'sentinel-2-l2a',
                                    'dataFilter' => [
                                        'timeRange' => [
                                            'from' => $date.'T00:00:00Z',
                                            'to' => $date.'T23:59:59Z',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'output' => [
                            'width' => 50,
                            'height' => 50,
                            'responses' => [
                                [
                                    'identifier' => 'default',
                                    'format' => ['type' => 'image/png'],
                                ],
                            ],
                        ],
                        'evalscript' => $this->getMSIScript(),
                    ]);

                if ($response->successful()) {
                    $imageData = $response->body();
                    $image = @imagecreatefromstring($imageData);

                    if ($image === false) {
                        Log::warning('Failed to decode MSI PNG image', [
                            'image_size' => strlen($imageData),
                            'date' => $date,
                            'location' => "{$latitude},{$longitude}",
                        ]);

                        return null;
                    }

                    $width = imagesx($image);
                    $height = imagesy($image);

                    $msiSum = 0;
                    $pixelCount = 0;

                    for ($y = 0; $y < $height; $y++) {
                        for ($x = 0; $x < $width; $x++) {
                            $rgb = imagecolorat($image, $x, $y);
                            $pixelValue = ($rgb >> 16) & 0xFF;

                            // Convert pixel value (0-255) to MSI (0-3)
                            // We scale 0-255 to 0-3 range
                            $msi = ($pixelValue / 255.0) * 3.0;

                            if ($msi >= 0 && $msi <= 3) {
                                $msiSum += $msi;
                                $pixelCount++;
                            }
                        }
                    }

                    imagedestroy($image);

                    $msiValue = $pixelCount > 0 ? $msiSum / $pixelCount : null;

                    if ($msiValue === null) {
                        Log::warning('No valid MSI pixels found', [
                            'date' => $date,
                            'location' => "{$latitude},{$longitude}",
                        ]);

                        return null;
                    }

                    Log::info('Copernicus Data Space MSI calculated', [
                        'msi_value' => $msiValue,
                        'pixel_count' => $pixelCount,
                        'date' => $date,
                        'location' => "{$latitude},{$longitude}",
                    ]);

                    return [
                        'value' => $msiValue,
                        'date' => $date,
                        'cloud_coverage' => null,
                        'metadata' => [
                            'index' => 'MSI',
                            'description' => 'Moisture Stress Index - Inverse moisture indicator',
                            'correlation' => 'R² = 0.70-0.80',
                            'validates' => ['Soil Moisture'],
                            'note' => 'Higher MSI = drier conditions (inverse of NDMI)',
                        ],
                    ];
                }

                if ($response->status() === 401 && $retryCount < 2) {
                    Cache::forget('copernicus_dataspace_token_data');

                    return $this->getMSIData($latitude, $longitude, $date, $retryCount + 1);
                }

                Log::error('Copernicus Data Space MSI error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Copernicus Data Space MSI exception', [
                    'message' => $e->getMessage(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

                return null;
            }
        });
    }

    /**
     * Get SAVI data (Soil-Adjusted Vegetation Index) - Better for sparse vegetation
     * Validates: LAI (m²/m²) in agricultural/semi-arid areas
     * R² = 0.70-0.80
     */
    public function getSAVIData(
        float $latitude,
        float $longitude,
        ?string $date = null,
        int $retryCount = 0
    ): ?array {
        $date = $date ?? now()->subDays(7)->format('Y-m-d');
        $cacheKey = "copernicus_savi_{$latitude}_{$longitude}_{$date}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($latitude, $longitude, $date, $retryCount) {
            $token = $this->getAccessToken();
            if (! $token) {
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
                                    'crs' => 'http://www.opengis.net/def/crs/EPSG/0/4326',
                                ],
                            ],
                            'data' => [
                                [
                                    'type' => 'sentinel-2-l2a',
                                    'dataFilter' => [
                                        'timeRange' => [
                                            'from' => $date.'T00:00:00Z',
                                            'to' => $date.'T23:59:59Z',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'output' => [
                            'width' => 50,
                            'height' => 50,
                            'responses' => [
                                [
                                    'identifier' => 'default',
                                    'format' => ['type' => 'image/png'],
                                ],
                            ],
                        ],
                        'evalscript' => $this->getSAVIScript(),
                    ]);

                if ($response->successful()) {
                    $imageData = $response->body();
                    $image = @imagecreatefromstring($imageData);

                    if ($image === false) {
                        Log::warning('Failed to decode SAVI PNG image', [
                            'image_size' => strlen($imageData),
                            'date' => $date,
                            'location' => "{$latitude},{$longitude}",
                        ]);

                        return null;
                    }

                    $width = imagesx($image);
                    $height = imagesy($image);

                    $saviSum = 0;
                    $pixelCount = 0;

                    for ($y = 0; $y < $height; $y++) {
                        for ($x = 0; $x < $width; $x++) {
                            $rgb = imagecolorat($image, $x, $y);
                            $pixelValue = ($rgb >> 16) & 0xFF;

                            // Convert pixel value (0-255) back to SAVI (-1 to 1)
                            $savi = ($pixelValue / 127.5) - 1;

                            if ($savi >= -1 && $savi <= 1) {
                                $saviSum += $savi;
                                $pixelCount++;
                            }
                        }
                    }

                    imagedestroy($image);

                    $saviValue = $pixelCount > 0 ? $saviSum / $pixelCount : null;

                    if ($saviValue === null) {
                        Log::warning('No valid SAVI pixels found', [
                            'date' => $date,
                            'location' => "{$latitude},{$longitude}",
                        ]);

                        return null;
                    }

                    Log::info('Copernicus Data Space SAVI calculated', [
                        'savi_value' => $saviValue,
                        'pixel_count' => $pixelCount,
                        'date' => $date,
                        'location' => "{$latitude},{$longitude}",
                    ]);

                    return [
                        'value' => $saviValue,
                        'date' => $date,
                        'cloud_coverage' => null,
                        'metadata' => [
                            'index' => 'SAVI',
                            'description' => 'Soil-Adjusted Vegetation Index - Better for sparse vegetation',
                            'correlation' => 'R² = 0.70-0.80',
                            'validates' => ['Leaf Area Index (LAI)'],
                            'note' => 'Corrects for soil brightness in sparse canopy',
                        ],
                    ];
                }

                if ($response->status() === 401 && $retryCount < 2) {
                    Cache::forget('copernicus_dataspace_token_data');

                    return $this->getSAVIData($latitude, $longitude, $date, $retryCount + 1);
                }

                Log::error('Copernicus Data Space SAVI error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Copernicus Data Space SAVI exception', [
                    'message' => $e->getMessage(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

                return null;
            }
        });
    }

    /**
     * Get GNDVI data (Green Normalized Difference Vegetation Index) - Sensitive to chlorophyll
     * Validates: Chlorophyll Content (µg/cm²)
     * R² = 0.75-0.85
     */
    public function getGNDVIData(
        float $latitude,
        float $longitude,
        ?string $date = null,
        int $retryCount = 0
    ): ?array {
        $date = $date ?? now()->subDays(7)->format('Y-m-d');
        $cacheKey = "copernicus_gndvi_{$latitude}_{$longitude}_{$date}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($latitude, $longitude, $date, $retryCount) {
            $token = $this->getAccessToken();
            if (! $token) {
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
                                    'crs' => 'http://www.opengis.net/def/crs/EPSG/0/4326',
                                ],
                            ],
                            'data' => [
                                [
                                    'type' => 'sentinel-2-l2a',
                                    'dataFilter' => [
                                        'timeRange' => [
                                            'from' => $date.'T00:00:00Z',
                                            'to' => $date.'T23:59:59Z',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'output' => [
                            'width' => 50,
                            'height' => 50,
                            'responses' => [
                                [
                                    'identifier' => 'default',
                                    'format' => ['type' => 'image/png'],
                                ],
                            ],
                        ],
                        'evalscript' => $this->getGNDVIScript(),
                    ]);

                if ($response->successful()) {
                    $imageData = $response->body();
                    $image = @imagecreatefromstring($imageData);

                    if ($image === false) {
                        Log::warning('Failed to decode GNDVI PNG image', [
                            'image_size' => strlen($imageData),
                            'date' => $date,
                            'location' => "{$latitude},{$longitude}",
                        ]);

                        return null;
                    }

                    $width = imagesx($image);
                    $height = imagesy($image);

                    $gndviSum = 0;
                    $pixelCount = 0;

                    for ($y = 0; $y < $height; $y++) {
                        for ($x = 0; $x < $width; $x++) {
                            $rgb = imagecolorat($image, $x, $y);
                            $pixelValue = ($rgb >> 16) & 0xFF;

                            // Convert pixel value (0-255) back to GNDVI (-1 to 1)
                            $gndvi = ($pixelValue / 127.5) - 1;

                            if ($gndvi >= -1 && $gndvi <= 1) {
                                $gndviSum += $gndvi;
                                $pixelCount++;
                            }
                        }
                    }

                    imagedestroy($image);

                    $gndviValue = $pixelCount > 0 ? $gndviSum / $pixelCount : null;

                    if ($gndviValue === null) {
                        Log::warning('No valid GNDVI pixels found', [
                            'date' => $date,
                            'location' => "{$latitude},{$longitude}",
                        ]);

                        return null;
                    }

                    Log::info('Copernicus Data Space GNDVI calculated', [
                        'gndvi_value' => $gndviValue,
                        'pixel_count' => $pixelCount,
                        'date' => $date,
                        'location' => "{$latitude},{$longitude}",
                    ]);

                    return [
                        'value' => $gndviValue,
                        'date' => $date,
                        'cloud_coverage' => null,
                        'metadata' => [
                            'index' => 'GNDVI',
                            'description' => 'Green NDVI - More sensitive to chlorophyll',
                            'correlation' => 'R² = 0.75-0.85',
                            'validates' => ['Chlorophyll Content'],
                            'note' => 'Alternative/validation for NDRE',
                        ],
                    ];
                }

                if ($response->status() === 401 && $retryCount < 2) {
                    Cache::forget('copernicus_dataspace_token_data');

                    return $this->getGNDVIData($latitude, $longitude, $date, $retryCount + 1);
                }

                Log::error('Copernicus Data Space GNDVI error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Copernicus Data Space GNDVI exception', [
                    'message' => $e->getMessage(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

                return null;
            }
        });
    }

    /**
     * Evalscript for NDRE calculation
     * Formula: (B08 - B05) / (B08 + B05)
     * B05 = Red Edge (705nm), B08 = NIR (842nm)
     */
    private function getNDREScript(): string
    {
        return <<<'SCRIPT'
//VERSION=3
function setup() {
    return {
        input: ["B05", "B08"],
        output: { bands: 3, sampleType: "UINT8" }
    };
}

function evaluatePixel(sample) {
    let ndre = (sample.B08 - sample.B05) / (sample.B08 + sample.B05);
    // Convert NDRE from [-1, 1] to [0, 255] for image output
    let value = (ndre + 1) * 127.5;
    return [value, value, value];
}
SCRIPT;
    }

    /**
     * Evalscript for EVI calculation
     * Formula: 2.5 * ((B08 - B04) / (B08 + 6*B04 - 7.5*B02 + 1))
     * B02 = Blue, B04 = Red, B08 = NIR
     */
    private function getEVIScript(): string
    {
        return <<<'SCRIPT'
//VERSION=3
function setup() {
    return {
        input: ["B02", "B04", "B08"],
        output: { bands: 3, sampleType: "UINT8" }
    };
}

function evaluatePixel(sample) {
    let evi = 2.5 * ((sample.B08 - sample.B04) / (sample.B08 + 6*sample.B04 - 7.5*sample.B02 + 1));
    // EVI can range beyond [-1, 1], so we clamp it
    evi = Math.max(-1, Math.min(1, evi));
    // Convert EVI from [-1, 1] to [0, 255] for image output
    let value = (evi + 1) * 127.5;
    return [value, value, value];
}
SCRIPT;
    }

    /**
     * Evalscript for MSI calculation
     * Formula: B11 / B08
     * B08 = NIR (842nm), B11 = SWIR1 (1610nm)
     */
    private function getMSIScript(): string
    {
        return <<<'SCRIPT'
//VERSION=3
function setup() {
    return {
        input: ["B08", "B11"],
        output: { bands: 3, sampleType: "UINT8" }
    };
}

function evaluatePixel(sample) {
    let msi = sample.B11 / sample.B08;
    // MSI typically ranges from 0 to 3+
    // We'll map 0-3 to 0-255
    let value = Math.min(255, (msi / 3.0) * 255);
    return [value, value, value];
}
SCRIPT;
    }

    /**
     * Evalscript for SAVI calculation
     * Formula: ((B08 - B04) / (B08 + B04 + 0.5)) * 1.5
     * B04 = Red (665nm), B08 = NIR (842nm)
     * L = 0.5 (soil brightness correction factor)
     */
    private function getSAVIScript(): string
    {
        return <<<'SCRIPT'
//VERSION=3
function setup() {
    return {
        input: ["B04", "B08"],
        output: { bands: 3, sampleType: "UINT8" }
    };
}

function evaluatePixel(sample) {
    let L = 0.5; // Soil brightness correction factor
    let savi = ((sample.B08 - sample.B04) / (sample.B08 + sample.B04 + L)) * (1 + L);
    // Convert SAVI from [-1, 1] to [0, 255] for image output
    let value = (savi + 1) * 127.5;
    return [value, value, value];
}
SCRIPT;
    }

    /**
     * Evalscript for GNDVI calculation
     * Formula: (B08 - B03) / (B08 + B03)
     * B03 = Green (560nm), B08 = NIR (842nm)
     */
    private function getGNDVIScript(): string
    {
        return <<<'SCRIPT'
//VERSION=3
function setup() {
    return {
        input: ["B03", "B08"],
        output: { bands: 3, sampleType: "UINT8" }
    };
}

function evaluatePixel(sample) {
    let gndvi = (sample.B08 - sample.B03) / (sample.B08 + sample.B03);
    // Convert GNDVI from [-1, 1] to [0, 255] for image output
    let value = (gndvi + 1) * 127.5;
    return [value, value, value];
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
