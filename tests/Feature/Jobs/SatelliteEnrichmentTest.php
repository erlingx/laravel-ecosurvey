<?php

declare(strict_types=1);

use App\Jobs\EnrichDataPointWithSatelliteData;
use App\Models\DataPoint;
use App\Models\SatelliteAnalysis;
use Illuminate\Support\Facades\Queue;

test('creating data point dispatches satellite enrichment job', function () {
    Queue::fake();

    $dataPoint = DataPoint::factory()->create();

    Queue::assertPushed(EnrichDataPointWithSatelliteData::class, function ($job) use ($dataPoint) {
        return $job->dataPoint->id === $dataPoint->id;
    });
});

test('updating data point does not dispatch satellite enrichment job', function () {
    Queue::fake();

    $dataPoint = DataPoint::factory()->create();

    // Clear queue after creation
    Queue::fake();

    // Update data point
    $dataPoint->update(['value' => 99.99]);

    Queue::assertNotPushed(EnrichDataPointWithSatelliteData::class);
});

test('satellite enrichment job can be dispatched manually', function () {
    $dataPoint = DataPoint::factory()->create();

    // Dispatch job manually (not via queue, for testing)
    $job = new EnrichDataPointWithSatelliteData($dataPoint);

    expect($job->dataPoint->id)->toBe($dataPoint->id);
});

test('satellite analysis can be created for data point', function () {
    $dataPoint = DataPoint::factory()->create();

    $analysis = SatelliteAnalysis::factory()->create([
        'data_point_id' => $dataPoint->id,
        'ndvi_value' => 0.75,
        'satellite_source' => 'Copernicus Sentinel-2',
    ]);

    expect($analysis->dataPoint->id)->toBe($dataPoint->id)
        ->and((float) $analysis->ndvi_value)->toBe(0.75) // Cast string to float
        ->and($analysis->satellite_source)->toBe('Copernicus Sentinel-2');
});

test('data point can have multiple satellite analyses from different sources', function () {
    Queue::fake(); // Prevent automatic enrichment job from running

    $dataPoint = DataPoint::factory()->create();

    SatelliteAnalysis::factory()->create([
        'data_point_id' => $dataPoint->id,
        'satellite_source' => 'Copernicus Sentinel-2',
        'ndvi_value' => 0.75,
    ]);

    SatelliteAnalysis::factory()->create([
        'data_point_id' => $dataPoint->id,
        'satellite_source' => 'NASA MODIS',
        'moisture_index' => 0.35, // Use moisture_index, not soil_moisture
    ]);

    expect($dataPoint->satelliteAnalyses)->toHaveCount(2);
});

test('satellite analysis relationship to data point works', function () {
    $dataPoint = DataPoint::factory()->create();
    $analysis = SatelliteAnalysis::factory()->create(['data_point_id' => $dataPoint->id]);

    expect($analysis->dataPoint)->toBeInstanceOf(DataPoint::class)
        ->and($analysis->dataPoint->id)->toBe($dataPoint->id);
});
