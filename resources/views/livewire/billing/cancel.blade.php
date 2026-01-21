<?php
use Livewire\Volt\Component;

new class extends Component
{
    //
}; ?>
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 text-center">
        <div class="mb-6">
            <svg class="w-24 h-24 mx-auto text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
            Checkout Cancelled
        </h1>
        <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">
            No worries! Your subscription was not created. You can try again anytime.
        </p>
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 mb-8">
            <p class="text-gray-700 dark:text-gray-300">
                Still have questions about our plans? We're here to help! Contact our support team or review our pricing options.
            </p>
        </div>
        <div class="space-y-4">
            <flux:button variant="primary" class="w-full" href="{{ route('billing.plans') }}" wire:navigate>
                View Plans Again
            </flux:button>
            <flux:button variant="outline" class="w-full" href="{{ route('dashboard') }}" wire:navigate>
                Return to Dashboard
            </flux:button>
        </div>
    </div>
</div>
