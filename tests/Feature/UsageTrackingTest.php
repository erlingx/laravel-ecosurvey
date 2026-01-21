<?php

use App\Models\User;
use App\Services\UsageTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->service = new UsageTrackingService;
});
test('records data point creation', function () {
    $user = User::factory()->create();
    $result = $this->service->recordDataPointCreation($user);
    expect($result)->toBeTrue();
    $usage = $this->service->getCurrentUsage($user);
    expect($usage['data_points'])->toBe(1);
});
test('records satellite analysis', function () {
    $user = User::factory()->create();
    $result = $this->service->recordSatelliteAnalysis($user, 'ndvi');
    expect($result)->toBeTrue();
    $usage = $this->service->getCurrentUsage($user);
    expect($usage['satellite_analyses'])->toBe(1);
});
test('records report export', function () {
    $user = User::factory()->create();
    $result = $this->service->recordReportExport($user, 'pdf');
    expect($result)->toBeTrue();
    $usage = $this->service->getCurrentUsage($user);
    expect($usage['report_exports'])->toBe(1);
});
test('calculates current usage correctly', function () {
    $user = User::factory()->create();
    $this->service->recordDataPointCreation($user);
    $this->service->recordDataPointCreation($user);
    $this->service->recordSatelliteAnalysis($user, 'ndvi');
    $this->service->recordReportExport($user, 'pdf');
    $usage = $this->service->getCurrentUsage($user);
    expect($usage['data_points'])->toBe(2);
    expect($usage['satellite_analyses'])->toBe(1);
    expect($usage['report_exports'])->toBe(1);
    expect($usage['cycle_start'])->toBeInstanceOf(\Carbon\Carbon::class);
    expect($usage['cycle_end'])->toBeInstanceOf(\Carbon\Carbon::class);
});
test('enforces free tier limits', function () {
    $user = User::factory()->create();
    // Record 50 data points (free tier limit)
    for ($i = 0; $i < 50; $i++) {
        $this->service->recordDataPointCreation($user);
    }
    expect($this->service->canPerformAction($user, 'data_points'))->toBeFalse();
    expect($this->service->getRemainingQuota($user, 'data_points'))->toBe(0);
});
test('allows usage under free tier limit', function () {
    $user = User::factory()->create();
    // Record 45 data points (under free tier limit of 50)
    for ($i = 0; $i < 45; $i++) {
        $this->service->recordDataPointCreation($user);
    }
    expect($this->service->canPerformAction($user, 'data_points'))->toBeTrue();
    expect($this->service->getRemainingQuota($user, 'data_points'))->toBe(5);
});
test('pro tier has higher limits', function () {
    $user = User::factory()->create();
    // Create a Pro subscription
    $user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_pro',
        'stripe_status' => 'active',
        'stripe_price' => config('subscriptions.plans.pro.stripe_price_id'),
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
    ]);
    DB::table('subscription_items')->insert([
        'subscription_id' => $user->subscriptions()->first()->id,
        'stripe_id' => 'si_test_pro',
        'stripe_product' => 'prod_test',
        'stripe_price' => config('subscriptions.plans.pro.stripe_price_id'),
        'quantity' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    // Record 100 data points (under Pro limit of 500)
    for ($i = 0; $i < 100; $i++) {
        $this->service->recordDataPointCreation($user);
    }
    expect($this->service->canPerformAction($user, 'data_points'))->toBeTrue();
    expect($this->service->getRemainingQuota($user, 'data_points'))->toBe(400);
});
test('enterprise tier has unlimited limits', function () {
    $user = User::factory()->create();
    // Create an Enterprise subscription
    $user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_enterprise',
        'stripe_status' => 'active',
        'stripe_price' => config('subscriptions.plans.enterprise.stripe_price_id'),
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
    ]);
    DB::table('subscription_items')->insert([
        'subscription_id' => $user->subscriptions()->first()->id,
        'stripe_id' => 'si_test_enterprise',
        'stripe_product' => 'prod_test',
        'stripe_price' => config('subscriptions.plans.enterprise.stripe_price_id'),
        'quantity' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    // Record 1000 data points
    for ($i = 0; $i < 1000; $i++) {
        $this->service->recordDataPointCreation($user);
    }
    expect($this->service->canPerformAction($user, 'data_points'))->toBeTrue();
    expect($this->service->getRemainingQuota($user, 'data_points'))->toBe(PHP_INT_MAX);
});
test('usage is tracked per billing cycle', function () {
    $user = User::factory()->create();
    $this->service->recordDataPointCreation($user);
    $cycleStart = $this->service->getBillingCycleStart($user);
    $cycleEnd = $this->service->getBillingCycleEnd($user);
    expect($cycleStart)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($cycleEnd)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($cycleEnd->greaterThan($cycleStart))->toBeTrue();
});
test('can reset usage for testing', function () {
    $user = User::factory()->create();
    $this->service->recordDataPointCreation($user);
    $this->service->recordSatelliteAnalysis($user, 'ndvi');
    $this->service->resetUsage($user);
    $usage = $this->service->getCurrentUsage($user);
    expect($usage['data_points'])->toBe(0);
    expect($usage['satellite_analyses'])->toBe(0);
});
test('can reset usage for specific resource', function () {
    $user = User::factory()->create();
    $this->service->recordDataPointCreation($user);
    $this->service->recordSatelliteAnalysis($user, 'ndvi');
    $this->service->resetUsage($user, 'data_points');
    $usage = $this->service->getCurrentUsage($user);
    expect($usage['data_points'])->toBe(0);
    expect($usage['satellite_analyses'])->toBe(1);
});
test('usage is cached for performance', function () {
    $user = User::factory()->create();
    $this->service->recordDataPointCreation($user);
    // First call hits database
    $usage1 = $this->service->getCurrentUsage($user);
    // Second call should hit cache
    $usage2 = $this->service->getCurrentUsage($user);
    expect($usage1['data_points'])->toBe($usage2['data_points']);
});
