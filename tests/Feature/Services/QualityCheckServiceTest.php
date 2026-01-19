<?php

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\EnvironmentalMetric;
use App\Models\User;
use App\Services\QualityCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake(); // Prevent automatic satellite enrichment which can hang tests
    $this->service = app(QualityCheckService::class);
    $this->user = User::factory()->create();
    $this->metric = EnvironmentalMetric::factory()->create([
        'name' => 'Temperature',
        'unit' => '°C',
        'expected_min' => -10,
        'expected_max' => 40,
    ]);
    $this->campaign = Campaign::factory()->create(['user_id' => $this->user->id]);
});

test('detects high GPS error', function () {
    $dataPoint = DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 25,
        'accuracy' => 75, // High GPS error
        'status' => 'pending',
    ]);

    $flags = $this->service->runQualityChecks($dataPoint);

    expect($flags)->toHaveCount(1)
        ->and($flags[0]['type'])->toBe('high_gps_error')
        ->and($flags[0]['severity'])->toBe('warning');
});

test('detects value outside expected range', function () {
    $dataPoint = DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 50, // Outside expected range (max 40)
        'accuracy' => 5,
        'status' => 'pending',
    ]);

    $flags = $this->service->runQualityChecks($dataPoint);

    expect($flags)->toHaveCount(1)
        ->and($flags[0]['type'])->toBe('unexpected_range')
        ->and($flags[0]['severity'])->toBe('warning');
});

test('detects statistical outliers using IQR method', function () {
    // Create baseline data (normal values around 20°C)
    for ($i = 0; $i < 15; $i++) {
        DataPoint::factory()->create([
            'campaign_id' => $this->campaign->id,
            'environmental_metric_id' => $this->metric->id,
            'user_id' => $this->user->id,
            'value' => 18 + ($i % 5), // Values between 18-22
            'accuracy' => 5,
            'status' => 'approved',
            'collected_at' => now()->subDays(rand(1, 20)),
        ]);
    }

    // Create outlier
    $outlier = DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 35, // Clear outlier
        'accuracy' => 5,
        'status' => 'pending',
        'collected_at' => now(),
    ]);

    $flags = $this->service->runQualityChecks($outlier);

    $hasOutlierFlag = collect($flags)->contains('type', 'statistical_outlier');
    expect($hasOutlierFlag)->toBeTrue();
});

test('passes clean data point with no issues', function () {
    $dataPoint = DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 25, // Within expected range
        'accuracy' => 5, // Good GPS accuracy
        'status' => 'pending',
    ]);

    $flags = $this->service->runQualityChecks($dataPoint);

    expect($flags)->toBeEmpty();
});

test('gets campaign quality statistics', function () {
    DataPoint::factory()->count(10)->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'status' => 'approved',
    ]);

    DataPoint::factory()->count(5)->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'status' => 'rejected',
    ]);

    DataPoint::factory()->count(3)->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'status' => 'pending',
    ]);

    $stats = $this->service->getCampaignQualityStats($this->campaign);

    expect($stats['total'])->toBe(18)
        ->and($stats['approved'])->toBe(10)
        ->and($stats['rejected'])->toBe(5)
        ->and($stats['pending'])->toBe(3)
        ->and($stats['approval_rate'])->toBe(55.6);
});

test('gets user contribution statistics', function () {
    $user1 = User::factory()->create(['name' => 'Top Contributor']);
    $user2 = User::factory()->create(['name' => 'Second Place']);

    // User 1: 10 submissions, 9 approved
    DataPoint::factory()->count(9)->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $user1->id,
        'status' => 'approved',
        'accuracy' => 5,
    ]);
    DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $user1->id,
        'status' => 'rejected',
        'accuracy' => 50,
    ]);

    // User 2: 5 submissions, all approved
    DataPoint::factory()->count(5)->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $user2->id,
        'status' => 'approved',
        'accuracy' => 8,
    ]);

    $stats = $this->service->getUserContributionStats(30);

    expect($stats)->toHaveCount(2)
        ->and($stats->first()->name)->toBe('Top Contributor')
        ->and($stats->first()->total_submissions)->toBe(10)
        ->and($stats->first()->approval_rate)->toBe(90.0);
});

test('auto-approves qualified data points', function () {
    // High quality data points (accuracy <= 10m, no flags)
    DataPoint::factory()->count(5)->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 25,
        'accuracy' => 5,
        'status' => 'pending',
        'qa_flags' => null,
    ]);

    // Poor quality (should not be auto-approved)
    DataPoint::factory()->count(3)->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 25,
        'accuracy' => 50, // Poor GPS
        'status' => 'pending',
        'qa_flags' => null,
    ]);

    $approved = $this->service->autoApproveQualified();

    expect($approved)->toBe(5);
    expect(DataPoint::where('status', 'approved')->count())->toBe(5);
    expect(DataPoint::where('status', 'pending')->count())->toBe(3);
});

test('flags suspicious readings', function () {
    // Create data point with high GPS error
    DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 25,
        'accuracy' => 75,
        'status' => 'pending',
        'qa_flags' => null,
    ]);

    // Create data point outside expected range
    DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => -50, // Way outside range
        'accuracy' => 5,
        'status' => 'pending',
        'qa_flags' => null,
    ]);

    $flagged = $this->service->flagSuspiciousReadings();

    expect($flagged)->toBe(2);
    expect(DataPoint::whereNotNull('qa_flags')->count())->toBe(2);
});
