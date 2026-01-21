<?php
use Livewire\Volt\Component;

new class extends Component
{
    public string $plan = '';

    public ?array $planDetails = null;

    public function mount(string $plan): void
    {
        $this->plan = $plan;
        $this->planDetails = config("subscriptions.plans.{$plan}");
        if (! $this->planDetails || ! isset($this->planDetails['stripe_price_id'])) {
            $this->redirect(route('billing.plans'), navigate: true);
        }
    }

    public function checkout(): void
    {
        $user = auth()->user();
        $priceId = $this->planDetails['stripe_price_id'];
        try {
            $checkout = $user->newSubscription('default', $priceId)
                ->checkout([
                    'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('billing.cancel'),
                ]);
            $this->redirect($checkout->url);
        } catch (\Exception $e) {
            session()->flash('error', 'Unable to start checkout: '.$e->getMessage());
        }
    }
}; ?>
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">
            Subscribe to {{ $planDetails['name'] ?? '' }}
        </h1>
        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif
        <div class="mb-8">
            <div class="flex items-baseline mb-4">
                <span class="text-5xl font-bold text-gray-900 dark:text-white">
                    ${{ $planDetails['price'] ?? 0 }}
                </span>
                <span class="text-xl text-gray-600 dark:text-gray-400 ml-2">/month</span>
            </div>
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                    What's included:
                </h3>
                <ul class="space-y-2">
                    @foreach($planDetails['features'] ?? [] as $feature)
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700 dark:text-gray-300">{{ $feature }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Monthly Limits:</h4>
                <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                    <li>📊 {{ ($planDetails['limits']['data_points'] ?? 0) === PHP_INT_MAX ? 'Unlimited' : number_format($planDetails['limits']['data_points'] ?? 0) }} data points</li>
                    <li>🛰️ {{ ($planDetails['limits']['satellite_analyses'] ?? 0) === PHP_INT_MAX ? 'Unlimited' : number_format($planDetails['limits']['satellite_analyses'] ?? 0) }} satellite analyses</li>
                    <li>📄 {{ ($planDetails['limits']['report_exports'] ?? 0) === PHP_INT_MAX ? 'Unlimited' : number_format($planDetails['limits']['report_exports'] ?? 0) }} report exports</li>
                </ul>
            </div>
        </div>
        <div class="space-y-4">
            <flux:button 
                variant="primary" 
                class="w-full text-lg py-3"
                wire:click="checkout"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="checkout">
                    Continue to Stripe Checkout
                </span>
                <span wire:loading wire:target="checkout">
                    Redirecting to Stripe...
                </span>
            </flux:button>
            <flux:button 
                variant="outline" 
                class="w-full"
                href="{{ route('billing.plans') }}"
                wire:navigate
            >
                Back to Plans
            </flux:button>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-6 text-center">
            You will be redirected to Stripe's secure checkout page. Your subscription will start immediately upon successful payment.
        </p>
    </div>
</div>
