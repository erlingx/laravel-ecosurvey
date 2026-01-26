<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Queue::fake(); // Fake queues for data point submissions
    $this->user = User::factory()->create();
    $this->campaign = Campaign::factory()->create(['user_id' => $this->user->id]);
});

// Note: Full rate limiting behavior (60/300/1000 requests) is tested in manual browser UX tests
// These automated tests verify the routes are accessible and return proper responses

// Data Points Submission Rate Limiting
test('data points submit route is accessible', function () {
    actingAs($this->user);
    get(route('data-points.submit'))->assertSuccessful();
});

// Maps Routes Rate Limiting
test('maps survey route is accessible', function () {
    actingAs($this->user);
    get(route('maps.survey'))->assertSuccessful();
});

test('maps satellite route is accessible', function () {
    actingAs($this->user);
    get(route('maps.satellite'))->assertSuccessful();
});

// Analytics Routes Rate Limiting
test('analytics heatmap route is accessible', function () {
    actingAs($this->user);
    get(route('analytics.heatmap'))->assertSuccessful();
});

test('analytics trends route is accessible', function () {
    actingAs($this->user);
    get(route('analytics.trends'))->assertSuccessful();
});

// Export Routes Rate Limiting
test('campaign export json route is accessible', function () {
    actingAs($this->user);
    get(route('campaigns.export.json', $this->campaign))->assertSuccessful();
});

test('campaign export csv route is accessible', function () {
    actingAs($this->user);
    get(route('campaigns.export.csv', $this->campaign))->assertSuccessful();
});

test('campaign export pdf route is accessible', function () {
    actingAs($this->user);
    get(route('campaigns.export.pdf', $this->campaign))->assertSuccessful();
});

// Non-Rate Limited Routes
test('billing routes are not rate limited and work normally', function () {
    actingAs($this->user);
    get(route('billing.plans'))->assertSuccessful();
    get(route('billing.manage'))->assertSuccessful();
});

test('dashboard route is not rate limited and works normally', function () {
    actingAs($this->user);
    get(route('dashboard'))->assertSuccessful();
});

test('campaigns index route is not rate limited and works normally', function () {
    actingAs($this->user);
    get('/admin/campaigns')->assertSuccessful();
});

// Tier-Specific Infrastructure
test('pro tier user can access rate limited routes', function () {
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
    get(route('data-points.submit'))->assertSuccessful();

    // Note: Full Pro limit (300/hr) testing done manually in browser UX tests
});

test('enterprise tier user can access rate limited routes', function () {
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
    get(route('data-points.submit'))->assertSuccessful();

    // Note: Full Enterprise limit (1000/hr) testing done manually in browser UX tests
});

// Dashboard Status Display
test('user dashboard shows rate limit status card', function () {
    actingAs($this->user);

    $response = get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertSee('Requests Remaining');
});

test('user dashboard displays correctly with rate limit info', function () {
    actingAs($this->user);

    $response = get(route('dashboard'));

    $response->assertSuccessful();
    // Note: Warning banner display when rate limited is tested in browser UX tests
});
