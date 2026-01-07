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
        return Cache::remember('copernicus_dataspace_token', 3600, function () {
            try {
                $response = Http::asForm()->post($this->tokenUrl, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ]);

                if ($response->successful()) {
                    return $response->json()['access_token'];
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
        ?string $date = null
    ): ?array {
        $date = $date ?? now()->subDays(7)->format('Y-m-d');
        $cacheKey = "copernicus_ndvi_{$latitude}_{$longitude}_{$date}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($latitude, $longitude, $date) {
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
                    'body' => substr($response->body(), 0, 500),
                    'date' => $date,
                    'location' => "{$latitude},{$longitude}",
                ]);

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
        ?string $date = null
    ): ?array {
        $date = $date ?? now()->subDays(7)->format('Y-m-d');
        $cacheKey = "copernicus_moisture_{$latitude}_{$longitude}_{$date}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($latitude, $longitude, $date) {
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
     * Get overlay visualization for specified type
     */
    public function getOverlayVisualization(
        float $latitude,
        float $longitude,
        ?string $date = null,
        string $overlayType = 'ndvi',
        int $width = 512,
        int $height = 512
    ): ?array {
        $date = $date ?? now()->subDays(7)->format('Y-m-d');

        // Use exact coordinates (don't round - preserve what was passed in)
        $lat = $latitude;
        $lon = $longitude;

        $cacheKey = "copernicus_overlay_{$overlayType}_{$lat}_{$lon}_{$date}_{$width}x{$height}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($lat, $lon, $date, $overlayType, $width, $height) {
            $token = $this->getAccessToken();
            if (! $token) {
                return null;
            }

            // Select appropriate evalscript based on overlay type
            $evalscript = match ($overlayType) {
                'ndvi' => $this->getNDVIVisualizationScript(),
                'moisture' => $this->getMoistureVisualizationScript(),
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
