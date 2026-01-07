<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\EnvironmentalMetric;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new AnalyticsService;
    $this->user = User::factory()->create();
    $this->campaign = Campaign::factory()->create();
    $this->metric = EnvironmentalMetric::factory()->create();
});

test('get heatmap data returns formatted array', function () {
    // Create data points with known coordinates
    DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 25.5,
        'location' => DB::raw("ST_GeomFromText('POINT(12.5683 55.6761)', 4326)"),
    ]);

    $result = $this->service->getHeatmapData();

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(1)
        ->and($result[0])->toHaveCount(3)
        ->and($result[0][0])->toBeFloat() // latitude
        ->and($result[0][1])->toBeFloat() // longitude
        ->and($result[0][2])->toBe(25.5); // value
});

test('get heatmap data filters by campaign', function () {
    $otherCampaign = Campaign::factory()->create();

    DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 10.0,
    ]);

    DataPoint::factory()->create([
        'campaign_id' => $otherCampaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 20.0,
    ]);

    $result = $this->service->getHeatmapData($this->campaign->id);

    expect($result)->toHaveCount(1)
        ->and($result[0][2])->toBe(10.0);
});

test('get heatmap data filters by metric', function () {
    $otherMetric = EnvironmentalMetric::factory()->create();

    DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 15.0,
    ]);

    DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $otherMetric->id,
        'user_id' => $this->user->id,
        'value' => 30.0,
    ]);

    $result = $this->service->getHeatmapData(null, $this->metric->id);

    expect($result)->toHaveCount(1)
        ->and($result[0][2])->toBe(15.0);
});

test('calculate statistics with data', function () {
    // Create data points with known values
    $values = [10, 20, 30, 40, 50];
    foreach ($values as $value) {
        DataPoint::factory()->create([
            'campaign_id' => $this->campaign->id,
            'environmental_metric_id' => $this->metric->id,
            'user_id' => $this->user->id,
            'value' => $value,
        ]);
    }

    $result = $this->service->calculateStatistics();

    expect($result['count'])->toBe(5)
        ->and($result['min'])->toBe(10.0)
        ->and($result['max'])->toBe(50.0)
        ->and($result['average'])->toBe(30.0)
        ->and($result['median'])->toBe(30.0)
        ->and($result['std_dev'])->toBeFloat();
});

test('calculate statistics with no data', function () {
    $result = $this->service->calculateStatistics();

    expect($result['count'])->toBe(0)
        ->and($result['min'])->toBeNull()
        ->and($result['max'])->toBeNull()
        ->and($result['average'])->toBeNull()
        ->and($result['median'])->toBeNull()
        ->and($result['std_dev'])->toBeNull();
});

test('calculate statistics median with even count', function () {
    $values = [10, 20, 30, 40];
    foreach ($values as $value) {
        DataPoint::factory()->create([
            'campaign_id' => $this->campaign->id,
            'environmental_metric_id' => $this->metric->id,
            'user_id' => $this->user->id,
            'value' => $value,
        ]);
    }

    $result = $this->service->calculateStatistics();

    expect($result['median'])->toBe(25.0); // (20 + 30) / 2
});

test('calculate statistics filters by campaign', function () {
    $otherCampaign = Campaign::factory()->create();

    DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 10.0,
    ]);

    DataPoint::factory()->create([
        'campaign_id' => $otherCampaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 100.0,
    ]);

    $result = $this->service->calculateStatistics($this->campaign->id);

    expect($result['count'])->toBe(1)
        ->and($result['max'])->toBe(10.0);
});

test('get trend data returns time series', function () {
    DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 20.0,
        'created_at' => now()->subDays(2),
    ]);

    DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 25.0,
        'created_at' => now()->subDays(1),
    ]);

    DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 30.0,
        'created_at' => now(),
    ]);

    $result = $this->service->getTrendData(null, null, 'day');

    expect($result)->toBeArray()
        ->and(count($result))->toBeGreaterThan(0)
        ->and($result[0])->toHaveKeys(['period', 'average', 'minimum', 'maximum', 'count']);
});

test('get trend data filters by campaign', function () {
    $otherCampaign = Campaign::factory()->create();

    DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 10.0,
    ]);

    DataPoint::factory()->create([
        'campaign_id' => $otherCampaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 50.0,
    ]);

    $result = $this->service->getTrendData($this->campaign->id);

    expect($result)->toHaveCount(1)
        ->and($result[0]['average'])->toBe(10.0);
});

test('get distribution data returns histogram', function () {
    $values = [10, 15, 20, 25, 30, 35, 40, 45, 50];
    foreach ($values as $value) {
        DataPoint::factory()->create([
            'campaign_id' => $this->campaign->id,
            'environmental_metric_id' => $this->metric->id,
            'user_id' => $this->user->id,
            'value' => $value,
        ]);
    }

    $result = $this->service->getDistributionData(null, null, 5);

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(5)
        ->and($result[0])->toHaveKeys(['range', 'count']);
});

test('get distribution data returns empty for no data', function () {
    $result = $this->service->getDistributionData();

    expect($result)->toBeArray()
        ->and($result)->toBeEmpty();
});

test('get distribution data filters by metric', function () {
    $otherMetric = EnvironmentalMetric::factory()->create();

    DataPoint::factory()->count(5)->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 20.0,
    ]);

    DataPoint::factory()->count(3)->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $otherMetric->id,
        'user_id' => $this->user->id,
        'value' => 50.0,
    ]);

    $result = $this->service->getDistributionData(null, $this->metric->id);

    expect(array_sum(array_column($result, 'count')))->toBe(5);
});
