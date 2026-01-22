<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SyncStripeSubscription extends Command
{
    protected $signature = 'subscription:sync {userId}';

    protected $description = 'Sync a user\'s subscription from Stripe';

    public function handle(): int
    {
        $userId = $this->argument('userId');
        $user = User::find($userId);

        if (! $user) {
            $this->error("User with ID {$userId} not found.");

            return 1;
        }

        if (! $user->stripe_id) {
            $this->error("User {$user->email} has no Stripe customer ID.");

            return 1;
        }

        $this->info("Syncing subscriptions for {$user->email} (Stripe ID: {$user->stripe_id})...");

        try {
            // Get all subscriptions from Stripe using Stripe API directly
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $stripeSubscriptions = $stripe->subscriptions->all([
                'customer' => $user->stripe_id,
                'limit' => 10,
            ]);

            if (count($stripeSubscriptions->data) === 0) {
                $this->warn('No subscriptions found in Stripe for this customer.');

                return 0;
            }

            foreach ($stripeSubscriptions->data as $stripeSubscription) {
                $this->info("Found Stripe subscription: {$stripeSubscription->id}");
                $this->info("  Status: {$stripeSubscription->status}");
                $this->info('  Items: '.count($stripeSubscription->items->data));

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

                    $this->info("  Synced item: {$item->price->id}");
                }

                $this->info("✓ Subscription {$stripeSubscription->id} synced successfully!");
            }

            $this->info("\n✓ Sync complete!");
            $this->info('User tier: '.$user->fresh()->subscriptionTier());

            return 0;
        } catch (\Exception $e) {
            $this->error("Error syncing subscription: {$e->getMessage()}");

            return 1;
        }
    }
}
