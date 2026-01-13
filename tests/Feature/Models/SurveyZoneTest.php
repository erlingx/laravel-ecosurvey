<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\SurveyZone;

test('survey zone can calculate area', function () {
    $zone = SurveyZone::factory()->create();
    $zone->refresh(); // Ensure geometry is set by factory afterCreating hook

    $area = $zone->calculateArea();

    expect($area)->toBeFloat()
        ->toBeGreaterThan(0)
        ->toBeLessThan(100); // Reasonable area for test zones
});

test('survey zone can get centroid', function () {
    $zone = SurveyZone::factory()->create();
    $zone->refresh();

    $centroid = $zone->getCentroid();

    expect($centroid)->toBeArray()
        ->toHaveCount(2)
        ->and((float) $centroid[0])->toBeFloat() // longitude (cast string to float)
        ->and((float) $centroid[1])->toBeFloat(); // latitude (cast string to float)
});

test('survey zone can get bounding box', function () {
    $zone = SurveyZone::factory()->create();
    $zone->refresh();

    $bbox = $zone->getBoundingBox();

    expect($bbox)->toBeArray()
        ->toHaveCount(4)
        ->and((float) $bbox[0])->toBeFloat() // minLon
        ->and((float) $bbox[1])->toBeFloat() // minLat
        ->and((float) $bbox[2])->toBeFloat() // maxLon
        ->and((float) $bbox[3])->toBeFloat() // maxLat
        ->and((float) $bbox[2])->toBeGreaterThan((float) $bbox[0]) // maxLon > minLon
        ->and((float) $bbox[3])->toBeGreaterThan((float) $bbox[1]); // maxLat > minLat
});

test('survey zone can export to GeoJSON', function () {
    $zone = SurveyZone::factory()->create(['name' => 'Test Zone']);
    $zone->refresh();

    $geojson = $zone->toGeoJSON();

    expect($geojson)->toBeArray()
        ->toHaveKeys(['type', 'geometry', 'properties'])
        ->and($geojson['type'])->toBe('Feature')
        ->and($geojson['geometry'])->toHaveKeys(['type', 'coordinates'])
        ->and($geojson['geometry']['type'])->toBe('Polygon')
        ->and($geojson['properties'])->toHaveKey('name')
        ->and($geojson['properties']['name'])->toBe('Test Zone');
});

test('survey zone can find contained data points', function () {
    $campaign = Campaign::factory()->create();
    $zone = SurveyZone::factory()->create(['campaign_id' => $campaign->id]);
    $zone->refresh();

    // Get zone center coordinates
    $bbox = $zone->getBoundingBox();
    $lat = ((float) $bbox[1] + (float) $bbox[3]) / 2;
    $lon = ((float) $bbox[0] + (float) $bbox[2]) / 2;

    // Create data point at zone center
    $dataPoint = DataPoint::factory()
        ->withCoordinates($lat, $lon)
        ->create(['campaign_id' => $campaign->id]);

    // Test spatial query (returns array of stdClass objects)
    $containedPoints = $zone->getContainedDataPoints();

    expect($containedPoints)->toBeArray()
        ->toHaveCount(1)
        ->and($containedPoints[0])->toBeInstanceOf(stdClass::class)
        ->and($containedPoints[0]->id)->toBe($dataPoint->id);
});

test('survey zone does not contain data points outside its bounds', function () {
    $campaign = Campaign::factory()->create();
    $zone = SurveyZone::factory()->create(['campaign_id' => $campaign->id]);
    $zone->refresh();

    // Create data point far outside zone (different continent)
    $dataPoint = DataPoint::factory()
        ->withCoordinates(0, 0) // Null Island (Atlantic Ocean)
        ->create(['campaign_id' => $campaign->id]);

    // Test spatial query
    $containedPoints = $zone->getContainedDataPoints();

    expect($containedPoints)->toHaveCount(0);
});

test('survey zone relationship with campaign works', function () {
    $campaign = Campaign::factory()->create();
    $zone = SurveyZone::factory()->create(['campaign_id' => $campaign->id]);

    expect($zone->campaign)->toBeInstanceOf(Campaign::class)
        ->and($zone->campaign->id)->toBe($campaign->id);
});

test('campaign can have multiple survey zones', function () {
    $campaign = Campaign::factory()->create();
    $zones = SurveyZone::factory()->count(3)->create(['campaign_id' => $campaign->id]);

    expect($campaign->surveyZones)->toHaveCount(3);
});
