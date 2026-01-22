<?php

use App\Models\User;
use App\Services\CopernicusDataSpaceService;
use App\Services\UsageTrackingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    // Fake the queue to prevent job dispatching
    Queue::fake();

    // Clear cache between tests
    Cache::flush();

    // Mock Copernicus API responses
    Http::fake([
        'identity.dataspace.copernicus.eu/*' => Http::response([
            'access_token' => 'fake-token',
            'expires_in' => 3600,
        ], 200),
        'sh.dataspace.copernicus.eu/*' => Http::response(
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='),
            200,
            ['Content-Type' => 'image/png']
        ),
    ]);
});

test('satellite overlay view is tracked in usage_meters for billing', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $service = app(CopernicusDataSpaceService::class);
    $usageService = app(UsageTrackingService::class);

    // Get initial usage
    $initialUsage = $usageService->getCurrentUsage($user);
    $initialCount = $initialUsage['satellite_analyses'];

    // View a satellite overlay (non-cached)
    $result = $service->getOverlayVisualization(55.7072, 12.5704, '2025-08-15', 'ndvi');

    expect($result)->not->toBeNull();

    // Check that usage was incremented in usage_meters
    $newUsage = $usageService->getCurrentUsage($user);
    expect($newUsage['satellite_analyses'])->toBe($initialCount + 1);

    // Verify the usage_meters table was updated
    $cycleStart = $usageService->getBillingCycleStart($user);
    $meter = DB::table('usage_meters')
        ->where('user_id', $user->id)
        ->where('resource', 'satellite_analyses')
        ->where('billing_cycle_start', $cycleStart->toDateString())
        ->first();

    expect($meter)->not->toBeNull();
    expect($meter->count)->toBe($initialCount + 1);
});

test('cached satellite overlay view is not double-counted in usage_meters', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $service = app(CopernicusDataSpaceService::class);
    $usageService = app(UsageTrackingService::class);

    // First call (not cached)
    $result1 = $service->getOverlayVisualization(55.7072, 12.5704, '2025-08-15', 'ndvi');
    expect($result1)->not->toBeNull();

    $usageAfterFirst = $usageService->getCurrentUsage($user);
    $countAfterFirst = $usageAfterFirst['satellite_analyses'];

    // Second call (should be cached)
    $result2 = $service->getOverlayVisualization(55.7072, 12.5704, '2025-08-15', 'ndvi');
    expect($result2)->not->toBeNull();

    // Usage should NOT increase (cached call shouldn't count)
    $usageAfterSecond = $usageService->getCurrentUsage($user);
    expect($usageAfterSecond['satellite_analyses'])->toBe($countAfterFirst);
});

test('satellite analysis data view is NOT tracked separately in usage_meters', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $service = app(CopernicusDataSpaceService::class);
    $usageService = app(UsageTrackingService::class);

    // Get initial usage
    $initialUsage = $usageService->getCurrentUsage($user);
    $initialCount = $initialUsage['satellite_analyses'];

    // View NDVI analysis data (non-cached)
    // This should NOT increment usage because it's for display purposes only
    // Usage is only tracked when viewing overlays or enriching data points
    $result = $service->getNDVIData(55.7072, 12.5704, '2025-08-15');

    expect($result)->not->toBeNull();

    // Check that usage was NOT incremented (analysis data is tracked via satellite_api_calls but not usage_meters)
    $newUsage = $usageService->getCurrentUsage($user);
    expect($newUsage['satellite_analyses'])->toBe($initialCount);
});

test('multiple different satellite overlay views are all tracked', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $service = app(CopernicusDataSpaceService::class);
    $usageService = app(UsageTrackingService::class);

    $initialUsage = $usageService->getCurrentUsage($user);
    $initialCount = $initialUsage['satellite_analyses'];

    // View multiple different overlay types
    // Analysis data calls (getNDVIData, getMoistureData) don't count separately
    $service->getOverlayVisualization(55.7072, 12.5704, '2025-08-15', 'ndvi');
    $service->getOverlayVisualization(55.7072, 12.5704, '2025-08-15', 'moisture');
    $service->getOverlayVisualization(55.7072, 12.5704, '2025-08-15', 'ndre');

    // All three overlay views should be counted
    $newUsage = $usageService->getCurrentUsage($user);
    expect($newUsage['satellite_analyses'])->toBe($initialCount + 3);
});

test('satellite api calls are tracked in both usage_meters and satellite_api_calls', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $service = app(CopernicusDataSpaceService::class);

    // View overlay
    $service->getOverlayVisualization(55.7072, 12.5704, '2025-08-15', 'ndvi');

    // Check satellite_api_calls table (for Filament dashboard)
    $apiCall = DB::table('satellite_api_calls')
        ->where('user_id', $user->id)
        ->where('call_type', 'overlay')
        ->where('index_type', 'ndvi')
        ->first();

    expect($apiCall)->not->toBeNull();

    // Check usage_meters table (for billing/usage page)
    $usageService = app(UsageTrackingService::class);
    $cycleStart = $usageService->getBillingCycleStart($user);
    $meter = DB::table('usage_meters')
        ->where('user_id', $user->id)
        ->where('resource', 'satellite_analyses')
        ->where('billing_cycle_start', $cycleStart->toDateString())
        ->first();

    expect($meter)->not->toBeNull();
    expect($meter->count)->toBeGreaterThan(0);
});

test('viewing overlay and analysis data together only counts once', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $service = app(CopernicusDataSpaceService::class);
    $usageService = app(UsageTrackingService::class);

    $initialUsage = $usageService->getCurrentUsage($user);
    $initialCount = $initialUsage['satellite_analyses'];

    // Simulate what happens in satellite viewer when selecting NDVI:
    // 1. Load overlay visualization
    $service->getOverlayVisualization(55.7072, 12.5704, '2025-08-15', 'ndvi');
    // 2. Load analysis data for the metrics panel
    $service->getNDVIData(55.7072, 12.5704, '2025-08-15');

    // Should only count as 1 usage (the overlay view), not 2
    $newUsage = $usageService->getCurrentUsage($user);
    expect($newUsage['satellite_analyses'])->toBe($initialCount + 1);
});
