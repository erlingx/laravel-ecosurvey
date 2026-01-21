<?php

use App\Models\User;
use App\Services\UsageTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->usageService = app(UsageTrackingService::class);
});
test('usage dashboard loads successfully', function () {
    $response = $this->get(route('billing.usage'));
    $response->assertSuccessful();
    $response->assertSeeLivewire('billing.usage-dashboard');
    $response->assertSee('Usage Dashboard');
});
test('displays current usage for free tier', function () {
    $this->usageService->recordDataPointCreation($this->user);
    $this->usageService->recordDataPointCreation($this->user);
    $this->usageService->recordSatelliteAnalysis($this->user, 'ndvi');
    $response = $this->get(route('billing.usage'));
    $response->assertSee('Current Plan: Free');
});
test('shows upgrade button for free tier users', function () {
    $response = $this->get(route('billing.usage'));
    $response->assertSee('Upgrade to Pro');
});
test('shows manage button for pro tier users', function () {
    $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price' => config('subscriptions.plans.pro.stripe_price_id'),
        'quantity' => 1,
    ]);
    DB::table('subscription_items')->insert([
        'subscription_id' => $this->user->subscriptions()->first()->id,
        'stripe_id' => 'si_test',
        'stripe_product' => 'prod_test',
        'stripe_price' => config('subscriptions.plans.pro.stripe_price_id'),
        'quantity' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $response = $this->get(route('billing.usage'));
    $response->assertSee('Current Plan: Pro');
    $response->assertSee('Manage Plan');
});
test('displays percentage bars for usage', function () {
    for ($i = 0; $i < 25; $i++) {
        $this->usageService->recordDataPointCreation($this->user);
    }
    $response = $this->get(route('billing.usage'));
    $response->assertSee('50%');
});
test('shows warning when approaching limit', function () {
    for ($i = 0; $i < 45; $i++) {
        $this->usageService->recordDataPointCreation($this->user);
    }
    $response = $this->get(route('billing.usage'));
    $response->assertSee('approaching your limit');
});
test('shows upgrade CTA when free user is over 50% usage', function () {
    for ($i = 0; $i < 26; $i++) {
        $this->usageService->recordDataPointCreation($this->user);
    }
    $response = $this->get(route('billing.usage'));
    $response->assertSee('Running low on resources?');
});
test('shows unlimited for enterprise tier', function () {
    $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price' => config('subscriptions.plans.enterprise.stripe_price_id'),
        'quantity' => 1,
    ]);
    DB::table('subscription_items')->insert([
        'subscription_id' => $this->user->subscriptions()->first()->id,
        'stripe_id' => 'si_test',
        'stripe_product' => 'prod_test',
        'stripe_price' => config('subscriptions.plans.enterprise.stripe_price_id'),
        'quantity' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $response = $this->get(route('billing.usage'));
    $response->assertSee('Current Plan: Enterprise');
    $response->assertSee('Unlimited');
});
test('displays billing cycle information', function () {
    $response = $this->get(route('billing.usage'));
    $response->assertSee('Billing cycle:');
});
test('requires authentication', function () {
    auth()->logout();
    $response = $this->get(route('billing.usage'));
    $response->assertRedirect(route('login'));
});
