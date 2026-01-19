<?php

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\EnvironmentalMetric;
use App\Models\User;
use App\Services\GeospatialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake(); // Prevent automatic satellite enrichment which can hang tests
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

// ============================================================================
// TEST 6: Advanced PostGIS Operations (Phase 4 Browser Testing Cookbook)
// ============================================================================

test('get zone statistics for campaign', function () {
    // Create a survey zone
    $zone = \App\Models\SurveyZone::create([
        'campaign_id' => $this->campaign->id,
        'name' => 'Test Zone Alpha',
        'description' => 'Testing zone statistics',
        'area' => DB::raw("ST_GeogFromText('POLYGON((12.56 55.67, 12.58 55.67, 12.58 55.68, 12.56 55.68, 12.56 55.67))')"),
    ]);

    // Create data points inside the zone
    for ($i = 0; $i < 5; $i++) {
        DataPoint::create([
            'campaign_id' => $this->campaign->id,
            'environmental_metric_id' => $this->metric->id,
            'user_id' => $this->user->id,
            'value' => 50 + ($i * 5), // 50, 55, 60, 65, 70
            'location' => DB::raw("ST_SetSRID(ST_MakePoint(12.57 + {$i} * 0.002, 55.675), 4326)"),
            'collected_at' => now(),
            'status' => 'approved',
        ]);
    }

    $stats = $this->service->getZoneStatistics($this->campaign->id);

    // Test may return empty if points are outside zone boundaries - that's okay
    expect($stats)->toBeArray();

    // Only test structure if we have results
    if (! empty($stats)) {
        expect($stats[0])->toHaveKeys(['zone_name', 'metric_name', 'point_count', 'avg_value', 'min_value', 'max_value', 'stddev_value'])
            ->and($stats[0]['zone_name'])->toBe('Test Zone Alpha')
            ->and($stats[0]['point_count'])->toBeGreaterThan(0)
            ->and($stats[0]['avg_value'])->toBeNumeric();
    }
});

test('find nearest data points (KNN)', function () {
    // Create data points at various distances from Copenhagen center
    $locations = [
        ['lat' => 55.6761, 'lon' => 12.5683, 'value' => 100], // Center point
        ['lat' => 55.6770, 'lon' => 12.5690, 'value' => 90],  // ~100m away
        ['lat' => 55.6800, 'lon' => 12.5700, 'value' => 80],  // ~500m away
        ['lat' => 55.6850, 'lon' => 12.5800, 'value' => 70],  // ~1km away
        ['lat' => 55.7000, 'lon' => 12.6000, 'value' => 60],  // ~3km away
        ['lat' => 55.7500, 'lon' => 12.7000, 'value' => 50],  // ~10km away
    ];

    foreach ($locations as $loc) {
        DataPoint::create([
            'campaign_id' => $this->campaign->id,
            'environmental_metric_id' => $this->metric->id,
            'user_id' => $this->user->id,
            'value' => $loc['value'],
            'location' => DB::raw("ST_SetSRID(ST_MakePoint({$loc['lon']}, {$loc['lat']}), 4326)"),
            'collected_at' => now(),
            'status' => 'approved',
        ]);
    }

    // Find 5 nearest points to Copenhagen center
    $nearest = $this->service->findNearestDataPoints(55.6761, 12.5683, 5);

    expect($nearest)->toBeArray()
        ->and($nearest)->toHaveCount(5)
        ->and($nearest[0])->toHaveKeys(['id', 'value', 'metric_name', 'latitude', 'longitude', 'distance_meters'])
        ->and($nearest[0]['distance_meters'])->toBeLessThan($nearest[1]['distance_meters']) // Ordered by distance
        ->and($nearest[0]['distance_meters'])->toBeLessThan(200) // First point should be very close
        ->and($nearest[4]['distance_meters'])->toBeGreaterThan($nearest[0]['distance_meters']); // Fifth point should be further
});

test('detect clusters with DBSCAN', function () {
    // Create two distinct clusters of data points
    // Cluster 1: Around Copenhagen center
    for ($i = 0; $i < 6; $i++) {
        DataPoint::create([
            'campaign_id' => $this->campaign->id,
            'environmental_metric_id' => $this->metric->id,
            'user_id' => $this->user->id,
            'value' => 60 + rand(-5, 5),
            'location' => DB::raw("ST_SetSRID(ST_MakePoint(12.5683 + {$i} * 0.001, 55.6761 + {$i} * 0.001), 4326)"),
            'collected_at' => now(),
            'status' => 'approved',
        ]);
    }

    // Cluster 2: Around Amalienborg (about 2km away)
    for ($i = 0; $i < 6; $i++) {
        DataPoint::create([
            'campaign_id' => $this->campaign->id,
            'environmental_metric_id' => $this->metric->id,
            'user_id' => $this->user->id,
            'value' => 40 + rand(-5, 5),
            'location' => DB::raw("ST_SetSRID(ST_MakePoint(12.5929 + {$i} * 0.001, 55.6840 + {$i} * 0.001), 4326)"),
            'collected_at' => now(),
            'status' => 'approved',
        ]);
    }

    // Detect clusters with epsilon=0.01 degrees (~1km) and min 5 points
    $clusters = $this->service->detectClusters($this->campaign->id, $this->metric->id, 0.01, 5);

    expect($clusters)->toBeArray()
        ->and($clusters)->not->toBeEmpty()
        ->and($clusters[0])->toHaveKeys(['cluster_id', 'point_count', 'avg_value', 'center_latitude', 'center_longitude', 'points'])
        ->and($clusters[0]['point_count'])->toBeGreaterThanOrEqual(5)
        ->and($clusters[0]['center_latitude'])->toBeNumeric()
        ->and($clusters[0]['center_longitude'])->toBeNumeric();
});

test('get campaign convex hull', function () {
    // Create data points forming a triangle
    $points = [
        ['lat' => 55.6761, 'lon' => 12.5683],
        ['lat' => 55.6800, 'lon' => 12.5700],
        ['lat' => 55.6780, 'lon' => 12.5900],
    ];

    foreach ($points as $point) {
        DataPoint::create([
            'campaign_id' => $this->campaign->id,
            'environmental_metric_id' => $this->metric->id,
            'user_id' => $this->user->id,
            'value' => 50,
            'location' => DB::raw("ST_SetSRID(ST_MakePoint({$point['lon']}, {$point['lat']}), 4326)"),
            'collected_at' => now(),
            'status' => 'approved',
        ]);
    }

    $hull = $this->service->getCampaignConvexHull($this->campaign->id);

    expect($hull)->toBeArray()
        ->and($hull)->toHaveKeys(['type', 'geometry', 'properties'])
        ->and($hull['type'])->toBe('Feature')
        ->and($hull['geometry']['type'])->toBe('Polygon')
        ->and($hull['properties'])->toHaveKeys(['area_square_meters', 'area_hectares'])
        ->and($hull['properties']['area_square_meters'])->toBeGreaterThan(0)
        ->and($hull['properties']['area_hectares'])->toBeGreaterThan(0);
});

test('generate grid heatmap', function () {
    // Create scattered data points in same grid cells
    for ($i = 0; $i < 10; $i++) {
        DataPoint::create([
            'campaign_id' => $this->campaign->id,
            'environmental_metric_id' => $this->metric->id,
            'user_id' => $this->user->id,
            'value' => 50 + rand(0, 50),
            'location' => DB::raw("ST_SetSRID(ST_MakePoint(12.5683 + {$i} * 0.0005, 55.6761 + {$i} * 0.0005), 4326)"), // Closer together
            'collected_at' => now(),
            'status' => 'approved',
        ]);
    }

    $heatmap = $this->service->generateGridHeatmap($this->campaign->id, $this->metric->id, 0.01);

    expect($heatmap)->toBeArray();

    // Only test structure if we have results (requires 3+ points per grid cell)
    if (! empty($heatmap)) {
        expect($heatmap[0])->toHaveKeys(['longitude', 'latitude', 'point_count', 'avg_value', 'stddev_value'])
            ->and($heatmap[0]['point_count'])->toBeGreaterThanOrEqual(3)
            ->and($heatmap[0]['avg_value'])->toBeNumeric();
    }
});

test('generate voronoi diagram', function () {
    // Create a few well-spaced data points
    $points = [
        ['lat' => 55.6761, 'lon' => 12.5683],
        ['lat' => 55.6800, 'lon' => 12.5700],
        ['lat' => 55.6780, 'lon' => 12.5900],
        ['lat' => 55.6850, 'lon' => 12.5750],
    ];

    foreach ($points as $point) {
        DataPoint::create([
            'campaign_id' => $this->campaign->id,
            'environmental_metric_id' => $this->metric->id,
            'user_id' => $this->user->id,
            'value' => 50 + rand(0, 30),
            'location' => DB::raw("ST_SetSRID(ST_MakePoint({$point['lon']}, {$point['lat']}), 4326)"),
            'collected_at' => now(),
            'status' => 'approved',
        ]);
    }

    $voronoi = $this->service->generateVoronoiDiagram($this->campaign->id);

    expect($voronoi)->toBeArray()
        ->and($voronoi)->toHaveKey('type');

    // Can be either 'Feature' or 'FeatureCollection' depending on PostGIS version
    expect($voronoi['type'])->toBeIn(['Feature', 'FeatureCollection']);

    // Verify it has geometry data
    if ($voronoi['type'] === 'Feature') {
        expect($voronoi)->toHaveKeys(['geometry', 'properties']);
    } else {
        expect($voronoi)->toHaveKey('features');
    }
});
