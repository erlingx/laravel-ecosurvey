<?php
use Livewire\Volt\Component;

new class extends Component
{
    public array $plans = [];

    public ?string $currentTier = null;

    public function mount(): void
    {
        $this->plans = config('subscriptions.plans');
        $this->currentTier = auth()->user()->subscriptionTier();
    }

    public function selectPlan(string $tier): void
    {
        if ($tier === 'free') {
            $this->redirect(route('billing.manage'), navigate: true);

            return;
        }
        $this->redirect(route('billing.checkout', ['plan' => $tier]), navigate: true);
    }
}; ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
            Choose Your Plan
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-400">
            Start free, upgrade when you need more power
        </p>
    </div>
    <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
        @foreach($plans as $key => $plan)
            <div class="relative flex flex-col bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden {{ $key === 'pro' ? 'ring-2 ring-blue-500' : '' }}">
                @if($key === 'pro')
                    <div class="absolute top-0 right-0 bg-blue-500 text-white px-4 py-1 text-sm font-semibold rounded-bl-lg">
                        Most Popular
                    </div>
                @endif
                <div class="p-6">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ $plan['name'] }}
                    </h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-gray-900 dark:text-white">
                            ${{ $plan['price'] }}
                        </span>
                        <span class="text-gray-600 dark:text-gray-400">/month</span>
                    </div>
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Features:</h4>
                        <ul class="space-y-2">
                            @foreach($plan['features'] as $feature)
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-gray-700 dark:text-gray-300">{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Usage Limits:</h4>
                        <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <li>📊 {{ $plan['limits']['data_points'] === PHP_INT_MAX ? 'Unlimited' : number_format($plan['limits']['data_points']) }} data points/month</li>
                            <li>🛰️ {{ $plan['limits']['satellite_analyses'] === PHP_INT_MAX ? 'Unlimited' : number_format($plan['limits']['satellite_analyses']) }} satellite analyses/month</li>
                            <li>📄 {{ $plan['limits']['report_exports'] === PHP_INT_MAX ? 'Unlimited' : number_format($plan['limits']['report_exports']) }} report exports/month</li>
                        </ul>
                    </div>
                </div>
                <div class="p-6 pt-0 mt-auto">
                    @if($currentTier === $key)
                        <flux:button variant="primary" class="w-full" disabled>
                            Current Plan
                        </flux:button>
                    @else
                        <flux:button 
                            variant="{{ $key === 'pro' ? 'primary' : 'outline' }}" 
                            class="w-full"
                            wire:click="selectPlan('{{ $key }}')"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="selectPlan('{{ $key }}')">
                                @if($key === 'free')
                                    View Free Plan
                                @else
                                    Upgrade to {{ $plan['name'] }}
                                @endif
                            </span>
                            <span wire:loading wire:target="selectPlan('{{ $key }}')">
                                Loading...
                            </span>
                        </flux:button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-12 text-center">
        <p class="text-gray-600 dark:text-gray-400">
            All plans include secure data storage, map visualization, and community support.
        </p>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
            Need a custom plan? <a href="mailto:support@ecosurvey.com" class="text-blue-500 hover:underline">Contact us</a>
        </p>
    </div>
</div>
