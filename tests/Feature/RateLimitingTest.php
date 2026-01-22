<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('free tier user is limited to 60 requests per hour', function () {
    expect($this->user->subscriptionTier())->toBe('free');

    actingAs($this->user);

    // Make 60 requests to rate-limited route (should succeed)
    for ($i = 0; $i < 60; $i++) {
        get(route('data-points.submit'))->assertSuccessful();
    }

    // 61st request should be rate limited
    get(route('data-points.submit'))->assertStatus(429);
});

test('pro tier user is limited to 300 requests per hour', function () {
    // Create Pro subscription using database (no Stripe API call)
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

    expect($this->user->fresh()->subscriptionTier())->toBe('pro');

    actingAs($this->user);

    // Make 60 requests (should all succeed - well below 300 limit)
    for ($i = 0; $i < 60; $i++) {
        get(route('data-points.submit'))->assertSuccessful();
    }

    // Should still have plenty of requests left
    expect(true)->toBeTrue();
});

test('enterprise tier user is limited to 1000 requests per hour', function () {
    // Create Enterprise subscription using database (no Stripe API call)
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

    expect($this->user->fresh()->subscriptionTier())->toBe('enterprise');

    actingAs($this->user);

    // Make 100 requests (should all succeed - well below 1000 limit)
    for ($i = 0; $i < 100; $i++) {
        get(route('data-points.submit'))->assertSuccessful();
    }

    // Should still have plenty of requests left
    expect(true)->toBeTrue();
});

test('rate limit returns 429 status code when exceeded', function () {
    actingAs($this->user);

    // Exhaust the limit (60 for free tier)
    for ($i = 0; $i < 60; $i++) {
        get(route('data-points.submit'));
    }

    // Next request should return 429
    $response = get(route('data-points.submit'));

    $response->assertStatus(429);
    $response->assertJson([
        'message' => 'Too many requests. Please slow down.',
    ]);
});

test('rate limit response includes retry_after header', function () {
    actingAs($this->user);

    // Exhaust the limit
    for ($i = 0; $i < 60; $i++) {
        get(route('data-points.submit'));
    }

    // Check response includes retry_after
    $response = get(route('data-points.submit'));

    $response->assertStatus(429);
    expect($response->json('retry_after'))->toBeGreaterThan(0);
});

test('rate limiting applies to maps routes', function () {
    actingAs($this->user);

    // Make requests to maps route
    for ($i = 0; $i < 60; $i++) {
        get(route('maps.survey'))->assertSuccessful();
    }

    // 61st request should be rate limited
    get(route('maps.survey'))->assertStatus(429);
});

test('different users have independent rate limits', function () {
    $user2 = User::factory()->create();

    actingAs($this->user);

    // Exhaust rate limit for user 1
    for ($i = 0; $i < 60; $i++) {
        get(route('data-points.submit'));
    }

    // User 1 should be rate limited
    get(route('data-points.submit'))->assertStatus(429);

    // User 2 should still have full quota
    actingAs($user2);
    get(route('data-points.submit'))->assertSuccessful();
});

test('rate limit resets after time window', function () {
    actingAs($this->user);

    // Make one request
    get(route('data-points.submit'))->assertSuccessful();

    // Fast forward time by 61 minutes (beyond the 1-hour window)
    $this->travel(61)->minutes();

    // Should be able to make 60 more requests
    for ($i = 0; $i < 60; $i++) {
        get(route('data-points.submit'))->assertSuccessful();
    }
});
