<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\SurveyZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('satellite viewer loads survey zones GeoJSON for campaign', function () {
    $campaign = Campaign::factory()->create(['name' => 'Test Campaign']);

    $zone = SurveyZone::factory()->for($campaign)->create([
        'name' => 'Zone Alpha',
        'description' => 'Primary survey area',
        'area_km2' => 5.5,
    ]);

    // Update geometry using direct SQL
    DB::statement(
        'UPDATE survey_zones SET area = ST_GeogFromText(?) WHERE id = ?',
        ['POLYGON((12.0 55.0, 12.1 55.0, 12.1 55.1, 12.0 55.1, 12.0 55.0))', $zone->id]
    );

    $component = Volt::test('maps.satellite-viewer')
        ->set('campaignId', $campaign->id);

    $surveyZonesGeoJSON = $component->get('surveyZonesGeoJSON');

    expect($surveyZonesGeoJSON)->toBeArray()
        ->and($surveyZonesGeoJSON)->toHaveKey('type', 'FeatureCollection')
        ->and($surveyZonesGeoJSON)->toHaveKey('features')
        ->and($surveyZonesGeoJSON['features'])->toHaveCount(1)
        ->and($surveyZonesGeoJSON['features'][0])->toHaveKey('type', 'Feature')
        ->and($surveyZonesGeoJSON['features'][0])->toHaveKey('properties')
        ->and($surveyZonesGeoJSON['features'][0])->toHaveKey('geometry')
        ->and($surveyZonesGeoJSON['features'][0]['properties'])->toHaveKey('name', 'Zone Alpha')
        ->and($surveyZonesGeoJSON['features'][0]['properties']['area_km2'])->toBeGreaterThan(0);
});

test('satellite viewer returns null when campaign has no survey zones', function () {
    $campaign = Campaign::factory()->create(['name' => 'Empty Campaign']);

    $component = Volt::test('maps.satellite-viewer')
        ->set('campaignId', $campaign->id);

    $surveyZonesGeoJSON = $component->get('surveyZonesGeoJSON');

    expect($surveyZonesGeoJSON)->toBeNull();
});

test('satellite viewer returns null when no campaign selected', function () {
    $component = Volt::test('maps.satellite-viewer')
        ->set('campaignId', null);

    $surveyZonesGeoJSON = $component->get('surveyZonesGeoJSON');

    expect($surveyZonesGeoJSON)->toBeNull();
});

test('satellite viewer loads multiple survey zones for campaign', function () {
    $campaign = Campaign::factory()->create();

    $zone1 = SurveyZone::factory()->for($campaign)->create(['name' => 'Zone A']);
    $zone2 = SurveyZone::factory()->for($campaign)->create(['name' => 'Zone B']);

    // Update geometries
    DB::statement(
        'UPDATE survey_zones SET area = ST_GeogFromText(?) WHERE id = ?',
        ['POLYGON((12.0 55.0, 12.1 55.0, 12.1 55.1, 12.0 55.1, 12.0 55.0))', $zone1->id]
    );

    DB::statement(
        'UPDATE survey_zones SET area = ST_GeogFromText(?) WHERE id = ?',
        ['POLYGON((12.2 55.2, 12.3 55.2, 12.3 55.3, 12.2 55.3, 12.2 55.2))', $zone2->id]
    );

    $component = Volt::test('maps.satellite-viewer')
        ->set('campaignId', $campaign->id);

    $surveyZonesGeoJSON = $component->get('surveyZonesGeoJSON');

    expect($surveyZonesGeoJSON)->toBeArray()
        ->and($surveyZonesGeoJSON['features'])->toHaveCount(2)
        ->and($surveyZonesGeoJSON['features'][0]['properties']['name'])->toBeIn(['Zone A', 'Zone B'])
        ->and($surveyZonesGeoJSON['features'][1]['properties']['name'])->toBeIn(['Zone A', 'Zone B']);
});
