<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Queue::fake();
    $this->user = User::factory()->create();
});

// Subscription Cancellation UI Tests
test('free tier user sees upgrade button on manage page', function () {
    actingAs($this->user)
        ->get(route('billing.manage'))
        ->assertSee('Upgrade Plan')
        ->assertSee('Free Plan');

    // Free tier users don't have subscription section
    expect($this->user->subscribed('default'))->toBeFalse();
});

test('subscribed user sees cancel button', function () {
    $priceId = config('subscriptions.plans.pro.stripe_price_id');

    $subscription = $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => $priceId,
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
    ]);

    $subscription->items()->create([
        'stripe_id' => 'si_test_'.uniqid(),
        'stripe_product' => 'prod_test',
        'stripe_price' => $priceId,
        'quantity' => 1,
    ]);

    actingAs($this->user)
        ->get(route('billing.manage'))
        ->assertSee('Update Payment Method')
        ->assertSee('Subscription Actions');
});

test('cancelled subscription shows resume button', function () {
    $priceId = config('subscriptions.plans.pro.stripe_price_id');

    $subscription = $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => $priceId,
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => now()->addDays(30), // Grace period
    ]);

    $subscription->items()->create([
        'stripe_id' => 'si_test_'.uniqid(),
        'stripe_product' => 'prod_test',
        'stripe_price' => $priceId,
        'quantity' => 1,
    ]);

    actingAs($this->user)
        ->get(route('billing.manage'))
        ->assertSee('Resume Subscription');

    // Verify grace period state
    expect($this->user->subscription('default')->onGracePeriod())->toBeTrue();
});

test('grace period shows cancelled status with end date', function () {
    $priceId = config('subscriptions.plans.pro.stripe_price_id');
    $endsAt = now()->addDays(30);

    $subscription = $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => $priceId,
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => $endsAt,
    ]);

    $subscription->items()->create([
        'stripe_id' => 'si_test_'.uniqid(),
        'stripe_product' => 'prod_test',
        'stripe_price' => $priceId,
        'quantity' => 1,
    ]);

    expect($this->user->subscription('default')->onGracePeriod())->toBeTrue();

    actingAs($this->user)
        ->get(route('billing.manage'))
        ->assertSee('Cancelled - Access until');
});

test('user on grace period retains tier features', function () {
    $priceId = config('subscriptions.plans.pro.stripe_price_id');

    $subscription = $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => $priceId,
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => now()->addDays(30),
    ]);

    $subscription->items()->create([
        'stripe_id' => 'si_test_'.uniqid(),
        'stripe_product' => 'prod_test',
        'stripe_price' => $priceId,
        'quantity' => 1,
    ]);

    $user = $this->user->fresh();

    // User should still have Pro tier access during grace period
    expect($user->subscriptionTier())->toBe('pro');
    expect($user->subscription('default')->onGracePeriod())->toBeTrue();
});

test('user on grace period has pro rate limits', function () {
    $priceId = config('subscriptions.plans.pro.stripe_price_id');

    $subscription = $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => $priceId,
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => now()->addDays(30),
    ]);

    $subscription->items()->create([
        'stripe_id' => 'si_test_'.uniqid(),
        'stripe_product' => 'prod_test',
        'stripe_price' => $priceId,
        'quantity' => 1,
    ]);

    actingAs($this->user);

    // Should be able to make more than 60 requests (Pro limit is 300)
    for ($i = 0; $i < 100; $i++) {
        get(route('data-points.submit'))->assertSuccessful();
    }

    expect($this->user->fresh()->subscriptionTier())->toBe('pro');
});

test('resuming subscription removes grace period', function () {
    $priceId = config('subscriptions.plans.pro.stripe_price_id');

    $subscription = $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => $priceId,
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => now()->addDays(30),
    ]);

    $subscription->items()->create([
        'stripe_id' => 'si_test_'.uniqid(),
        'stripe_product' => 'prod_test',
        'stripe_price' => $priceId,
        'quantity' => 1,
    ]);

    expect($this->user->subscription('default')->onGracePeriod())->toBeTrue();

    // Resume subscription
    $subscription->update(['ends_at' => null]);

    $this->user->refresh();

    expect($this->user->subscription('default')->onGracePeriod())->toBeFalse();
    expect($this->user->subscriptionTier())->toBe('pro');
});

test('immediate cancellation downgrades to free tier', function () {
    $priceId = config('subscriptions.plans.pro.stripe_price_id');

    $subscription = $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => $priceId,
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
    ]);

    $subscription->items()->create([
        'stripe_id' => 'si_test_'.uniqid(),
        'stripe_product' => 'prod_test',
        'stripe_price' => $priceId,
        'quantity' => 1,
    ]);

    expect($this->user->subscriptionTier())->toBe('pro');

    // Immediate cancellation
    $subscription->items()->delete();
    $subscription->delete();

    $this->user->refresh();

    expect($this->user->subscriptionTier())->toBe('free');
    expect($this->user->subscribed('default'))->toBeFalse();
});

test('expired grace period downgrades to free tier', function () {
    $priceId = config('subscriptions.plans.pro.stripe_price_id');

    $subscription = $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => $priceId,
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => now()->subDay(), // Already expired
    ]);

    $subscription->items()->create([
        'stripe_id' => 'si_test_'.uniqid(),
        'stripe_product' => 'prod_test',
        'stripe_price' => $priceId,
        'quantity' => 1,
    ]);

    $user = $this->user->fresh();

    // After grace period ends, user should be free tier
    expect($user->subscriptionTier())->toBe('free');
    expect($user->subscription('default')->ended())->toBeTrue();
});

test('billing history section shows for subscribed users', function () {
    $priceId = config('subscriptions.plans.enterprise.stripe_price_id');

    $subscription = $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => $priceId,
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
    ]);

    $subscription->items()->create([
        'stripe_id' => 'si_test_'.uniqid(),
        'stripe_product' => 'prod_test',
        'stripe_price' => $priceId,
        'quantity' => 1,
    ]);

    actingAs($this->user)
        ->get(route('billing.manage'))
        ->assertSee('Billing History');
});

test('enterprise subscription displays correct tier and price', function () {
    $priceId = config('subscriptions.plans.enterprise.stripe_price_id');

    $subscription = $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => $priceId,
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
    ]);

    $subscription->items()->create([
        'stripe_id' => 'si_test_'.uniqid(),
        'stripe_product' => 'prod_test',
        'stripe_price' => $priceId,
        'quantity' => 1,
    ]);

    actingAs($this->user)
        ->get(route('billing.manage'))
        ->assertSee('Enterprise Plan')
        ->assertSee('$99/month');
});
