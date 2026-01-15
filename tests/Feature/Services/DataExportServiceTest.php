<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\SatelliteAnalysis;
use App\Services\DataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

test('exports campaign data with full provenance for publication', function () {
    $campaign = Campaign::factory()->create(['name' => 'Test Campaign']);

    $dataPoint = DataPoint::factory()->for($campaign)->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5683, 55.6761), 4326)'),
        'collected_at' => now()->subDays(3),
        'status' => 'approved',
        'accuracy' => 5.0,
    ]);

    SatelliteAnalysis::create([
        'data_point_id' => $dataPoint->id,
        'campaign_id' => $campaign->id,
        'ndvi_value' => 0.75,
        'moisture_index' => 0.45,
        'acquisition_date' => now()->subDays(2),
        'satellite_source' => 'Sentinel-2 L2A',
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5683, 55.6761), 4326)'),
    ]);

    $service = new DataExportService;
    $export = $service->exportForPublication($campaign);

    expect($export)->toHaveKeys(['metadata', 'data_points'])
        ->and($export['metadata'])->toHaveKey('campaign_name', 'Test Campaign')
        ->and($export['metadata'])->toHaveKey('coordinate_system', 'WGS84 (EPSG:4326)')
        ->and($export['metadata'])->toHaveKey('satellite_indices')
        ->and($export['metadata']['satellite_indices'])->toHaveKey('NDVI')
        ->and($export['data_points'])->toHaveCount(1)
        ->and($export['data_points'][0])->toHaveKeys(['id', 'location', 'quality_control', 'satellite_context', 'measurement'])
        ->and($export['data_points'][0]['satellite_context']['ndvi_value'])->toBe(0.75)
        ->and($export['data_points'][0]['satellite_context']['temporal_quality'])->toBe('excellent');
});

test('export includes QA statistics', function () {
    $campaign = Campaign::factory()->create();

    DataPoint::factory()->for($campaign)->count(3)->create(['status' => 'approved']);
    DataPoint::factory()->for($campaign)->count(2)->create(['status' => 'pending']);
    DataPoint::factory()->for($campaign)->create(['status' => 'rejected']);

    $service = new DataExportService;
    $export = $service->exportForPublication($campaign);

    expect($export['metadata']['qa_statistics']->approved_count)->toBe(3)
        ->and($export['metadata']['qa_statistics']->pending_count)->toBe(2)
        ->and($export['metadata']['qa_statistics']->rejected_count)->toBe(1);
});

test('export only includes approved data points', function () {
    $campaign = Campaign::factory()->create();

    DataPoint::factory()->for($campaign)->create(['status' => 'approved']);
    DataPoint::factory()->for($campaign)->create(['status' => 'pending']);
    DataPoint::factory()->for($campaign)->create(['status' => 'rejected']);
    DataPoint::factory()->for($campaign)->create(['status' => 'draft']);

    $service = new DataExportService;
    $export = $service->exportForPublication($campaign);

    expect($export['data_points'])->toHaveCount(1)
        ->and($export['data_points'][0]['quality_control']['status'])->toBe('approved');
});

test('exports CSV format correctly', function () {
    $campaign = Campaign::factory()->create();

    $dataPoint = DataPoint::factory()->for($campaign)->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5683, 55.6761), 4326)'),
        'status' => 'approved',
    ]);

    SatelliteAnalysis::create([
        'data_point_id' => $dataPoint->id,
        'campaign_id' => $campaign->id,
        'ndvi_value' => 0.80,
        'moisture_index' => 0.50,
        'acquisition_date' => now()->subDays(1),
        'satellite_source' => 'Sentinel-2 L2A',
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5683, 55.6761), 4326)'),
    ]);

    $service = new DataExportService;
    $csv = $service->exportAsCSV($campaign);

    expect($csv)->toContain('id,collected_at,latitude,longitude')
        ->and($csv)->toContain('55.6761')
        ->and($csv)->toContain('12.5683')
        ->and($csv)->toContain('0.8')
        ->and($csv)->toContain('0.5');
});

test('temporal quality is calculated correctly', function () {
    $campaign = Campaign::factory()->create();

    $dataPoint1 = DataPoint::factory()->for($campaign)->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5683, 55.6761), 4326)'),
        'collected_at' => now(),
        'status' => 'approved',
    ]);

    SatelliteAnalysis::create([
        'data_point_id' => $dataPoint1->id,
        'campaign_id' => $campaign->id,
        'ndvi_value' => 0.75,
        'acquisition_date' => now()->subDays(2), // 2 days difference
        'satellite_source' => 'Sentinel-2 L2A',
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5683, 55.6761), 4326)'),
    ]);

    $service = new DataExportService;
    $export = $service->exportForPublication($campaign);

    expect($export['data_points'][0]['satellite_context']['temporal_quality'])->toBe('excellent');
});

test('handles data points without satellite analyses', function () {
    $campaign = Campaign::factory()->create();

    DataPoint::factory()->for($campaign)->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5683, 55.6761), 4326)'),
        'status' => 'approved',
    ]);

    $service = new DataExportService;
    $export = $service->exportForPublication($campaign);

    expect($export['data_points'])->toHaveCount(1)
        ->and($export['data_points'][0]['satellite_context'])->toBeNull();
});

test('exports all 7 satellite indices when available', function () {
    $campaign = Campaign::factory()->create();

    $dataPoint = DataPoint::factory()->for($campaign)->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5683, 55.6761), 4326)'),
        'status' => 'approved',
    ]);

    SatelliteAnalysis::create([
        'data_point_id' => $dataPoint->id,
        'campaign_id' => $campaign->id,
        'ndvi_value' => 0.75,
        'moisture_index' => 0.45,
        'ndre_value' => 0.35,
        'evi_value' => 0.58,
        'msi_value' => 1.45,
        'savi_value' => 0.68,
        'gndvi_value' => 0.72,
        'acquisition_date' => now()->subDays(1),
        'satellite_source' => 'Sentinel-2 L2A',
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5683, 55.6761), 4326)'),
    ]);

    $service = new DataExportService;
    $export = $service->exportForPublication($campaign);

    $satContext = $export['data_points'][0]['satellite_context'];

    expect($satContext['ndvi_value'])->toBe(0.75)
        ->and($satContext['ndmi_value'])->toBe(0.45)
        ->and($satContext['ndre_value'])->toBe(0.35)
        ->and($satContext['evi_value'])->toBe(0.58)
        ->and($satContext['msi_value'])->toBe(1.45)
        ->and($satContext['savi_value'])->toBe(0.68)
        ->and($satContext['gndvi_value'])->toBe(0.72);
});
