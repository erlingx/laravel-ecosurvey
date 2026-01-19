<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\EnvironmentalMetric;
use App\Models\SatelliteAnalysis;
use App\Models\SurveyZone;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('data point belongs to campaign', function () {
    $campaign = Campaign::factory()->create();
    $dataPoint = DataPoint::factory()->create(['campaign_id' => $campaign->id]);

    expect($dataPoint->campaign)->toBeInstanceOf(Campaign::class)
        ->and($dataPoint->campaign->id)->toBe($campaign->id);
});

test('data point belongs to environmental metric', function () {
    $metric = EnvironmentalMetric::factory()->create();
    $dataPoint = DataPoint::factory()->create(['environmental_metric_id' => $metric->id]);

    expect($dataPoint->environmentalMetric)->toBeInstanceOf(EnvironmentalMetric::class)
        ->and($dataPoint->environmentalMetric->id)->toBe($metric->id);
});

test('data point belongs to user', function () {
    $user = User::factory()->create();
    $dataPoint = DataPoint::factory()->create(['user_id' => $user->id]);

    expect($dataPoint->user)->toBeInstanceOf(User::class)
        ->and($dataPoint->user->id)->toBe($user->id);
});

test('data point can belong to survey zone', function () {
    $zone = SurveyZone::factory()->create();
    $dataPoint = DataPoint::factory()->create(['survey_zone_id' => $zone->id]);

    expect($dataPoint->surveyZone)->toBeInstanceOf(SurveyZone::class)
        ->and($dataPoint->surveyZone->id)->toBe($zone->id);
});

test('data point survey zone relationship is nullable', function () {
    $dataPoint = DataPoint::factory()->create(['survey_zone_id' => null]);

    expect($dataPoint->surveyZone)->toBeNull();
});

test('data point can have reviewer', function () {
    $reviewer = User::factory()->create();
    $dataPoint = DataPoint::factory()->create([
        'status' => 'approved',
        'reviewed_by' => $reviewer->id,
    ]);

    expect($dataPoint->reviewer)->toBeInstanceOf(User::class)
        ->and($dataPoint->reviewer->id)->toBe($reviewer->id);
});

test('data point reviewer relationship is nullable', function () {
    $dataPoint = DataPoint::factory()->create([
        'status' => 'pending',
        'reviewed_by' => null,
    ]);

    expect($dataPoint->reviewer)->toBeNull();
});

test('data point has many satellite analyses', function () {
    Queue::fake(); // Prevent automatic satellite analysis creation

    $dataPoint = DataPoint::factory()->create();
    SatelliteAnalysis::factory()->count(3)->create(['data_point_id' => $dataPoint->id]);

    expect($dataPoint->satelliteAnalyses)->toHaveCount(3);
    $dataPoint->satelliteAnalyses->each(function ($analysis) {
        expect($analysis)->toBeInstanceOf(SatelliteAnalysis::class);
    });
});

test('data point satellite analyses relationship is empty when no analyses exist', function () {
    Queue::fake(); // Prevent automatic satellite analysis creation

    $dataPoint = DataPoint::factory()->create();

    expect($dataPoint->satelliteAnalyses)->toBeEmpty();
});

test('data point can eager load all relationships', function () {
    Queue::fake(); // Prevent automatic satellite analysis creation

    $campaign = Campaign::factory()->create();
    $metric = EnvironmentalMetric::factory()->create();
    $user = User::factory()->create();
    $reviewer = User::factory()->create();
    $zone = SurveyZone::factory()->create(['campaign_id' => $campaign->id]);

    $dataPoint = DataPoint::factory()->create([
        'campaign_id' => $campaign->id,
        'environmental_metric_id' => $metric->id,
        'user_id' => $user->id,
        'survey_zone_id' => $zone->id,
        'reviewed_by' => $reviewer->id,
        'status' => 'approved',
    ]);

    SatelliteAnalysis::factory()->count(2)->create(['data_point_id' => $dataPoint->id]);

    // Eager load all relationships
    $loaded = DataPoint::with([
        'campaign',
        'environmentalMetric',
        'user',
        'surveyZone',
        'reviewer',
        'satelliteAnalyses',
    ])->find($dataPoint->id);

    expect($loaded->relationLoaded('campaign'))->toBeTrue()
        ->and($loaded->relationLoaded('environmentalMetric'))->toBeTrue()
        ->and($loaded->relationLoaded('user'))->toBeTrue()
        ->and($loaded->relationLoaded('surveyZone'))->toBeTrue()
        ->and($loaded->relationLoaded('reviewer'))->toBeTrue()
        ->and($loaded->relationLoaded('satelliteAnalyses'))->toBeTrue()
        ->and($loaded->campaign)->not->toBeNull()
        ->and($loaded->environmentalMetric)->not->toBeNull()
        ->and($loaded->user)->not->toBeNull()
        ->and($loaded->surveyZone)->not->toBeNull()
        ->and($loaded->reviewer)->not->toBeNull()
        ->and($loaded->satelliteAnalyses)->toHaveCount(2);
});

test('high quality scope returns only approved data points with accuracy under 50m', function () {
    // Create high quality data points
    DataPoint::factory()->create(['status' => 'approved', 'accuracy' => 20]);
    DataPoint::factory()->create(['status' => 'approved', 'accuracy' => 45]);

    // Create data points that don't meet criteria
    DataPoint::factory()->create(['status' => 'pending', 'accuracy' => 20]); // Not approved
    DataPoint::factory()->create(['status' => 'approved', 'accuracy' => 80]); // High accuracy
    DataPoint::factory()->create(['status' => 'rejected', 'accuracy' => 30]); // Rejected (valid status)

    $highQuality = DataPoint::highQuality()->get();

    expect($highQuality)->toHaveCount(2);

    $highQuality->each(function ($dp) {
        expect($dp->status)->toBe('approved')
            ->and($dp->accuracy)->toBeLessThanOrEqual(50);
    });
});
