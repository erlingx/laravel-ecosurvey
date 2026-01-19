<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\EnvironmentalMetric;
use App\Models\SurveyZone;
use App\Services\GeospatialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake(); // Prevent automatic satellite enrichment which can hang tests
    $this->service = new GeospatialService;
});

test('getZoneStatistics calculates aggregates by zone and metric', function () {
    $campaign = Campaign::factory()->create();
    $metric = EnvironmentalMetric::factory()->create(['name' => 'Soil pH', 'unit' => 'pH']);

    // Create survey zone
    $zone = SurveyZone::factory()->for($campaign)->create([
        'name' => 'Test Zone A',
    ]);

    // Update geometry using direct SQL since it's a PostGIS column
    DB::statement(
        'UPDATE survey_zones SET area = ST_GeogFromText(?) WHERE id = ?',
        ['POLYGON((12.0 55.0, 12.1 55.0, 12.1 55.1, 12.0 55.1, 12.0 55.0))', $zone->id]
    );

    // Create data points inside zone
    DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.05, 55.05), 4326)'),
        'value' => 6.5,
        'status' => 'approved',
    ]);

    DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.06, 55.06), 4326)'),
        'value' => 7.5,
        'status' => 'approved',
    ]);

    // Create point outside zone (should not be included)
    DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(13.0, 56.0), 4326)'),
        'value' => 5.0,
        'status' => 'approved',
    ]);

    $stats = $this->service->getZoneStatistics($campaign->id);

    expect($stats)->toHaveCount(1)
        ->and($stats[0])->toHaveKey('zone_name', 'Test Zone A')
        ->and($stats[0])->toHaveKey('metric_name', 'Soil pH')
        ->and($stats[0])->toHaveKey('point_count', 2)
        ->and($stats[0]['avg_value'])->toBe(7.0)
        ->and($stats[0]['min_value'])->toBe(6.5)
        ->and($stats[0]['max_value'])->toBe(7.5);
});

test('findNearestDataPoints returns K closest points', function () {
    $campaign = Campaign::factory()->create();
    $metric = EnvironmentalMetric::factory()->create();

    // Create points at known distances
    $target_lat = 55.6761;
    $target_lon = 12.5683;

    // Close point
    DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
        'location' => DB::raw("ST_SetSRID(ST_MakePoint($target_lon, $target_lat), 4326)"),
        'value' => 1.0,
        'status' => 'approved',
    ]);

    // Medium distance
    DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.58, 55.68), 4326)'),
        'value' => 2.0,
        'status' => 'approved',
    ]);

    // Far point
    DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(13.0, 56.0), 4326)'),
        'value' => 3.0,
        'status' => 'approved',
    ]);

    $nearest = $this->service->findNearestDataPoints($target_lat, $target_lon, 2);

    expect($nearest)->toHaveCount(2)
        ->and($nearest[0]['value'])->toBe(1.0) // Closest
        ->and($nearest[1]['value'])->toBe(2.0) // Second closest
        ->and($nearest[0]['distance_meters'])->toBeLessThan($nearest[1]['distance_meters']);
});

test('findNearestDataPoints respects limit parameter', function () {
    $campaign = Campaign::factory()->create();
    $metric = EnvironmentalMetric::factory()->create();

    // Create 5 points
    for ($i = 0; $i < 5; $i++) {
        DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
            'location' => DB::raw("ST_SetSRID(ST_MakePoint(12.5 + ($i * 0.01), 55.6 + ($i * 0.01)), 4326)"),
            'status' => 'approved',
        ]);
    }

    $nearest = $this->service->findNearestDataPoints(55.6, 12.5, 3);

    expect($nearest)->toHaveCount(3);
});

test('generateGridHeatmap aggregates points into grid cells', function () {
    $campaign = Campaign::factory()->create();
    $metric = EnvironmentalMetric::factory()->create();

    // Create cluster of points in same grid cell
    for ($i = 0; $i < 4; $i++) {
        DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
            'location' => DB::raw("ST_SetSRID(ST_MakePoint(12.5 + ($i * 0.0001), 55.6 + ($i * 0.0001)), 4326)"),
            'value' => 10.0 + $i,
            'status' => 'approved',
        ]);
    }

    $heatmap = $this->service->generateGridHeatmap($campaign->id, $metric->id, 0.001);

    expect($heatmap)->toBeArray()
        ->and($heatmap[0])->toHaveKey('point_count')
        ->and($heatmap[0])->toHaveKey('avg_value')
        ->and($heatmap[0]['point_count'])->toBeGreaterThanOrEqual(3);
});

test('generateGridHeatmap filters cells with less than 3 points', function () {
    $campaign = Campaign::factory()->create();
    $metric = EnvironmentalMetric::factory()->create();

    // Create 2 points (below minimum)
    DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5, 55.6), 4326)'),
        'value' => 10.0,
        'status' => 'approved',
    ]);

    DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5001, 55.6001), 4326)'),
        'value' => 11.0,
        'status' => 'approved',
    ]);

    $heatmap = $this->service->generateGridHeatmap($campaign->id, $metric->id, 0.001);

    expect($heatmap)->toBeEmpty();
});

test('detectClusters identifies spatial clusters using DBSCAN', function () {
    $campaign = Campaign::factory()->create();
    $metric = EnvironmentalMetric::factory()->create();

    // Create first cluster (5 points close together)
    for ($i = 0; $i < 5; $i++) {
        DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
            'location' => DB::raw("ST_SetSRID(ST_MakePoint(12.5 + ($i * 0.001), 55.6 + ($i * 0.001)), 4326)"),
            'value' => 10.0,
            'status' => 'approved',
        ]);
    }

    // Create second cluster (5 points close together, far from first)
    for ($i = 0; $i < 5; $i++) {
        DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
            'location' => DB::raw("ST_SetSRID(ST_MakePoint(12.6 + ($i * 0.001), 55.7 + ($i * 0.001)), 4326)"),
            'value' => 20.0,
            'status' => 'approved',
        ]);
    }

    $clusters = $this->service->detectClusters($campaign->id, $metric->id, 0.01, 5);

    expect($clusters)->toBeArray()
        ->and(count($clusters))->toBeGreaterThanOrEqual(1)
        ->and($clusters[0])->toHaveKeys(['cluster_id', 'point_count', 'avg_value', 'center_latitude', 'center_longitude', 'points']);
});

test('detectClusters filters out noise points', function () {
    $campaign = Campaign::factory()->create();
    $metric = EnvironmentalMetric::factory()->create();

    // Create cluster
    for ($i = 0; $i < 5; $i++) {
        DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
            'location' => DB::raw("ST_SetSRID(ST_MakePoint(12.5 + ($i * 0.001), 55.6 + ($i * 0.001)), 4326)"),
            'value' => 10.0,
            'status' => 'approved',
        ]);
    }

    // Create isolated noise point
    DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(13.0, 56.0), 4326)'),
        'value' => 5.0,
        'status' => 'approved',
    ]);

    $clusters = $this->service->detectClusters($campaign->id, $metric->id, 0.01, 5);

    // Noise points should not appear in results
    $totalPointsInClusters = collect($clusters)->sum('point_count');
    expect($totalPointsInClusters)->toBe(5); // Only cluster points, not noise
});

test('generateVoronoiDiagram creates valid GeoJSON', function () {
    $campaign = Campaign::factory()->create();
    $metric = EnvironmentalMetric::factory()->create();

    // Create at least 3 points for valid Voronoi
    DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5, 55.6), 4326)'),
        'status' => 'approved',
    ]);

    DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.6, 55.7), 4326)'),
        'status' => 'approved',
    ]);

    DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.7, 55.8), 4326)'),
        'status' => 'approved',
    ]);

    $voronoi = $this->service->generateVoronoiDiagram($campaign->id);

    expect($voronoi)->toHaveKey('type', 'Feature')
        ->and($voronoi)->toHaveKey('geometry')
        ->and($voronoi)->toHaveKey('properties')
        ->and($voronoi['properties'])->toHaveKey('campaign_id', $campaign->id);
});

test('generateVoronoiDiagram returns empty for no points', function () {
    $campaign = Campaign::factory()->create();

    $voronoi = $this->service->generateVoronoiDiagram($campaign->id);

    expect($voronoi)->toHaveKey('type', 'FeatureCollection')
        ->and($voronoi['features'])->toBeEmpty();
});

test('getCampaignConvexHull calculates hull and area', function () {
    $campaign = Campaign::factory()->create();
    $metric = EnvironmentalMetric::factory()->create();

    // Create points forming a square
    DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.0, 55.0), 4326)'),
        'status' => 'approved',
    ]);

    DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.1, 55.0), 4326)'),
        'status' => 'approved',
    ]);

    DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.1, 55.1), 4326)'),
        'status' => 'approved',
    ]);

    DataPoint::factory()->for($campaign)->for($metric, 'environmentalMetric')->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.0, 55.1), 4326)'),
        'status' => 'approved',
    ]);

    $hull = $this->service->getCampaignConvexHull($campaign->id);

    expect($hull)->toHaveKey('type', 'Feature')
        ->and($hull)->toHaveKey('geometry')
        ->and($hull)->toHaveKey('properties')
        ->and($hull['properties'])->toHaveKey('area_square_meters')
        ->and($hull['properties'])->toHaveKey('area_hectares')
        ->and($hull['properties']['area_square_meters'])->toBeGreaterThan(0);
});

test('getCampaignConvexHull returns null for empty campaign', function () {
    $campaign = Campaign::factory()->create();

    $hull = $this->service->getCampaignConvexHull($campaign->id);

    expect($hull)->toBeNull();
});
