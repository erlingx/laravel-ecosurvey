<?php

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\EnvironmentalMetric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake(); // Prevent automatic satellite enrichment which can hang tests
    $this->user = User::factory()->create();
    $this->campaign = Campaign::factory()->create(['status' => 'active', 'user_id' => $this->user->id]);
    $this->metric = EnvironmentalMetric::factory()->create(['is_active' => true]);
});

test('survey map page is accessible for authenticated users', function () {
    $this->actingAs($this->user)
        ->get(route('maps.survey'))
        ->assertOk()
        ->assertSeeLivewire('maps.survey-map-viewer')
        ->assertSee('Survey Map');
});

test('survey map page requires authentication', function () {
    $this->get(route('maps.survey'))
        ->assertRedirect(route('login'));
});

test('map displays all campaigns in filter dropdown', function () {
    $campaign2 = Campaign::factory()->create(['status' => 'active', 'user_id' => $this->user->id]);
    $campaign3 = Campaign::factory()->create(['status' => 'completed', 'user_id' => $this->user->id]);

    Livewire::actingAs($this->user)
        ->test('maps.survey-map-viewer')
        ->assertSee($this->campaign->name)
        ->assertSee($campaign2->name)
        ->assertDontSee($campaign3->name); // Completed campaigns should not show
});

test('map displays all metrics in filter dropdown', function () {
    $metric2 = EnvironmentalMetric::factory()->create(['is_active' => true, 'name' => 'Test Active Metric']);
    $metric3 = EnvironmentalMetric::factory()->create(['is_active' => false, 'name' => 'Test Inactive Metric']);

    Livewire::actingAs($this->user)
        ->test('maps.survey-map-viewer')
        ->assertSee($this->metric->name)
        ->assertSee('Test Active Metric')
        ->assertDontSee('Test Inactive Metric'); // Inactive metrics should not show
});

test('map data includes all data points by default', function () {
    // Create multiple data points
    DataPoint::create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 42.5,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5683, 55.6761), 4326)'),
        'notes' => 'Test reading 1',
        'collected_at' => now(),
    ]);

    DataPoint::create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 35.0,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5700, 55.6800), 4326)'),
        'notes' => 'Test reading 2',
        'collected_at' => now(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test('maps.survey-map-viewer');

    $dataPoints = $component->get('dataPoints');

    expect($dataPoints)
        ->toHaveKey('type', 'FeatureCollection')
        ->toHaveKey('features')
        ->and($dataPoints['features'])->toHaveCount(2);
});

test('map filters data by campaign', function () {
    $campaign2 = Campaign::factory()->create(['status' => 'active', 'user_id' => $this->user->id]);

    // Create data points for different campaigns
    DataPoint::create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 42.5,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5683, 55.6761), 4326)'),
        'collected_at' => now(),
    ]);

    DataPoint::create([
        'campaign_id' => $campaign2->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 35.0,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5700, 55.6800), 4326)'),
        'collected_at' => now(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test('maps.survey-map-viewer')
        ->set('campaignId', $this->campaign->id);

    $dataPoints = $component->get('dataPoints');

    expect($dataPoints['features'])->toHaveCount(1)
        ->and($dataPoints['features'][0]['properties']['campaign'])->toBe($this->campaign->name);
});

test('map filters data by metric', function () {
    $metric2 = EnvironmentalMetric::factory()->create(['is_active' => true]);

    // Create data points for different metrics
    DataPoint::create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 42.5,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5683, 55.6761), 4326)'),
        'collected_at' => now(),
    ]);

    DataPoint::create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $metric2->id,
        'user_id' => $this->user->id,
        'value' => 35.0,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5700, 55.6800), 4326)'),
        'collected_at' => now(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test('maps.survey-map-viewer')
        ->set('metricId', $this->metric->id);

    $dataPoints = $component->get('dataPoints');

    expect($dataPoints['features'])->toHaveCount(1)
        ->and($dataPoints['features'][0]['properties']['metric'])->toBe($this->metric->name);
});

test('map filters data by both campaign and metric', function () {
    $campaign2 = Campaign::factory()->create(['status' => 'active', 'user_id' => $this->user->id]);
    $metric2 = EnvironmentalMetric::factory()->create(['is_active' => true]);

    // Create data points with different combinations
    DataPoint::create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 42.5,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5683, 55.6761), 4326)'),
        'collected_at' => now(),
    ]);

    DataPoint::create([
        'campaign_id' => $campaign2->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 35.0,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5700, 55.6800), 4326)'),
        'collected_at' => now(),
    ]);

    DataPoint::create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $metric2->id,
        'user_id' => $this->user->id,
        'value' => 28.0,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5800, 55.6900), 4326)'),
        'collected_at' => now(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test('maps.survey-map-viewer')
        ->set('campaignId', $this->campaign->id)
        ->set('metricId', $this->metric->id);

    $dataPoints = $component->get('dataPoints');

    expect($dataPoints['features'])->toHaveCount(1)
        ->and($dataPoints['features'][0]['properties']['value'])->toBe('42.50'); // Decimal cast adds trailing zero
});

test('map geojson includes all required properties', function () {
    DataPoint::create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 42.5,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5683, 55.6761), 4326)'),
        'accuracy' => 10.5,
        'notes' => 'Test observation',
        'collected_at' => now(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test('maps.survey-map-viewer');

    $dataPoints = $component->get('dataPoints');
    $feature = $dataPoints['features'][0];

    expect($feature)
        ->toHaveKey('type', 'Feature')
        ->toHaveKey('geometry')
        ->toHaveKey('properties')
        ->and($feature['geometry'])
        ->toHaveKey('type', 'Point')
        ->toHaveKey('coordinates')
        ->and($feature['properties'])
        ->toHaveKeys(['id', 'value', 'metric', 'unit', 'campaign', 'user', 'accuracy', 'notes', 'collected_at']);
});

test('map bounding box is calculated correctly', function () {
    // Create data points in different locations
    DataPoint::create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 42.5,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5683, 55.6761), 4326)'), // Southwest
        'collected_at' => now(),
    ]);

    DataPoint::create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 35.0,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.6500, 55.7000), 4326)'), // Northeast
        'collected_at' => now(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test('maps.survey-map-viewer');

    $bounds = $component->get('boundingBox');

    expect($bounds)->toHaveKeys(['southwest', 'northeast'])
        ->and($bounds['southwest'])->toHaveCount(2)
        ->and($bounds['northeast'])->toHaveCount(2)
        ->and($bounds['southwest'][0])->toBeLessThan($bounds['northeast'][0]) // Lat
        ->and($bounds['southwest'][1])->toBeLessThan($bounds['northeast'][1]); // Lon
});

test('map handles empty data gracefully', function () {
    $component = Livewire::actingAs($this->user)
        ->test('maps.survey-map-viewer');

    $dataPoints = $component->get('dataPoints');
    $bounds = $component->get('boundingBox');

    expect($dataPoints['features'])->toHaveCount(0)
        ->and($bounds)->toBeNull();
});

test('map shows point count badge', function () {
    DataPoint::factory()->count(5)->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('maps.survey'))
        ->assertSee('5 points');
});

test('map geojson coordinates are in correct order', function () {
    // GeoJSON uses [longitude, latitude] order
    DataPoint::create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 42.5,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5683, 55.6761), 4326)'), // lon, lat
        'collected_at' => now(),
    ]);

    $component = Livewire::actingAs($this->user)
        ->test('maps.survey-map-viewer');

    $dataPoints = $component->get('dataPoints');
    $coords = $dataPoints['features'][0]['geometry']['coordinates'];

    expect($coords[0])->toBeGreaterThan(10) // Longitude for Copenhagen
        ->and($coords[1])->toBeGreaterThan(50) // Latitude for Copenhagen
        ->and($coords[0])->toBeLessThan(15)
        ->and($coords[1])->toBeLessThan(60);
});
