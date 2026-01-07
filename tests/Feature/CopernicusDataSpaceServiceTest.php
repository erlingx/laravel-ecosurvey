<?php

declare(strict_types=1);

use App\Services\CopernicusDataSpaceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    $this->service = new CopernicusDataSpaceService;
});

test('authenticates with Copernicus Data Space OAuth2', function () {
    Http::fake([
        'identity.dataspace.copernicus.eu/*' => Http::response([
            'access_token' => 'test_token_12345',
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ], 200),
        'sh.dataspace.copernicus.eu/*' => Http::response('fake_image_data', 200),
    ]);

    config([
        'services.copernicus_dataspace.client_id' => 'test_client_id',
        'services.copernicus_dataspace.client_secret' => 'test_client_secret',
    ]);

    $service = new CopernicusDataSpaceService;
    $result = $service->getSatelliteImagery(55.6761, 12.5683, '2024-01-01');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'identity.dataspace.copernicus.eu') &&
               $request['grant_type'] === 'client_credentials';
    });
});

test('get satellite imagery from Copernicus Data Space', function () {
    Http::fake([
        'identity.dataspace.copernicus.eu/*' => Http::response([
            'access_token' => 'test_token',
        ], 200),
        'sh.dataspace.copernicus.eu/*' => Http::response('fake_png_image_data', 200),
    ]);

    config([
        'services.copernicus_dataspace.client_id' => 'test_client',
        'services.copernicus_dataspace.client_secret' => 'test_secret',
    ]);

    $service = new CopernicusDataSpaceService;
    $result = $service->getSatelliteImagery(55.6761, 12.5683, '2024-01-01');

    expect($result)->not->toBeNull()
        ->and($result['date'])->toBe('2024-01-01')
        ->and($result['latitude'])->toBe(55.6761)
        ->and($result['longitude'])->toBe(12.5683)
        ->and($result['source'])->toBe('Sentinel-2 (Copernicus Data Space)')
        ->and($result['resolution'])->toBe('10m')
        ->and($result['provider'])->toBe('copernicus_dataspace');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'sh.dataspace.copernicus.eu') &&
        $request->hasHeader('Authorization', 'Bearer test_token')
    );
});

test('get satellite imagery uses default date when not provided', function () {
    Http::fake([
        'identity.dataspace.copernicus.eu/*' => Http::response(['access_token' => 'test_token'], 200),
        'sh.dataspace.copernicus.eu/*' => Http::response('fake_image', 200),
    ]);

    config([
        'services.copernicus_dataspace.client_id' => 'test',
        'services.copernicus_dataspace.client_secret' => 'test',
    ]);

    $service = new CopernicusDataSpaceService;
    $result = $service->getSatelliteImagery(55.6761, 12.5683);

    expect($result)->not->toBeNull()
        ->and($result['date'])->toBe(now()->subDays(7)->format('Y-m-d'));
});

test('get satellite imagery caches responses', function () {
    Http::fake([
        'identity.dataspace.copernicus.eu/*' => Http::response(['access_token' => 'test_token'], 200),
        'sh.dataspace.copernicus.eu/*' => Http::response('fake_image', 200),
    ]);

    config([
        'services.copernicus_dataspace.client_id' => 'test',
        'services.copernicus_dataspace.client_secret' => 'test',
    ]);

    $service = new CopernicusDataSpaceService;
    $result1 = $service->getSatelliteImagery(55.6761, 12.5683, '2024-01-01');
    $result2 = $service->getSatelliteImagery(55.6761, 12.5683, '2024-01-01');

    expect($result1)->toBe($result2);

    // Should only make 2 HTTP calls total: 1 for auth, 1 for imagery (not 4)
    Http::assertSentCount(2);
});

test('get satellite imagery handles API errors', function () {
    Http::fake([
        'identity.dataspace.copernicus.eu/*' => Http::response(['access_token' => 'test_token'], 200),
        'sh.dataspace.copernicus.eu/*' => Http::response([], 500),
    ]);

    config([
        'services.copernicus_dataspace.client_id' => 'test',
        'services.copernicus_dataspace.client_secret' => 'test',
    ]);

    $service = new CopernicusDataSpaceService;
    $result = $service->getSatelliteImagery(55.6761, 12.5683);

    expect($result)->toBeNull();
});

test('get satellite imagery handles OAuth failure', function () {
    Http::fake([
        'identity.dataspace.copernicus.eu/*' => Http::response([], 401),
    ]);

    config([
        'services.copernicus_dataspace.client_id' => 'invalid',
        'services.copernicus_dataspace.client_secret' => 'invalid',
    ]);

    $service = new CopernicusDataSpaceService;
    $result = $service->getSatelliteImagery(55.6761, 12.5683);

    expect($result)->toBeNull();
});

test('get NDVI data from Copernicus Data Space', function () {
    // Create a simple 2x2 grayscale PNG image for NDVI
    $img = imagecreate(2, 2);
    $gray = imagecolorallocate($img, 191, 191, 191); // ~0.5 NDVI value
    imagefill($img, 0, 0, $gray);

    ob_start();
    imagepng($img);
    $pngData = ob_get_clean();
    imagedestroy($img);

    Http::fake([
        'identity.dataspace.copernicus.eu/*' => Http::response(['access_token' => 'test_token'], 200),
        'sh.dataspace.copernicus.eu/*' => Http::response($pngData, 200),
    ]);

    config([
        'services.copernicus_dataspace.client_id' => 'test',
        'services.copernicus_dataspace.client_secret' => 'test',
    ]);

    $service = new CopernicusDataSpaceService;
    $result = $service->getNDVIData(55.6761, 12.5683, '2024-01-01');

    expect($result)->not->toBeNull()
        ->and($result['date'])->toBe('2024-01-01')
        ->and($result['latitude'])->toBe(55.6761)
        ->and($result['longitude'])->toBe(12.5683)
        ->and($result['source'])->toBe('Sentinel-2 (Copernicus Data Space)')
        ->and($result['provider'])->toBe('copernicus_dataspace')
        ->and($result['ndvi_value'])->toBeFloat()
        ->and($result['interpretation'])->toBeString();
});

test('get NDVI data uses default date when not provided', function () {
    $img = imagecreate(2, 2);
    $gray = imagecolorallocate($img, 127, 127, 127);
    imagefill($img, 0, 0, $gray);
    ob_start();
    imagepng($img);
    $pngData = ob_get_clean();
    imagedestroy($img);

    Http::fake([
        'identity.dataspace.copernicus.eu/*' => Http::response(['access_token' => 'test_token'], 200),
        'sh.dataspace.copernicus.eu/*' => Http::response($pngData, 200),
    ]);

    config([
        'services.copernicus_dataspace.client_id' => 'test',
        'services.copernicus_dataspace.client_secret' => 'test',
    ]);

    $service = new CopernicusDataSpaceService;
    $result = $service->getNDVIData(55.6761, 12.5683);

    expect($result)->not->toBeNull()
        ->and($result['date'])->toBe(now()->subDays(7)->format('Y-m-d'));
});

test('get NDVI data caches responses', function () {
    $img = imagecreate(2, 2);
    $gray = imagecolorallocate($img, 127, 127, 127);
    imagefill($img, 0, 0, $gray);
    ob_start();
    imagepng($img);
    $pngData = ob_get_clean();
    imagedestroy($img);

    Http::fake([
        'identity.dataspace.copernicus.eu/*' => Http::response(['access_token' => 'test_token'], 200),
        'sh.dataspace.copernicus.eu/*' => Http::response($pngData, 200),
    ]);

    config([
        'services.copernicus_dataspace.client_id' => 'test',
        'services.copernicus_dataspace.client_secret' => 'test',
    ]);

    $service = new CopernicusDataSpaceService;
    $result1 = $service->getNDVIData(55.6761, 12.5683, '2024-01-01');
    $result2 = $service->getNDVIData(55.6761, 12.5683, '2024-01-01');

    expect($result1)->toBe($result2);
    Http::assertSentCount(2); // 1 auth + 1 NDVI request
});

test('get NDVI data handles API errors', function () {
    Http::fake([
        'identity.dataspace.copernicus.eu/*' => Http::response(['access_token' => 'test_token'], 200),
        'sh.dataspace.copernicus.eu/*' => Http::response([], 404),
    ]);

    config([
        'services.copernicus_dataspace.client_id' => 'test',
        'services.copernicus_dataspace.client_secret' => 'test',
    ]);

    $service = new CopernicusDataSpaceService;
    $result = $service->getNDVIData(55.6761, 12.5683);

    expect($result)->toBeNull();
});

test('interpret NDVI value correctly', function () {
    Http::fake();
    $service = new CopernicusDataSpaceService;

    // Use reflection to test private interpretNDVI method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('interpretNDVI');
    $method->setAccessible(true);

    expect($method->invoke($service, -0.5))->toBe('Water');
    expect($method->invoke($service, 0.05))->toBe('Barren rock, sand, or snow');
    expect($method->invoke($service, 0.15))->toBe('Shrub and grassland');
    expect($method->invoke($service, 0.25))->toBe('Sparse vegetation');
    expect($method->invoke($service, 0.45))->toBe('Moderate vegetation');
    expect($method->invoke($service, 0.75))->toBe('Dense vegetation');
    expect($method->invoke($service, null))->toBe('No data');
});

test('cache keys include date for different time periods', function () {
    Cache::flush();

    $img = imagecreate(2, 2);
    $gray = imagecolorallocate($img, 127, 127, 127);
    imagefill($img, 0, 0, $gray);
    ob_start();
    imagepng($img);
    $pngData = ob_get_clean();
    imagedestroy($img);

    Http::fake([
        'identity.dataspace.copernicus.eu/*' => Http::response(['access_token' => 'test_token'], 200),
        'sh.dataspace.copernicus.eu/*' => Http::response($pngData, 200),
    ]);

    config([
        'services.copernicus_dataspace.client_id' => 'test',
        'services.copernicus_dataspace.client_secret' => 'test',
    ]);

    $service = new CopernicusDataSpaceService;
    $result1 = $service->getSatelliteImagery(55.6761, 12.5683, '2024-01-01');
    $result2 = $service->getSatelliteImagery(55.6761, 12.5683, '2024-02-01');

    // Different dates should result in different API calls
    Http::assertSentCount(3); // 1 auth + 2 imagery requests

    expect($result1)->not->toBe($result2);
});

test('OAuth token is cached and reused', function () {
    Http::fake([
        'identity.dataspace.copernicus.eu/*' => Http::response(['access_token' => 'cached_token'], 200),
        'sh.dataspace.copernicus.eu/*' => Http::response('fake_image', 200),
    ]);

    config([
        'services.copernicus_dataspace.client_id' => 'test',
        'services.copernicus_dataspace.client_secret' => 'test',
    ]);

    $service = new CopernicusDataSpaceService;

    // Make multiple requests
    $service->getSatelliteImagery(55.6761, 12.5683, '2024-01-01', 100, 100);
    $service->getNDVIData(55.6761, 12.5683, '2024-01-02');
    $service->getSatelliteImagery(55.6860, 12.5700, '2024-01-03', 100, 100);

    // Should only authenticate once (token is cached)
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'identity.dataspace.copernicus.eu');
    }, 1);
});

test('get overlay visualization with NDVI type', function () {
    Http::fake([
        'identity.dataspace.copernicus.eu/*' => Http::response(['access_token' => 'test_token'], 200),
        'sh.dataspace.copernicus.eu/*' => Http::response('fake_overlay_image', 200),
    ]);

    config([
        'services.copernicus_dataspace.client_id' => 'test',
        'services.copernicus_dataspace.client_secret' => 'test',
    ]);

    $service = new CopernicusDataSpaceService;
    $result = $service->getOverlayVisualization(55.6761, 12.5683, '2024-01-01', 'ndvi');

    expect($result)->not->toBeNull()
        ->and($result['overlay_type'])->toBe('ndvi')
        ->and($result['source'])->toBe('Sentinel-2 (Copernicus Data Space)')
        ->and($result['url'])->toStartWith('data:image/png;base64,');
});

test('get overlay visualization with moisture type', function () {
    Http::fake([
        'identity.dataspace.copernicus.eu/*' => Http::response(['access_token' => 'test_token'], 200),
        'sh.dataspace.copernicus.eu/*' => Http::response('fake_moisture_image', 200),
    ]);

    config([
        'services.copernicus_dataspace.client_id' => 'test',
        'services.copernicus_dataspace.client_secret' => 'test',
    ]);

    $service = new CopernicusDataSpaceService;
    $result = $service->getOverlayVisualization(55.6761, 12.5683, '2024-01-01', 'moisture');

    expect($result)->not->toBeNull()
        ->and($result['overlay_type'])->toBe('moisture');
});

test('get moisture data from Copernicus Data Space', function () {
    $img = imagecreate(2, 2);
    $gray = imagecolorallocate($img, 127, 127, 127);
    imagefill($img, 0, 0, $gray);
    ob_start();
    imagepng($img);
    $pngData = ob_get_clean();
    imagedestroy($img);

    Http::fake([
        'identity.dataspace.copernicus.eu/*' => Http::response(['access_token' => 'test_token'], 200),
        'sh.dataspace.copernicus.eu/*' => Http::response($pngData, 200),
    ]);

    config([
        'services.copernicus_dataspace.client_id' => 'test',
        'services.copernicus_dataspace.client_secret' => 'test',
    ]);

    $service = new CopernicusDataSpaceService;
    $result = $service->getMoistureData(55.6761, 12.5683, '2024-01-01');

    expect($result)->not->toBeNull()
        ->and($result['moisture_value'])->toBeFloat()
        ->and($result['interpretation'])->toBeString()
        ->and($result['source'])->toBe('Sentinel-2 (Copernicus Data Space)');
});
