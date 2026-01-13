<?php

declare(strict_types=1);

use App\Services\EnvironmentalDataService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new EnvironmentalDataService;
});

test('get current AQI from OpenWeatherMap API', function () {
    Http::fake([
        'api.openweathermap.org/*' => Http::response([
            'list' => [
                [
                    'main' => ['aqi' => 3],
                    'components' => [
                        'pm2_5' => 15.5,
                        'pm10' => 20.0,
                        'no2' => 10.5,
                    ],
                    'dt' => 1609459200,
                ],
            ],
        ], 200),
    ]);

    config(['services.openweathermap.api_key' => 'test_key']);

    $result = $this->service->getCurrentAQI(55.6761, 12.5683);

    expect($result)->not->toBeNull()
        ->and($result['aqi'])->toBe(3)
        ->and($result['source'])->toBe('OpenWeatherMap')
        ->and($result['components'])->toHaveKey('pm2_5');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'air_pollution') &&
        str_contains($request->url(), 'lat=55.6761')
    );
});

test('get current AQI returns null when API key missing', function () {
    Http::preventStrayRequests();
    config(['services.openweathermap.api_key' => '']);

    $result = $this->service->getCurrentAQI(55.6761, 12.5683);

    expect($result)->toBeNull();
});

test('get current AQI caches responses', function () {
    Http::fake([
        'api.openweathermap.org/*' => Http::response([
            'list' => [
                [
                    'main' => ['aqi' => 3],
                    'components' => ['pm2_5' => 15.5],
                    'dt' => 1609459200,
                ],
            ],
        ], 200),
    ]);

    config(['services.openweathermap.api_key' => 'test_key']);

    // First call - should hit API
    $result1 = $this->service->getCurrentAQI(55.6761, 12.5683);

    // Second call - should use cache
    $result2 = $this->service->getCurrentAQI(55.6761, 12.5683);

    expect($result1)->toBe($result2);

    // API should only be called once
    Http::assertSentCount(1);
});

test('get current AQI handles API errors gracefully', function () {
    Http::fake([
        'api.openweathermap.org/*' => Http::response([], 500),
    ]);

    config(['services.openweathermap.api_key' => 'test_key']);

    $result = $this->service->getCurrentAQI(55.6761, 12.5683);

    expect($result)->toBeNull();
});

test('find nearest WAQI station', function () {
    Http::fake([
        'api.waqi.info/*' => Http::response([
            'status' => 'ok',
            'data' => [
                'aqi' => 45,
                'city' => [
                    'name' => 'Copenhagen',
                    'geo' => [55.6761, 12.5683],
                ],
                'iaqi' => [
                    'pm25' => ['v' => 15.5],
                    'pm10' => ['v' => 20.0],
                ],
                'time' => ['v' => 1609459200],
            ],
        ], 200),
    ]);

    config(['services.waqi.api_key' => 'test_key']);

    $result = $this->service->findNearestStation(55.6761, 12.5683);

    expect($result)->not->toBeNull()
        ->and($result['station_name'])->toBe('Copenhagen')
        ->and($result['aqi'])->toBe(45)
        ->and($result['source'])->toBe('WAQI')
        ->and($result['latitude'])->toBe(55.6761)
        ->and($result['longitude'])->toBe(12.5683);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'waqi.info') &&
        str_contains($request->url(), 'geo:55.6761;12.5683')
    );
});

test('find nearest station returns null when API key missing', function () {
    Http::preventStrayRequests();
    config(['services.waqi.api_key' => '']);

    $result = $this->service->findNearestStation(55.6761, 12.5683);

    expect($result)->toBeNull();
});

test('find nearest station caches responses', function () {
    Http::fake([
        'api.waqi.info/*' => Http::response([
            'status' => 'ok',
            'data' => [
                'aqi' => 45,
                'city' => ['name' => 'Copenhagen', 'geo' => [55.6761, 12.5683]],
            ],
        ], 200),
    ]);

    config(['services.waqi.api_key' => 'test_key']);

    $result1 = $this->service->findNearestStation(55.6761, 12.5683);
    $result2 = $this->service->findNearestStation(55.6761, 12.5683);

    expect($result1)->toBe($result2);
    Http::assertSentCount(1);
});

test('compare with official data calculates variance', function () {
    Http::fake([
        'api.waqi.info/*' => Http::response([
            'status' => 'ok',
            'data' => [
                'aqi' => 50,
                'city' => [
                    'name' => 'Copenhagen Central',
                    'geo' => [55.6761, 12.5683],
                ],
            ],
        ], 200),
    ]);

    config(['services.waqi.api_key' => 'test_key']);

    $result = $this->service->compareWithOfficial(60, 55.6761, 12.5683);

    expect($result)->not->toBeNull()
        ->and($result['user_value'])->toBe(60.0)
        ->and($result['official_value'])->toBe(50.0)
        ->and($result['variance_percentage'])->toBe(20.0)
        ->and($result['station_name'])->toBe('Copenhagen Central')
        ->and($result['distance_meters'])->toBeGreaterThanOrEqual(0);
});

test('compare with official returns null when no station found', function () {
    Http::fake([
        'api.waqi.info/*' => Http::response(['status' => 'error'], 404),
    ]);

    config(['services.waqi.api_key' => 'test_key']);

    $result = $this->service->compareWithOfficial(60, 55.6761, 12.5683);

    expect($result)->toBeNull();
});

test('calculate variance percentage correctly', function () {
    $variance1 = $this->service->calculateVariance(60, 50);
    $variance2 = $this->service->calculateVariance(40, 50);
    $variance3 = $this->service->calculateVariance(50, 50);
    $variance4 = $this->service->calculateVariance(50, 0);

    expect($variance1)->toBe(20.0)
        ->and($variance2)->toBe(-20.0)
        ->and($variance3)->toBe(0.0)
        ->and($variance4)->toBe(0.0);
});

test('cache keys use coordinate rounding and time buckets', function () {
    Http::fake([
        'api.openweathermap.org/*' => Http::response([
            'list' => [['main' => ['aqi' => 3], 'components' => [], 'dt' => time()]],
        ], 200),
    ]);

    config(['services.openweathermap.api_key' => 'test_key']);

    // These coordinates should use the same cache key (rounded to 3 decimals)
    $result1 = $this->service->getCurrentAQI(55.67611, 12.56831);
    $result2 = $this->service->getCurrentAQI(55.67619, 12.56839);

    expect($result1)->toBe($result2);

    // Only one API call due to cache
    Http::assertSentCount(1);
});

test('cache keys include time buckets for TTL rotation', function () {
    Cache::flush();

    Http::fake([
        'api.openweathermap.org/*' => Http::response([
            'list' => [['main' => ['aqi' => 3], 'components' => [], 'dt' => time()]],
        ], 200),
    ]);

    config(['services.openweathermap.api_key' => 'test_key']);

    $latitude = 55.6761;
    $longitude = 12.5683;

    // Get initial result
    $result = $this->service->getCurrentAQI($latitude, $longitude);

    expect($result)->not->toBeNull();

    // Verify cache was used
    $cacheHit = Cache::has('env_data:aqi:55.676:12.568:'.floor(time() / 3600));
    expect($cacheHit)->toBeTrue();
});
