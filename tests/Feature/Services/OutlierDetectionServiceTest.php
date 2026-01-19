<?php

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\EnvironmentalMetric;
use App\Services\OutlierDetectionService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake(); // Prevent automatic satellite enrichment which can hang tests
});

test('detects outliers using IQR method', function () {
    $campaign = Campaign::factory()->create();
    $metric = EnvironmentalMetric::factory()->create();

    // Create normal data points (values: 10-20)
    foreach (range(10, 20) as $value) {
        DataPoint::factory()->create([
            'campaign_id' => $campaign->id,
            'environmental_metric_id' => $metric->id,
            'value' => $value,
            'status' => 'approved',
        ]);
    }

    // Create outliers
    $outlier1 = DataPoint::factory()->create([
        'campaign_id' => $campaign->id,
        'environmental_metric_id' => $metric->id,
        'value' => 100, // Clear outlier
        'status' => 'approved',
    ]);

    $outlier2 = DataPoint::factory()->create([
        'campaign_id' => $campaign->id,
        'environmental_metric_id' => $metric->id,
        'value' => -50, // Clear outlier
        'status' => 'approved',
    ]);

    $service = new OutlierDetectionService;
    $outliers = $service->detectOutliersIQR($campaign->id, $metric->id);

    expect($outliers)->toContain($outlier1->id, $outlier2->id);
});

test('detects outliers using Z-score method', function () {
    $campaign = Campaign::factory()->create();
    $metric = EnvironmentalMetric::factory()->create();

    // Create normal data points with mean ~50
    foreach (range(45, 55) as $value) {
        DataPoint::factory()->create([
            'campaign_id' => $campaign->id,
            'environmental_metric_id' => $metric->id,
            'value' => $value,
            'status' => 'approved',
        ]);
    }

    // Create clear outlier
    $outlier = DataPoint::factory()->create([
        'campaign_id' => $campaign->id,
        'environmental_metric_id' => $metric->id,
        'value' => 200,
        'status' => 'approved',
    ]);

    $service = new OutlierDetectionService;
    $outliers = $service->detectOutliersZScore($campaign->id, $metric->id, 3.0);

    expect($outliers)->toContain($outlier->id);
});

test('does not flag rejected data points as outliers', function () {
    $campaign = Campaign::factory()->create();
    $metric = EnvironmentalMetric::factory()->create();

    // Create normal data
    foreach (range(10, 20) as $value) {
        DataPoint::factory()->create([
            'campaign_id' => $campaign->id,
            'environmental_metric_id' => $metric->id,
            'value' => $value,
            'status' => 'approved',
        ]);
    }

    // Create rejected outlier - should be ignored
    DataPoint::factory()->create([
        'campaign_id' => $campaign->id,
        'environmental_metric_id' => $metric->id,
        'value' => 1000,
        'status' => 'rejected',
    ]);

    $service = new OutlierDetectionService;
    $outliers = $service->detectOutliersIQR($campaign->id, $metric->id);

    expect($outliers)->toBeEmpty();
});

test('flags outliers in database', function () {
    $campaign = Campaign::factory()->create();
    $metric = EnvironmentalMetric::factory()->create();

    // Create normal data
    foreach (range(10, 15) as $value) {
        DataPoint::factory()->create([
            'campaign_id' => $campaign->id,
            'environmental_metric_id' => $metric->id,
            'value' => $value,
            'status' => 'approved',
        ]);
    }

    // Create outlier
    $outlier = DataPoint::factory()->create([
        'campaign_id' => $campaign->id,
        'environmental_metric_id' => $metric->id,
        'value' => 100,
        'status' => 'approved',
    ]);

    $service = new OutlierDetectionService;
    $flaggedCount = $service->flagOutliers($campaign->id, $metric->id);

    expect($flaggedCount)->toBeGreaterThan(0);

    $outlier->refresh();
    expect($outlier->qa_flags)->not->toBeNull()
        ->and($outlier->qa_flags)->toBeArray()
        ->and($outlier->qa_flags[0]['type'])->toBe('outlier');
});

test('returns empty array for insufficient data', function () {
    $campaign = Campaign::factory()->create();
    $metric = EnvironmentalMetric::factory()->create();

    // Create only 2 data points (less than required minimum)
    DataPoint::factory()->count(2)->create([
        'campaign_id' => $campaign->id,
        'environmental_metric_id' => $metric->id,
        'status' => 'approved',
    ]);

    $service = new OutlierDetectionService;
    $outliers = $service->detectOutliersIQR($campaign->id, $metric->id);

    expect($outliers)->toBeEmpty();
});

test('handles identical values without errors', function () {
    $campaign = Campaign::factory()->create();
    $metric = EnvironmentalMetric::factory()->create();

    // Create data points with identical values
    foreach (range(1, 10) as $i) {
        DataPoint::factory()->create([
            'campaign_id' => $campaign->id,
            'environmental_metric_id' => $metric->id,
            'value' => 50.0,
            'status' => 'approved',
        ]);
    }

    $service = new OutlierDetectionService;
    $outliers = $service->detectOutliersZScore($campaign->id, $metric->id);

    expect($outliers)->toBeEmpty();
});
