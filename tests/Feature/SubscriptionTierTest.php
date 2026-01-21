<?php

use App\Models\User;

test('user subscription tier defaults to free', function () {
    $user = User::factory()->create();
    expect($user->subscriptionTier())->toBe('free');
});
test('user can check subscription tier', function () {
    $user = User::factory()->create();
    expect($user->hasActivePlan('free'))->toBeTrue();
    expect($user->hasActivePlan('pro'))->toBeFalse();
    expect($user->hasActivePlan('enterprise'))->toBeFalse();
});
test('free tier has usage limits', function () {
    $user = User::factory()->create();
    expect($user->getUsageLimit('data_points'))->toBe(50);
    expect($user->getUsageLimit('satellite_analyses'))->toBe(10);
    expect($user->getUsageLimit('report_exports'))->toBe(2);
});
test('pro tier has higher limits', function () {
    $user = User::factory()->create();
    // Mock pro subscription by setting stripe_price to pro price ID
    // This will be properly tested when checkout is implemented
    // For now, verify config exists
    $proLimits = config('subscriptions.plans.pro.limits');
    expect($proLimits['data_points'])->toBe(500);
    expect($proLimits['satellite_analyses'])->toBe(100);
    expect($proLimits['report_exports'])->toBe(20);
});
test('enterprise tier has unlimited limits', function () {
    $enterpriseLimits = config('subscriptions.plans.enterprise.limits');
    expect($enterpriseLimits['data_points'])->toBe(PHP_INT_MAX);
    expect($enterpriseLimits['satellite_analyses'])->toBe(PHP_INT_MAX);
    expect($enterpriseLimits['report_exports'])->toBe(PHP_INT_MAX);
});
test('user can create data point', function () {
    $user = User::factory()->create();
    // Should return true for now (usage tracking not implemented yet)
    expect($user->canCreateDataPoint())->toBeTrue();
});
test('user can run satellite analysis', function () {
    $user = User::factory()->create();
    // Should return true for now (usage tracking not implemented yet)
    expect($user->canRunSatelliteAnalysis())->toBeTrue();
});
test('subscriptions config file exists and has correct structure', function () {
    expect(config('subscriptions.plans'))->toBeArray();
    expect(config('subscriptions.plans.free'))->toBeArray();
    expect(config('subscriptions.plans.pro'))->toBeArray();
    expect(config('subscriptions.plans.enterprise'))->toBeArray();
    // Check required keys
    expect(config('subscriptions.plans.free'))->toHaveKeys(['name', 'price', 'limits', 'features']);
    expect(config('subscriptions.plans.pro'))->toHaveKeys(['name', 'price', 'stripe_price_id', 'limits', 'features']);
    expect(config('subscriptions.plans.enterprise'))->toHaveKeys(['name', 'price', 'stripe_price_id', 'limits', 'features']);
});
test('rate limits are configured per tier', function () {
    expect(config('subscriptions.rate_limits.free'))->toBe(60);
    expect(config('subscriptions.rate_limits.pro'))->toBe(300);
    expect(config('subscriptions.rate_limits.enterprise'))->toBe(1000);
});
