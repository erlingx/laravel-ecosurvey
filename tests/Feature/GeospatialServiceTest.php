<?php

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\EnvironmentalMetric;
use App\Models\User;
use App\Services\GeospatialService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new GeospatialService;
    $this->user = User::factory()->create();
    $this->campaign = Campaign::factory()->create(['status' => 'active', 'user_id' => $this->user->id]);
    $this->metric = EnvironmentalMetric::factory()->create(['is_active' => true]);
});

test('get data points as GeoJSON', function () {
    // Create test data points
    $dataPoint1 = DataPoint::create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 42.5,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5683, 55.6761), 4326)'),
        'collected_at' => now(),
    ]);

    $dataPoint2 = DataPoint::create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 35.0,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5700, 55.6800), 4326)'),
        'collected_at' => now(),
    ]);

    $geojson = $this->service->getDataPointsAsGeoJSON();

    expect($geojson)
        ->toHaveKey('type', 'FeatureCollection')
        ->toHaveKey('features')
        ->and($geojson['features'])->toHaveCount(2)
        ->and($geojson['features'][0])
        ->toHaveKey('type', 'Feature')
        ->toHaveKey('geometry')
        ->toHaveKey('properties');
});

test('filter data points by campaign', function () {
    $campaign2 = Campaign::factory()->create(['status' => 'active', 'user_id' => $this->user->id]);

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

    $geojson = $this->service->getDataPointsAsGeoJSON($this->campaign->id);

    expect($geojson['features'])->toHaveCount(1)
        ->and($geojson['features'][0]['properties']['campaign'])->toBe($this->campaign->name);
});

test('find points within radius', function () {
    // Create point at Copenhagen center
    DataPoint::create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 42.5,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5683, 55.6761), 4326)'),
        'collected_at' => now(),
    ]);

    // Create point 5km away
    DataPoint::create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 35.0,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.6500, 55.7000), 4326)'),
        'collected_at' => now(),
    ]);

    // Find points within 6km of Copenhagen center
    $points = $this->service->findPointsInRadius(55.6761, 12.5683, 6000);

    expect($points)->toHaveCount(2);

    // Find points within 2km - should only get the first one
    $points = $this->service->findPointsInRadius(55.6761, 12.5683, 2000);

    expect($points)->toHaveCount(1);
});

test('calculate distance between two points', function () {
    // Distance from Copenhagen center to Amalienborg (approx 1.8km)
    $distance = $this->service->calculateDistance(
        55.6761, 12.5683, // Copenhagen center
        55.6840, 12.5929  // Amalienborg
    );

    // Distance should be approximately 1800 meters
    expect($distance)->toBeGreaterThan(1500)
        ->and($distance)->toBeLessThan(2000);
});

test('get bounding box for data points', function () {
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
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 35.0,
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.6500, 55.7000), 4326)'),
        'collected_at' => now(),
    ]);

    $bbox = $this->service->getBoundingBox();

    expect($bbox)->toHaveKeys(['southwest', 'northeast'])
        ->and($bbox['southwest'])->toHaveCount(2)
        ->and($bbox['northeast'])->toHaveCount(2);
});

test('create buffer zone around point', function () {
    $geojson = $this->service->createBufferZone(55.6761, 12.5683, 1000);

    expect($geojson)->toBeString()
        ->and(json_decode($geojson))->toBeObject();
});
