<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('user can be billable customer', function () {
    $user = User::factory()->create();
    expect(method_exists($user, 'createAsStripeCustomer'))->toBeTrue();
    expect(method_exists($user, 'subscriptions'))->toBeTrue();
    expect(method_exists($user, 'subscription'))->toBeTrue();
    expect(method_exists($user, 'subscribed'))->toBeTrue();
});
test('cashier migrations created required tables', function () {
    expect(Schema::hasTable('subscriptions'))->toBeTrue();
    expect(Schema::hasTable('subscription_items'))->toBeTrue();
    // Check subscriptions table has required columns
    expect(Schema::hasColumn('subscriptions', 'id'))->toBeTrue();
    expect(Schema::hasColumn('subscriptions', 'user_id'))->toBeTrue();
    expect(Schema::hasColumn('subscriptions', 'type'))->toBeTrue();
    expect(Schema::hasColumn('subscriptions', 'stripe_id'))->toBeTrue();
    expect(Schema::hasColumn('subscriptions', 'stripe_status'))->toBeTrue();
    expect(Schema::hasColumn('subscriptions', 'stripe_price'))->toBeTrue();
    expect(Schema::hasColumn('subscriptions', 'quantity'))->toBeTrue();
    expect(Schema::hasColumn('subscriptions', 'trial_ends_at'))->toBeTrue();
    expect(Schema::hasColumn('subscriptions', 'ends_at'))->toBeTrue();
    // Check subscription_items table has required columns
    expect(Schema::hasColumn('subscription_items', 'id'))->toBeTrue();
    expect(Schema::hasColumn('subscription_items', 'subscription_id'))->toBeTrue();
    expect(Schema::hasColumn('subscription_items', 'stripe_id'))->toBeTrue();
    expect(Schema::hasColumn('subscription_items', 'stripe_product'))->toBeTrue();
    expect(Schema::hasColumn('subscription_items', 'stripe_price'))->toBeTrue();
    expect(Schema::hasColumn('subscription_items', 'quantity'))->toBeTrue();
});
test('user has customer columns', function () {
    expect(Schema::hasColumn('users', 'stripe_id'))->toBeTrue();
    expect(Schema::hasColumn('users', 'pm_type'))->toBeTrue();
    expect(Schema::hasColumn('users', 'pm_last_four'))->toBeTrue();
    expect(Schema::hasColumn('users', 'trial_ends_at'))->toBeTrue();
});
