<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Laravel\Cashier\Cashier;

class SyncStripeSubscriptions extends Command
{
    protected $signature = 'stripe:sync-subscriptions {user_id? : The ID of the user to sync}';

    protected $description = 'Sync subscriptions from Stripe to local database';

    public function handle(): int
    {
        $userId = $this->argument('user_id');
        if ($userId) {
            $user = User::find($userId);
            if (! $user) {
                $this->error("User {$userId} not found.");

                return self::FAILURE;
            }
            $this->syncUserSubscriptions($user);
        } else {
            $this->info('Syncing all users with Stripe customer IDs...');
            $users = User::whereNotNull('stripe_id')->get();
            if ($users->isEmpty()) {
                $this->warn('No users with Stripe customer IDs found.');

                return self::SUCCESS;
            }
            $bar = $this->output->createProgressBar($users->count());
            $bar->start();
            foreach ($users as $user) {
                $this->syncUserSubscriptions($user);
                $bar->advance();
            }
            $bar->finish();
            $this->newLine(2);
        }
        $this->info('Sync complete!');

        return self::SUCCESS;
    }

    private function syncUserSubscriptions(User $user): void
    {
        if (! $user->stripe_id) {
            $this->warn("User {$user->id} ({$user->email}) has no Stripe customer ID. Skipping.");

            return;
        }
        try {
            $stripe = Cashier::stripe();
            $customer = $stripe->customers->retrieve($user->stripe_id, [
                'expand' => ['subscriptions'],
            ]);
            if (! $customer->subscriptions || count($customer->subscriptions->data) === 0) {
                $this->line("No subscriptions found for user {$user->id} ({$user->email})");

                return;
            }
            foreach ($customer->subscriptions->data as $stripeSubscription) {
                $subscription = $user->subscriptions()
                    ->where('stripe_id', $stripeSubscription->id)
                    ->first();
                if ($subscription) {
                    $this->line("Subscription {$stripeSubscription->id} already exists for user {$user->id}");

                    continue;
                }
                $newSubscription = $user->subscriptions()->create([
                    'type' => 'default',
                    'stripe_id' => $stripeSubscription->id,
                    'stripe_status' => $stripeSubscription->status,
                    'stripe_price' => $stripeSubscription->items->data[0]->price->id ?? null,
                    'quantity' => $stripeSubscription->items->data[0]->quantity ?? 1,
                    'trial_ends_at' => $stripeSubscription->trial_end ? date('Y-m-d H:i:s', $stripeSubscription->trial_end) : null,
                    'ends_at' => $stripeSubscription->cancel_at ? date('Y-m-d H:i:s', $stripeSubscription->cancel_at) : null,
                ]);
                foreach ($stripeSubscription->items->data as $item) {
                    \DB::table('subscription_items')->insert([
                        'subscription_id' => $newSubscription->id,
                        'stripe_id' => $item->id,
                        'stripe_product' => $item->price->product,
                        'stripe_price' => $item->price->id,
                        'quantity' => $item->quantity,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $this->info("✓ Synced subscription {$stripeSubscription->id} for user {$user->id} ({$user->email})");
            }
        } catch (\Exception $e) {
            $this->error("Failed to sync user {$user->id}: {$e->getMessage()}");
        }
    }
}
