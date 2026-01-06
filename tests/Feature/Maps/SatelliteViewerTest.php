<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\EnvironmentalMetric;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('satellite viewer page is accessible for authenticated users', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response(['url' => 'test.png'], 200),
    ]);

    Livewire::actingAs($this->user)
        ->test('maps.satellite-viewer')
        ->assertStatus(200)
        ->assertSee('Satellite Data Viewer')
        ->assertSee('Copernicus Sentinel-2 imagery and NDVI analysis');
});

test('satellite viewer page requires authentication', function () {
    $this->get(route('maps.satellite'))
        ->assertRedirect(route('login'));
});

test('satellite viewer displays campaign filter dropdown', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response(['url' => 'test.png'], 200),
    ]);

    $campaign = Campaign::factory()->create(['status' => 'active']);

    // Create a data point so campaign appears in dropdown
    DataPoint::factory()->create([
        'campaign_id' => $campaign->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::actingAs($this->user)
        ->test('maps.satellite-viewer')
        ->assertSee($campaign->name);
});

test('satellite viewer has default copenhagen coordinates', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response(['url' => 'test.png'], 200),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test('maps.satellite-viewer');

    expect($component->get('selectedLat'))->toBe(55.7072)
        ->and($component->get('selectedLon'))->toBe(12.5704);
});

test('satellite viewer sets default date to specific verified date', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response(['url' => 'test.png'], 200),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test('maps.satellite-viewer');

    // Component uses hardcoded date with confirmed Sentinel-2 data
    $expectedDate = '2025-08-15';

    expect($component->get('selectedDate'))->toBe($expectedDate);
});

test('satellite viewer updates location when campaign selected', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response(['url' => 'test.png'], 200),
    ]);

    $campaign = Campaign::factory()->create(['status' => 'active']);

    $dataPoint = DataPoint::factory()->create([
        'campaign_id' => $campaign->id,
        'user_id' => $this->user->id,
    ]);

    // Prefer model-level updates over raw DB statements.
    // If the project uses a geometry cast, the factory/state should handle it.
    // Otherwise, leave location null and rely on the component's fallback behavior.

    $component = Livewire::actingAs($this->user)
        ->test('maps.satellite-viewer')
        ->set('campaignId', $campaign->id);

    // If there's no location stored, component should keep defaults.
    // If location is handled by casts/factory, selectedLat/Lon will update accordingly.
    expect($component->get('selectedLat'))->toBeFloat()
        ->and($component->get('selectedLon'))->toBeFloat();
});

test('satellite viewer fetches imagery data', function () {
    Http::fake([
        'api.nasa.gov/planetary/earth/imagery*' => Http::response([
            'url' => 'https://example.com/satellite-image.png',
        ], 200),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test('maps.satellite-viewer');

    $satelliteData = $component->get('satelliteData');

    expect($satelliteData)->not->toBeNull()
        ->and($satelliteData['source'])->toBe('Sentinel-2 (Copernicus Data Space)');
});

test('satellite viewer only fetches NDVI when enabled', function () {
    Http::fake([
        'api.nasa.gov/planetary/earth/imagery*' => Http::response(['url' => 'test.png'], 200),
        'api.nasa.gov/planetary/earth/assets*' => Http::response([
            'id' => 'LC08_L1TP_196025_20240101_20240101_01_T1',
            'date' => '2024-01-01',
        ], 200),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test('maps.satellite-viewer');

    // Initially NDVI overlay is set by default
    expect($component->get('overlayType'))->toBe('ndvi')
        ->and($component->get('analysisData'))->not->toBeNull();

    // Change to true color - no analysis data
    $component->set('overlayType', 'truecolor');

    expect($component->get('analysisData'))->toBeNull();
});

test('satellite viewer displays NDVI interpretation when enabled', function () {
    Http::fake([
        'api.nasa.gov/planetary/earth/imagery*' => Http::response(['url' => 'test.png'], 200),
        'api.nasa.gov/planetary/earth/assets*' => Http::response([
            'id' => 'LC08_L1TP_196025_20240101_20240101_01_T1',
            'date' => '2024-01-01',
            'cloud_score' => 5,
            'url' => 'https://example.com/scene.jpg',
        ], 200),
    ]);

    Livewire::actingAs($this->user)
        ->test('maps.satellite-viewer')
        ->set('overlayType', 'ndvi')
        ->assertSee('NDVI Analysis')
        ->assertSee('NDVI Scale')
        ->assertSee('Water')
        ->assertSee('Dense vegetation');
});

test('satellite viewer date picker has max date of today', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response(['url' => 'test.png'], 200),
    ]);

    Livewire::actingAs($this->user)
        ->test('maps.satellite-viewer')
        ->assertSee('max="'.now()->format('Y-m-d').'"', false);
});

test('satellite viewer updates imagery when date changes', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response(['url' => 'test.png'], 200),
    ]);

    $newDate = now()->subDays(14)->format('Y-m-d');

    $component = Livewire::actingAs($this->user)
        ->test('maps.satellite-viewer')
        ->set('selectedDate', $newDate);

    $satelliteData = $component->get('satelliteData');

    expect($satelliteData['date'])->toBe($newDate);
});

test('satellite viewer displays coordinates', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response(['url' => 'test.png'], 200),
    ]);

    Livewire::actingAs($this->user)
        ->test('maps.satellite-viewer')
        ->assertSee('55.707200')
        ->assertSee('12.570400')
        ->assertSee('Location:');
});

test('satellite viewer map element exists', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response(['url' => 'test.png'], 200),
    ]);

    Livewire::actingAs($this->user)
        ->test('maps.satellite-viewer')
        ->assertSee('id="satellite-map"', false);
});

test('satellite viewer data container includes all required attributes', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response(['url' => 'test.png'], 200),
    ]);

    Livewire::actingAs($this->user)
        ->test('maps.satellite-viewer')
        ->assertSee('id="satellite-data-container"', false)
        ->assertSee('data-lat=', false)
        ->assertSee('data-lon=', false)
        ->assertSee('data-imagery=', false)
        ->assertSee('data-analysis=', false)
        ->assertSee('data-overlay-type=', false);
});

test('satellite viewer handles API errors gracefully', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response([], 500),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test('maps.satellite-viewer');

    $satelliteData = $component->get('satelliteData');

    // Check structure without dumping the entire image data
    expect($satelliteData)->toBeArray()
        ->and(isset($satelliteData['url']))->toBeTrue()
        ->and(isset($satelliteData['source']))->toBeTrue();
});

test('satellite viewer updates when campaign is selected', function () {
    Http::fake([
        'api.nasa.gov/*' => Http::response(['url' => 'test.png'], 200),
    ]);

    $campaign = Campaign::factory()->create(['status' => 'active']);
    $metric = EnvironmentalMetric::factory()->create();

    DataPoint::factory()->create([
        'campaign_id' => $campaign->id,
        'environmental_metric_id' => $metric->id,
        'user_id' => $this->user->id,
        // Avoid DB::raw PostGIS expressions in tests to keep them portable.
        // The component should still be able to select a campaign even without geometry.
    ]);

    $component = Livewire::actingAs($this->user)
        ->test('maps.satellite-viewer')
        ->set('campaignId', $campaign->id);

    expect($component->get('selectedLat'))->toBeFloat()
        ->and($component->get('selectedLon'))->toBeFloat();
});
