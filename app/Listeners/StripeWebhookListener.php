<?php

declare(strict_types=1);

namespace App\Listeners;

use Laravel\Cashier\Events\WebhookReceived;

class StripeWebhookListener
{
    public function handle(WebhookReceived $event): void
    {
        $payload = $event->payload;

        // Log all webhook events for debugging
        \Log::info('Stripe Webhook Received', [
            'type' => $payload['type'] ?? 'unknown',
            'id' => $payload['id'] ?? null,
        ]);

        // Handle checkout.session.completed
        if ($payload['type'] === 'checkout.session.completed') {
            $this->handleCheckoutCompleted($payload);
        }

        // Handle customer.subscription.created
        if ($payload['type'] === 'customer.subscription.created') {
            $this->handleSubscriptionCreated($payload);
        }

        // Handle customer.subscription.updated
        if ($payload['type'] === 'customer.subscription.updated') {
            $this->handleSubscriptionUpdated($payload);
        }
    }

    protected function handleCheckoutCompleted(array $payload): void
    {
        $session = $payload['data']['object'] ?? null;

        if (! $session || ! isset($session['customer'])) {
            return;
        }

        $customerId = $session['customer'];
        $subscriptionId = $session['subscription'] ?? null;

        \Log::info('Checkout completed', [
            'customer' => $customerId,
            'subscription' => $subscriptionId,
            'mode' => $session['mode'] ?? 'unknown',
        ]);

        // If there's a subscription, sync it
        if ($subscriptionId) {
            $this->syncSubscriptionFromStripe($customerId, $subscriptionId);
        }
    }

    protected function handleSubscriptionCreated(array $payload): void
    {
        $subscription = $payload['data']['object'] ?? null;

        if (! $subscription) {
            return;
        }

        $customerId = $subscription['customer'];
        $subscriptionId = $subscription['id'];

        \Log::info('Subscription created', [
            'customer' => $customerId,
            'subscription' => $subscriptionId,
            'status' => $subscription['status'] ?? 'unknown',
        ]);

        $this->syncSubscriptionFromStripe($customerId, $subscriptionId);
    }

    protected function handleSubscriptionUpdated(array $payload): void
    {
        $subscription = $payload['data']['object'] ?? null;

        if (! $subscription) {
            return;
        }

        $customerId = $subscription['customer'];
        $subscriptionId = $subscription['id'];

        \Log::info('Subscription updated', [
            'customer' => $customerId,
            'subscription' => $subscriptionId,
            'status' => $subscription['status'] ?? 'unknown',
        ]);

        $this->syncSubscriptionFromStripe($customerId, $subscriptionId);
    }

    protected function syncSubscriptionFromStripe(string $customerId, string $subscriptionId): void
    {
        try {
            // Find user by Stripe customer ID
            $user = \App\Models\User::where('stripe_id', $customerId)->first();

            if (! $user) {
                \Log::warning('User not found for Stripe customer', ['customer_id' => $customerId]);

                return;
            }

            // Fetch subscription from Stripe
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $stripeSubscription = $stripe->subscriptions->retrieve($subscriptionId);

            \Log::info('Syncing subscription for user', [
                'user_id' => $user->id,
                'email' => $user->email,
                'subscription_id' => $subscriptionId,
                'status' => $stripeSubscription->status,
            ]);

            // Create or update the subscription in our database
            $subscription = $user->subscriptions()->updateOrCreate(
                ['stripe_id' => $stripeSubscription->id],
                [
                    'type' => $stripeSubscription->metadata->name ?? 'default',
                    'stripe_status' => $stripeSubscription->status,
                    'trial_ends_at' => $stripeSubscription->trial_end ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->trial_end) : null,
                    'ends_at' => $stripeSubscription->cancel_at ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->cancel_at) : null,
                ]
            );

            // Sync subscription items
            foreach ($stripeSubscription->items->data as $item) {
                $subscription->items()->updateOrCreate(
                    ['stripe_id' => $item->id],
                    [
                        'stripe_product' => $item->price->product,
                        'stripe_price' => $item->price->id,
                        'quantity' => $item->quantity,
                    ]
                );
            }

            \Log::info('✓ Subscription synced successfully', [
                'user_id' => $user->id,
                'subscription_id' => $subscriptionId,
                'tier' => $user->fresh()->subscriptionTier(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to sync subscription from Stripe', [
                'customer_id' => $customerId,
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
