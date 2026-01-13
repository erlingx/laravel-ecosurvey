<?php

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\EnvironmentalMetric;
use App\Models\User;

test('can approve a data point', function () {
    $reviewer = User::factory()->create();
    $dataPoint = DataPoint::factory()->create(['status' => 'pending']);

    $dataPoint->approve($reviewer, 'Verified accuracy');

    expect($dataPoint->fresh())
        ->status->toBe('approved')
        ->reviewed_by->toBe($reviewer->id)
        ->reviewed_at->not->toBeNull()
        ->review_notes->toBe('Verified accuracy')
        ->isApproved()->toBeTrue()
        ->isPending()->toBeFalse();
});

test('can approve a flagged data point after manual review', function () {
    $reviewer = User::factory()->create();
    $dataPoint = DataPoint::factory()->create([
        'status' => 'pending',
        'qa_flags' => [
            ['type' => 'outlier', 'reason' => 'Value exceeds 3 standard deviations', 'flagged_at' => now()],
        ],
    ]);

    expect($dataPoint->isFlagged())->toBeTrue();

    $dataPoint->approve($reviewer, 'Checked against official station - value correct');

    expect($dataPoint->fresh())
        ->status->toBe('approved')
        ->isFlagged()->toBeTrue() // Flags remain for audit trail
        ->review_notes->toBe('Checked against official station - value correct');
});

test('can reject a data point', function () {
    $reviewer = User::factory()->create();
    $dataPoint = DataPoint::factory()->create(['status' => 'pending']);

    $dataPoint->reject($reviewer, 'Sensor malfunction detected');

    expect($dataPoint->fresh())
        ->status->toBe('rejected')
        ->reviewed_by->toBe($reviewer->id)
        ->reviewed_at->not->toBeNull()
        ->review_notes->toBe('Sensor malfunction detected')
        ->isRejected()->toBeTrue()
        ->isPending()->toBeFalse();
});

test('can reset data point to pending review', function () {
    $reviewer = User::factory()->create();
    $dataPoint = DataPoint::factory()->create([
        'status' => 'approved',
        'reviewed_by' => $reviewer->id,
        'reviewed_at' => now(),
        'review_notes' => 'Initial approval',
    ]);

    $dataPoint->resetToReview();

    expect($dataPoint->fresh())
        ->status->toBe('pending')
        ->reviewed_by->toBeNull()
        ->reviewed_at->toBeNull()
        ->review_notes->toBeNull()
        ->isPending()->toBeTrue();
});

test('can clear qa flags after resolution', function () {
    $dataPoint = DataPoint::factory()->create([
        'qa_flags' => [
            ['type' => 'outlier', 'reason' => 'Suspicious value', 'flagged_at' => now()],
        ],
    ]);

    expect($dataPoint->isFlagged())->toBeTrue();

    $dataPoint->clearFlags();

    expect($dataPoint->fresh())
        ->qa_flags->toBeNull()
        ->isFlagged()->toBeFalse();
});

test('flag as outlier adds to existing flags', function () {
    $dataPoint = DataPoint::factory()->create([
        'qa_flags' => [
            ['type' => 'outlier', 'reason' => 'First flag', 'flagged_at' => now()->subDay()],
        ],
    ]);

    $dataPoint->flagAsOutlier('Second flag');

    $flags = $dataPoint->fresh()->qa_flags;
    expect($flags)
        ->toHaveCount(2)
        ->and($flags[0]['reason'])->toBe('First flag')
        ->and($flags[1]['reason'])->toBe('Second flag');
});

test('reviewer relationship is accessible', function () {
    $reviewer = User::factory()->create(['name' => 'Dr. Jane Smith']);
    $dataPoint = DataPoint::factory()->create();

    $dataPoint->approve($reviewer, 'Looks good');

    expect($dataPoint->fresh()->reviewer)
        ->toBeInstanceOf(User::class)
        ->name->toBe('Dr. Jane Smith');
});

test('scope high quality filters approved and accurate data', function () {
    DataPoint::factory()->create(['status' => 'approved', 'accuracy' => 10.0]);
    DataPoint::factory()->create(['status' => 'approved', 'accuracy' => 50.0]);
    DataPoint::factory()->create(['status' => 'approved', 'accuracy' => 100.0]); // Too inaccurate
    DataPoint::factory()->create(['status' => 'pending', 'accuracy' => 10.0]); // Not approved

    $highQuality = DataPoint::highQuality()->get();

    expect($highQuality)->toHaveCount(2);

    foreach ($highQuality as $point) {
        expect($point->accuracy)->toBeLessThanOrEqual(50);
    }
});

test('approval workflow example with flagged data', function () {
    $campaign = Campaign::factory()->create();
    $metric = EnvironmentalMetric::factory()->create();
    $reviewer = User::factory()->create(['name' => 'QA Manager']);

    // Data point with suspicious value gets flagged
    $dataPoint = DataPoint::factory()->create([
        'campaign_id' => $campaign->id,
        'environmental_metric_id' => $metric->id,
        'status' => 'pending',
        'value' => 45.5, // Unusually high temperature
    ]);

    $dataPoint->flagAsOutlier('Value exceeds 3σ from mean');

    // Reviewer investigates and approves
    $dataPoint->approve($reviewer, 'Cross-checked with nearby official station. Heat island effect confirmed.');

    expect($dataPoint->fresh())
        ->status->toBe('approved')
        ->isFlagged()->toBeTrue() // Flag preserved for audit
        ->review_notes->toContain('Heat island effect confirmed');
});
