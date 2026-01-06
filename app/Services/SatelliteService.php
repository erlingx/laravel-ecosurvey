<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SatelliteService
{
    private string $nasaApiKey;

    private string $nasaBaseUrl;

    private int $cacheTtl;

    private bool $useMock;

    public function __construct()
    {
        $this->nasaApiKey = config('services.nasa_earth.api_key', 'DEMO_KEY');
        $this->nasaBaseUrl = config('services.nasa_earth.base_url');
        $this->cacheTtl = config('services.nasa_earth.cache_ttl', 3600);
        $this->useMock = config('services.nasa_earth.use_mock', false);
    }

    /**
     * Get satellite imagery for given coordinates and date
     */
    public function getSatelliteImagery(
        float $latitude,
        float $longitude,
        ?string $date = null,
        float $dim = 0.025
    ): ?array {
        $date = $date ?? now()->subDays(7)->format('Y-m-d'); // Default to 7 days ago

        $cacheKey = $this->getCacheKey('imagery', $latitude, $longitude, $date);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($latitude, $longitude, $date, $dim) {
            // Skip API call if mock mode is enabled
            if ($this->useMock) {
                Log::info('NASA_USE_MOCK enabled - using fallback imagery data');

                return $this->getMockImageryData($latitude, $longitude, $date, $dim);
            }

            try {
                $response = Http::timeout(120) // NASA API is VERY slow (can take 60+ seconds)
                    ->get("{$this->nasaBaseUrl}/planetary/earth/imagery", [
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'date' => $date,
                        'dim' => $dim,
                        'api_key' => $this->nasaApiKey,
                    ]);

                if ($response->successful()) {
                    // NASA Earth Imagery API returns PNG image directly
                    // We construct a data URL for the image
                    $imageData = base64_encode($response->body());

                    return [
                        'url' => "data:image/png;base64,{$imageData}",
                        'date' => $date,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'dim' => $dim,
                        'source' => 'NASA Earth',
                    ];
                }

                Log::warning('NASA Earth API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

            } catch (\Exception $e) {
                Log::error('NASA Earth API error', [
                    'message' => $e->getMessage(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);
            }

            // Fallback: Return mock data for local development
            Log::info('Using fallback imagery data for local development');

            return $this->getMockImageryData($latitude, $longitude, $date, $dim);
        });
    }

    /**
     * Mock imagery data for local development when API is unreachable
     */
    private function getMockImageryData(float $latitude, float $longitude, string $date, float $dim): array
    {
        return [
            'url' => 'https://tile.openstreetmap.org/13/4396/2691.png', // Example satellite-like tile
            'date' => $date,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'dim' => $dim,
            'source' => 'Mock Data (NASA API unavailable)',
            'mock' => true,
        ];
    }

    /**
     * Get NDVI (Normalized Difference Vegetation Index) data
     */
    public function getNDVIData(
        float $latitude,
        float $longitude,
        ?string $date = null
    ): ?array {
        $date = $date ?? now()->subDays(7)->format('Y-m-d');

        $cacheKey = $this->getCacheKey('ndvi', $latitude, $longitude, $date);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($latitude, $longitude, $date) {
            // Skip API call if mock mode is enabled
            if ($this->useMock) {
                Log::info('NASA_USE_MOCK enabled - using fallback NDVI data');

                return $this->getMockNDVIData($latitude, $longitude, $date);
            }

            try {
                // NASA Earth Assets API for NDVI calculation
                $response = Http::timeout(120) // NASA API is VERY slow (can take 60+ seconds)
                    ->get("{$this->nasaBaseUrl}/planetary/earth/assets", [
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'date' => $date,
                        'api_key' => $this->nasaApiKey,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    // Parse Landsat scene data if available
                    if (isset($data['id'])) {
                        return [
                            'scene_id' => $data['id'],
                            'date' => $data['date'],
                            'cloud_score' => $data['cloud_score'] ?? null,
                            'url' => $data['url'] ?? null,
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                            // NDVI calculation would require accessing actual band data
                            // For now, return scene metadata
                            'source' => 'NASA Landsat',
                        ];
                    }
                }

                Log::warning('NASA NDVI API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

            } catch (\Exception $e) {
                Log::error('NASA NDVI API error', [
                    'message' => $e->getMessage(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);
            }

            // Fallback: Return mock data for local development
            Log::info('Using fallback NDVI data for local development');

            return $this->getMockNDVIData($latitude, $longitude, $date);
        });
    }

    /**
     * Mock NDVI data for local development when API is unreachable
     */
    private function getMockNDVIData(float $latitude, float $longitude, string $date): array
    {
        return [
            'scene_id' => 'LC8_MOCK_'.date('Ymd', strtotime($date)),
            'date' => $date,
            'cloud_score' => 15, // Low cloud coverage
            'url' => null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'source' => 'Mock Data (NASA API unavailable)',
            'mock' => true,
            'ndvi_value' => 0.45, // Moderate vegetation
            'interpretation' => 'Moderate vegetation',
        ];
    }

    /**
     * Get imagery for a date range (batch processing)
     */
    public function getImageryForDateRange(
        float $latitude,
        float $longitude,
        string $startDate,
        string $endDate,
        int $intervalDays = 7
    ): array {
        $results = [];
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);

        while ($start->lte($end)) {
            $imagery = $this->getSatelliteImagery(
                $latitude,
                $longitude,
                $start->format('Y-m-d')
            );

            if ($imagery) {
                $results[] = $imagery;
            }

            $start->addDays($intervalDays);
        }

        return $results;
    }

    /**
     * Calculate NDVI from NIR and Red band values
     * NDVI = (NIR - Red) / (NIR + Red)
     */
    public function calculateNDVI(float $nir, float $red): float
    {
        if (($nir + $red) == 0) {
            return 0.0;
        }

        $ndvi = ($nir - $red) / ($nir + $red);

        // NDVI ranges from -1 to 1
        return round(max(-1, min(1, $ndvi)), 4);
    }

    /**
     * Interpret NDVI value
     */
    public function interpretNDVI(float $ndvi): string
    {
        if ($ndvi < 0) {
            return 'Water';
        } elseif ($ndvi < 0.1) {
            return 'Barren rock, sand, or snow';
        } elseif ($ndvi < 0.2) {
            return 'Shrub and grassland';
        } elseif ($ndvi < 0.3) {
            return 'Sparse vegetation';
        } elseif ($ndvi < 0.6) {
            return 'Moderate vegetation';
        } else {
            return 'Dense vegetation';
        }
    }

    /**
     * Generate cache key for satellite data
     */
    private function getCacheKey(string $type, float $latitude, float $longitude, ?string $date = null): string
    {
        // Round to 3 decimal places (~110m precision)
        $roundedLat = round($latitude, 3);
        $roundedLon = round($longitude, 3);

        $dateKey = $date ?? 'current';

        return "satellite:{$type}:{$roundedLat}:{$roundedLon}:{$dateKey}";
    }
}
