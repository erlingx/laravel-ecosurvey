<?php
use Livewire\Volt\Component;

new class extends Component
{
    public ?string $sessionId = null;

    public ?string $tier = null;

    public function mount(): void
    {
        $this->sessionId = request('session_id');

        // If we have a session_id from Stripe Checkout, sync the subscription
        if ($this->sessionId) {
            try {
                $stripe = new \Stripe\StripeClient(config('cashier.secret'));
                $session = $stripe->checkout->sessions->retrieve($this->sessionId);

                // Get the subscription ID from the session
                if ($session->subscription) {
                    $user = auth()->user();
                    $stripeSubscription = $stripe->subscriptions->retrieve($session->subscription);

                    // Create or update the subscription in our database
                    $subscription = $user->subscriptions()->updateOrCreate(
                        ['stripe_id' => $stripeSubscription->id],
                        [
                            'type' => 'default',
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

                    \Log::info('Subscription synced from checkout session', [
                        'user_id' => $user->id,
                        'session_id' => $this->sessionId,
                        'subscription_id' => $stripeSubscription->id,
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to sync subscription from checkout session', [
                    'session_id' => $this->sessionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->tier = auth()->user()->fresh()->subscriptionTier();
    }
}; ?>
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 text-center">
        <div class="mb-6">
            <svg class="w-24 h-24 mx-auto text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
            Subscription Activated!
        </h1>
        <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">
            Welcome to {{ ucfirst($tier) }}! Your subscription is now active and you have access to all premium features.
        </p>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                What's Next?
            </h3>
            <ul class="text-left space-y-2 text-gray-700 dark:text-gray-300">
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>Start creating data points with your increased limits</span>
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>Access all satellite analysis features</span>
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>Export unlimited reports</span>
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>Manage your subscription anytime in your account settings</span>
                </li>
            </ul>
        </div>
        <div class="space-y-4">
            <flux:button variant="primary" class="w-full" href="{{ route('dashboard') }}" wire:navigate>
                Go to Dashboard
            </flux:button>
            <flux:button variant="outline" class="w-full" href="{{ route('billing.manage') }}" wire:navigate>
                Manage Subscription
            </flux:button>
        </div>
    </div>
</div>
