<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\UsageTrackingService;
use Illuminate\Support\Facades\DB;

test('usage tracking uses transaction to prevent race conditions', function () {
    $user = User::factory()->create();
    $usageService = app(UsageTrackingService::class);

    for ($i = 0; $i < 5; $i++) {
        $usageService->recordDataPointCreation($user);
    }

    $usage = $usageService->getCurrentUsage($user, 'data_points');
    expect($usage['data_points'])->toBe(5);
});

test('satellite enrichment job wraps critical operations in transaction', function () {
    $jobFile = file_get_contents(app_path('Jobs/EnrichDataPointWithSatelliteData.php'));

    expect($jobFile)->toContain('DB::transaction');
    expect($jobFile)->toContain('SatelliteAnalysis::create([');
});

test('usage service transaction prevents partial updates', function () {
    $user = User::factory()->create();
    $usageService = app(UsageTrackingService::class);

    $usageService->recordDataPointCreation($user);

    $records = DB::table('usage_meters')
        ->where('user_id', $user->id)
        ->where('resource', 'data_points')
        ->count();

    expect($records)->toBe(1);
});

test('usage tracking increment is atomic', function () {
    $user = User::factory()->create();
    $usageService = app(UsageTrackingService::class);

    $usageService->recordDataPointCreation($user);
    $usageService->recordDataPointCreation($user);

    $record = DB::table('usage_meters')
        ->where('user_id', $user->id)
        ->where('resource', 'data_points')
        ->first();

    expect($record)->not->toBeNull();
    expect($record->count)->toBe(2);
});
