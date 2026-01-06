<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnvironmentalDataService
{
    private string $openWeatherApiKey;

    private string $openWeatherBaseUrl;

    private string $waqiApiKey;

    private string $waqiBaseUrl;

    private int $cacheTtl;

    public function __construct()
    {
        $this->openWeatherApiKey = config('services.openweathermap.api_key', '');
        $this->openWeatherBaseUrl = config('services.openweathermap.base_url');
        $this->waqiApiKey = config('services.waqi.api_key', '');
        $this->waqiBaseUrl = config('services.waqi.base_url');
        $this->cacheTtl = config('services.openweathermap.cache_ttl', 3600);
    }

    /**
     * Get current Air Quality Index for given coordinates
     */
    public function getCurrentAQI(float $latitude, float $longitude): ?array
    {
        if (empty($this->openWeatherApiKey)) {
            Log::warning('OpenWeatherMap API key not configured');

            return null;
        }

        $cacheKey = $this->getCacheKey('aqi', $latitude, $longitude);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($latitude, $longitude) {
            try {
                $response = Http::timeout(10)
                    ->get("{$this->openWeatherBaseUrl}/air_pollution", [
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'appid' => $this->openWeatherApiKey,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['list'][0])) {
                        return [
                            'aqi' => $data['list'][0]['main']['aqi'],
                            'components' => $data['list'][0]['components'],
                            'timestamp' => $data['list'][0]['dt'],
                            'source' => 'OpenWeatherMap',
                        ];
                    }
                }

                Log::warning('OpenWeatherMap API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('OpenWeatherMap API error', [
                    'message' => $e->getMessage(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

                return null;
            }
        });
    }

    /**
     * Find nearest official WAQI monitoring station
     */
    public function findNearestStation(float $latitude, float $longitude): ?array
    {
        if (empty($this->waqiApiKey)) {
            Log::warning('WAQI API key not configured');

            return null;
        }

        $cacheKey = $this->getCacheKey('station', $latitude, $longitude);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($latitude, $longitude) {
            try {
                $response = Http::timeout(10)
                    ->get("{$this->waqiBaseUrl}/feed/geo:{$latitude};{$longitude}/", [
                        'token' => $this->waqiApiKey,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if ($data['status'] === 'ok' && isset($data['data'])) {
                        return [
                            'station_name' => $data['data']['city']['name'] ?? 'Unknown',
                            'aqi' => $data['data']['aqi'],
                            'latitude' => $data['data']['city']['geo'][0] ?? null,
                            'longitude' => $data['data']['city']['geo'][1] ?? null,
                            'pollutants' => $data['data']['iaqi'] ?? [],
                            'timestamp' => $data['data']['time']['v'] ?? null,
                            'source' => 'WAQI',
                        ];
                    }
                }

                Log::warning('WAQI API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('WAQI API error', [
                    'message' => $e->getMessage(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

                return null;
            }
        });
    }

    /**
     * Compare user reading with official station data
     */
    public function compareWithOfficial(float $userValue, float $latitude, float $longitude): ?array
    {
        $station = $this->findNearestStation($latitude, $longitude);

        if (! $station || ! isset($station['aqi'])) {
            return null;
        }

        $officialValue = (float) $station['aqi'];
        $variance = $this->calculateVariance($userValue, $officialValue);

        return [
            'user_value' => $userValue,
            'official_value' => $officialValue,
            'variance_percentage' => $variance,
            'station_name' => $station['station_name'],
            'station_latitude' => $station['latitude'],
            'station_longitude' => $station['longitude'],
            'distance_meters' => $this->calculateDistance(
                $latitude,
                $longitude,
                $station['latitude'] ?? $latitude,
                $station['longitude'] ?? $longitude
            ),
        ];
    }

    /**
     * Calculate variance percentage between user and official readings
     */
    public function calculateVariance(float $userValue, float $officialValue): float
    {
        if ($officialValue == 0) {
            return 0.0;
        }

        return round((($userValue - $officialValue) / $officialValue) * 100, 2);
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Generate cache key for API responses
     */
    private function getCacheKey(string $type, float $latitude, float $longitude): string
    {
        // Round to 3 decimal places (~110m precision) for cache efficiency
        $roundedLat = round($latitude, 3);
        $roundedLon = round($longitude, 3);

        // Create time bucket (1 hour intervals)
        $timeBucket = floor(time() / $this->cacheTtl);

        return "env_data:{$type}:{$roundedLat}:{$roundedLon}:{$timeBucket}";
    }
}
