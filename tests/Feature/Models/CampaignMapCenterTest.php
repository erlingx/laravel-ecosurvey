<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\SurveyZone;

test('campaign with survey zone returns zone centroid as map center', function () {
    $campaign = Campaign::factory()->create();
    $zone = SurveyZone::factory()->create(['campaign_id' => $campaign->id]);
    $zone->refresh();

    $center = $campaign->getMapCenter();
    $zoneCentroid = $zone->getCentroid();

    expect($center)->toBeArray()
        ->toHaveCount(2)
        ->and($center[0])->toBe($zoneCentroid[0])
        ->and($center[1])->toBe($zoneCentroid[1]);
});

test('campaign with only data points returns average of data point locations', function () {
    $campaign = Campaign::factory()->create();

    // Create data points at known coordinates
    DataPoint::factory()
        ->withCoordinates(55.6761, 12.5683)
        ->create(['campaign_id' => $campaign->id]);
    DataPoint::factory()
        ->withCoordinates(55.7061, 12.5983)
        ->create(['campaign_id' => $campaign->id]);

    $center = $campaign->getMapCenter();

    // Expected average: lat = (55.6761 + 55.7061) / 2 = 55.6911
    //                   lon = (12.5683 + 12.5983) / 2 = 12.5833
    expect($center)->toBeArray()
        ->toHaveCount(2)
        ->and($center[0])->toBeFloat()
        ->and($center[1])->toBeFloat()
        ->and($center[0])->toBeGreaterThan(12.58)
        ->and($center[0])->toBeLessThan(12.59)
        ->and($center[1])->toBeGreaterThan(55.69)
        ->and($center[1])->toBeLessThan(55.70);
});

test('campaign without survey zone or data points returns default Copenhagen coordinates', function () {
    $campaign = Campaign::factory()->create();

    $center = $campaign->getMapCenter();

    expect($center)->toBeArray()
        ->toHaveCount(2)
        ->and($center[0])->toBe(12.5683) // Default Copenhagen longitude
        ->and($center[1])->toBe(55.6761); // Default Copenhagen latitude
});

test('campaign prioritizes survey zone over data points for map center', function () {
    $campaign = Campaign::factory()->create();

    // Create survey zone with known centroid
    $zone = SurveyZone::factory()->create(['campaign_id' => $campaign->id]);
    $zone->refresh();
    $zoneCentroid = $zone->getCentroid();

    // Create data points at different location
    DataPoint::factory()
        ->withCoordinates(60.0, 10.0) // Far from zone
        ->create(['campaign_id' => $campaign->id]);

    $center = $campaign->getMapCenter();

    // Should return zone centroid, NOT data point average
    expect($center)->toBeArray()
        ->and($center[0])->toBe($zoneCentroid[0])
        ->and($center[1])->toBe($zoneCentroid[1]);
});
