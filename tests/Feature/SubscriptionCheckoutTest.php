<?php

use App\Models\User;
use Livewire\Volt\Volt;

test('displays subscription plans page', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get(route('billing.plans'));
    $response->assertOk();
    $response->assertSeeLivewire('billing.subscription-plans');
    $response->assertSee('Choose Your Plan');
    $response->assertSee('Free');
    $response->assertSee('Pro');
    $response->assertSee('Enterprise');
});
test('shows pricing for all tiers', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get(route('billing.plans'));
    $response->assertSee('$0');
    $response->assertSee('$29');
    $response->assertSee('$99');
});
test('shows features for each plan', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Volt::test('billing.subscription-plans')
        ->assertSee('Basic maps')
        ->assertSee('All maps and visualization')
        ->assertSee('Unlimited everything');
});
test('shows current plan badge', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Volt::test('billing.subscription-plans')
        ->assertSee('Current Plan');
});
test('can navigate to checkout page', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get(route('billing.checkout', ['plan' => 'pro']));
    $response->assertOk();
    $response->assertSeeLivewire('billing.checkout');
});
test('checkout page shows plan details', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Volt::test('billing.checkout', ['plan' => 'pro'])
        ->assertSee('Subscribe to Pro')
        ->assertSee('$29')
        ->assertSee('Continue to Stripe Checkout');
});
test('invalid plan redirects to plans page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Volt::test('billing.checkout', ['plan' => 'invalid'])
        ->assertRedirect(route('billing.plans'));
});
test('displays success page after checkout', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get(route('billing.success'));
    $response->assertOk();
    $response->assertSeeLivewire('billing.success');
    $response->assertSee('Subscription Activated!');
});
test('displays cancel page after cancelled checkout', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get(route('billing.cancel'));
    $response->assertOk();
    $response->assertSeeLivewire('billing.cancel');
    $response->assertSee('Checkout Cancelled');
});
test('manage subscription page loads', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get(route('billing.manage'));
    $response->assertOk();
    $response->assertSeeLivewire('billing.manage');
    $response->assertSee('Manage Subscription');
});
test('manage page shows current tier', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Volt::test('billing.manage')
        ->assertSee('Free Plan');
});
test('requires authentication to access billing pages', function () {
    $response = $this->get(route('billing.plans'));
    $response->assertRedirect(route('login'));
});
test('select free plan redirects to manage page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Volt::test('billing.subscription-plans')
        ->call('selectPlan', 'free')
        ->assertRedirect(route('billing.manage'));
});
test('select pro plan redirects to checkout', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Volt::test('billing.subscription-plans')
        ->call('selectPlan', 'pro')
        ->assertRedirect(route('billing.checkout', ['plan' => 'pro']));
});
