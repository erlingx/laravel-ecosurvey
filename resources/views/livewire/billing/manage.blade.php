<?php
use Livewire\Volt\Component;

new class extends Component
{
    public string $currentTier = '';

    public function mount(): void
    {
        $this->currentTier = auth()->user()->subscriptionTier();
    }
}; ?>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">
        Manage Subscription
    </h1>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            Current Plan
        </h2>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ ucfirst($currentTier) }} Plan
                </p>
                <p class="text-gray-600 dark:text-gray-400">
                    @if($currentTier === 'free')
                        Free forever - Upgrade anytime to unlock more features
                    @else
                        Active subscription
                    @endif
                </p>
            </div>
            @if($currentTier === 'free')
                <flux:button variant="primary" href="{{ route('billing.plans') }}" wire:navigate>
                    Upgrade Plan
                </flux:button>
            @else
                <flux:button variant="outline" href="{{ route('billing.plans') }}" wire:navigate>
                    Change Plan
                </flux:button>
            @endif
        </div>
    </div>
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
        <p class="text-gray-700 dark:text-gray-300">
            <strong>Coming Soon:</strong> Full subscription management including payment method updates, invoices, and more will be available in Task 3.1.
        </p>
    </div>
</div>
