<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('manage page shows cancel button for subscribed users', function () {
    // Create fake subscription directly in database
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
        ->assertSee('Cancel Subscription');
});

test('free tier users do not see cancel button', function () {
    $response = actingAs($this->user)
        ->get(route('billing.manage'));

    $response->assertSee('Upgrade Plan');
    $response->assertSee('Free Plan');
    $response->assertDontSee('Subscription Actions');
});

test('user can cancel subscription at end of period', function () {
    // Create fake subscription
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

    expect($this->user->subscribed('default'))->toBeTrue();
    expect($this->user->subscription('default')->onGracePeriod())->toBeFalse();

    // Simulate cancellation by setting ends_at
    $subscription->update(['ends_at' => now()->addDays(30)]);

    $this->user->refresh();

    expect($this->user->subscribed('default'))->toBeTrue();
    expect($this->user->subscription('default')->onGracePeriod())->toBeTrue();
    expect($this->user->subscription('default')->ends_at)->not->toBeNull();
});

test('user can cancel subscription immediately', function () {
    // Create fake subscription
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

    expect($this->user->subscribed('default'))->toBeTrue();
    expect($this->user->subscriptionTier())->toBe('pro');

    // Simulate immediate cancellation by deleting subscription
    $subscription->items()->delete();
    $subscription->delete();

    $this->user->refresh();

    expect($this->user->subscribed('default'))->toBeFalse();
    expect($this->user->subscriptionTier())->toBe('free');
});

test('user on grace period sees resume button', function () {
    // Create cancelled subscription (grace period)
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

    expect($this->user->subscription('default')->onGracePeriod())->toBeTrue();

    actingAs($this->user)
        ->get(route('billing.manage'))
        ->assertSee('Resume Subscription');
});

test('user can resume cancelled subscription', function () {
    // Create cancelled subscription
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

    // Resume by removing ends_at
    $subscription->update(['ends_at' => null]);

    $this->user->refresh();

    expect($this->user->subscription('default')->onGracePeriod())->toBeFalse();
    expect($this->user->subscription('default')->ends_at)->toBeNull();
});

test('manage page displays billing history section', function () {
    // Create subscription
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
        ->assertSee('Billing History');
});

test('manage page shows current tier and price', function () {
    // Create Pro subscription
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
        ->assertSee('Pro Plan')
        ->assertSee('$29/month');
});
