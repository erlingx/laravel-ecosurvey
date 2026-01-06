<?php

declare(strict_types=1);

use App\Services\SatelliteService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new SatelliteService;
});

test('get satellite imagery from NASA Earth API', function () {
    Http::fake([
        'api.nasa.gov/planetary/earth/imagery*' => Http::response([
            'url' => 'https://example.com/satellite-image.png',
        ], 200),
    ]);

    config(['services.nasa_earth.api_key' => 'test_key']);

    $result = $this->service->getSatelliteImagery(55.6761, 12.5683, '2024-01-01');

    expect($result)->not->toBeNull()
        ->and($result['date'])->toBe('2024-01-01')
        ->and($result['latitude'])->toBe(55.6761)
        ->and($result['longitude'])->toBe(12.5683)
        ->and($result['source'])->toBe('NASA Earth');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'planetary/earth/imagery') &&
        str_contains($request->url(), 'lat=55.6761') &&
        str_contains($request->url(), 'date=2024-01-01')
    );
});

test('get satellite imagery uses default date when not provided', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response(['url' => 'test.png'], 200),
    ]);

    config(['services.nasa_earth.api_key' => 'test_key']);

    $result = $this->service->getSatelliteImagery(55.6761, 12.5683);

    expect($result)->not->toBeNull()
        ->and($result['date'])->toBe(now()->subDays(7)->format('Y-m-d'));
});

test('get satellite imagery caches responses', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response(['url' => 'test.png'], 200),
    ]);

    config(['services.nasa_earth.api_key' => 'test_key']);

    $result1 = $this->service->getSatelliteImagery(55.6761, 12.5683, '2024-01-01');
    $result2 = $this->service->getSatelliteImagery(55.6761, 12.5683, '2024-01-01');

    expect($result1)->toBe($result2);
    Http::assertSentCount(1);
});

test('get satellite imagery handles API errors', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response([], 500),
    ]);

    config(['services.nasa_earth.api_key' => 'test_key']);

    $result = $this->service->getSatelliteImagery(55.6761, 12.5683);

    expect($result)->toBeNull();
});

test('get NDVI data from NASA assets API', function () {
    Http::fake([
        'api.nasa.gov/planetary/earth/assets*' => Http::response([
            'id' => 'LC08_L1TP_196023_20240101_20240101_01_T1',
            'date' => '2024-01-01',
            'cloud_score' => 0.15,
            'url' => 'https://example.com/landsat-scene',
        ], 200),
    ]);

    config(['services.nasa_earth.api_key' => 'test_key']);

    $result = $this->service->getNDVIData(55.6761, 12.5683, '2024-01-01');

    expect($result)->not->toBeNull()
        ->and($result['scene_id'])->toContain('LC08')
        ->and($result['date'])->toBe('2024-01-01')
        ->and($result['cloud_score'])->toBe(0.15)
        ->and($result['source'])->toBe('NASA Landsat');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'planetary/earth/assets')
    );
});

test('get NDVI data uses default date when not provided', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response([
            'id' => 'test_scene',
            'date' => now()->subDays(7)->format('Y-m-d'),
        ], 200),
    ]);

    config(['services.nasa_earth.api_key' => 'test_key']);

    $result = $this->service->getNDVIData(55.6761, 12.5683);

    expect($result)->not->toBeNull()
        ->and($result['date'])->toBe(now()->subDays(7)->format('Y-m-d'));
});

test('get NDVI data caches responses', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response([
            'id' => 'test_scene',
            'date' => '2024-01-01',
        ], 200),
    ]);

    config(['services.nasa_earth.api_key' => 'test_key']);

    $result1 = $this->service->getNDVIData(55.6761, 12.5683, '2024-01-01');
    $result2 = $this->service->getNDVIData(55.6761, 12.5683, '2024-01-01');

    expect($result1)->toBe($result2);
    Http::assertSentCount(1);
});

test('get NDVI data handles API errors', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response([], 404),
    ]);

    config(['services.nasa_earth.api_key' => 'test_key']);

    $result = $this->service->getNDVIData(55.6761, 12.5683);

    expect($result)->toBeNull();
});

test('get imagery for date range returns multiple results', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response(['url' => 'test.png'], 200),
    ]);

    config(['services.nasa_earth.api_key' => 'test_key']);

    $results = $this->service->getImageryForDateRange(
        55.6761,
        12.5683,
        '2024-01-01',
        '2024-01-21',
        7
    );

    expect($results)->toBeArray()
        ->and(count($results))->toBeGreaterThan(0);

    // Should make multiple API calls for different dates
    Http::assertSentCount(count($results));
});

test('calculate NDVI from NIR and Red bands', function () {
    // Test case: NIR=0.8, Red=0.2 => NDVI=(0.8-0.2)/(0.8+0.2)=0.6
    $ndvi1 = $this->service->calculateNDVI(0.8, 0.2);
    expect($ndvi1)->toBe(0.6);

    // Test case: NIR=0.5, Red=0.5 => NDVI=0
    $ndvi2 = $this->service->calculateNDVI(0.5, 0.5);
    expect($ndvi2)->toBe(0.0);

    // Test case: NIR=0.2, Red=0.8 => NDVI=-0.6
    $ndvi3 = $this->service->calculateNDVI(0.2, 0.8);
    expect($ndvi3)->toBe(-0.6);

    // Test edge case: Division by zero
    $ndvi4 = $this->service->calculateNDVI(0, 0);
    expect($ndvi4)->toBe(0.0);
});

test('NDVI values are clamped between -1 and 1', function () {
    // Normal range
    $ndvi1 = $this->service->calculateNDVI(1.0, 0.0);
    expect($ndvi1)->toBe(1.0);

    $ndvi2 = $this->service->calculateNDVI(0.0, 1.0);
    expect($ndvi2)->toBe(-1.0);

    // Even with extreme values, should clamp
    $ndvi3 = $this->service->calculateNDVI(10.0, 0.01);
    expect($ndvi3)->toBeLessThanOrEqual(1.0);
});

test('interpret NDVI value correctly', function () {
    expect($this->service->interpretNDVI(-0.5))->toBe('Water');
    expect($this->service->interpretNDVI(0.05))->toBe('Barren rock, sand, or snow');
    expect($this->service->interpretNDVI(0.15))->toBe('Shrub and grassland');
    expect($this->service->interpretNDVI(0.25))->toBe('Sparse vegetation');
    expect($this->service->interpretNDVI(0.45))->toBe('Moderate vegetation');
    expect($this->service->interpretNDVI(0.75))->toBe('Dense vegetation');
});

test('cache keys use coordinate rounding', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response(['url' => 'test.png'], 200),
    ]);

    config(['services.nasa_earth.api_key' => 'test_key']);

    // These coordinates should use the same cache key
    $result1 = $this->service->getSatelliteImagery(55.67611, 12.56831, '2024-01-01');
    $result2 = $this->service->getSatelliteImagery(55.67619, 12.56839, '2024-01-01');

    expect($result1)->toBe($result2);
    Http::assertSentCount(1);
});

test('cache keys include date for different time periods', function () {
    Cache::flush();

    Http::fake([
        'api.nasa.gov/*' => Http::response(['url' => 'test.png'], 200),
    ]);

    config(['services.nasa_earth.api_key' => 'test_key']);

    $result1 = $this->service->getSatelliteImagery(55.6761, 12.5683, '2024-01-01');
    $result2 = $this->service->getSatelliteImagery(55.6761, 12.5683, '2024-02-01');

    // Different dates should result in different API calls
    Http::assertSentCount(2);

    expect($result1)->not->toBe($result2);
});

test('uses DEMO_KEY as default NASA API key', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response(['url' => 'test.png'], 200),
    ]);

    // Set API key to DEMO_KEY
    config(['services.nasa_earth.api_key' => 'DEMO_KEY']);

    // Create new service instance to pick up config
    $service = new SatelliteService;

    $result = $service->getSatelliteImagery(55.6761, 12.5683);

    expect($result)->not->toBeNull();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api_key=DEMO_KEY')
    );
});
