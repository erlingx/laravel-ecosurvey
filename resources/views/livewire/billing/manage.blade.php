<?php
use Livewire\Volt\Component;

new class extends Component
{
    public string $currentTier = '';

    public bool $showCancelModal = false;

    public string $cancelType = 'end_of_period'; // 'end_of_period' or 'immediately'

    public function mount(): void
    {
        $this->currentTier = auth()->user()->subscriptionTier();
    }

    public function updatePaymentMethod(): void
    {
        $user = auth()->user();

        if (! $user->subscribed('default')) {
            session()->flash('error', 'No active subscription found.');

            return;
        }

        // Redirect to Stripe Billing Portal
        $this->redirect($user->redirectToBillingPortal(route('billing.manage')));
    }

    public function openCancelModal(): void
    {
        $this->showCancelModal = true;
    }

    public function cancelSubscription(): void
    {
        $user = auth()->user();

        if (! $user->subscribed('default')) {
            session()->flash('error', 'No active subscription found.');
            $this->showCancelModal = false;

            return;
        }

        $subscription = $user->subscription('default');

        if ($this->cancelType === 'immediately') {
            // Cancel immediately
            $subscription->cancelNow();
            session()->flash('success', 'Your subscription has been cancelled immediately. You now have access to the Free tier.');
        } else {
            // Cancel at period end
            $subscription->cancel();
            session()->flash('success', 'Your subscription will be cancelled at the end of your billing period. You can continue using your current plan until then.');
        }

        $this->showCancelModal = false;
        $this->currentTier = $user->fresh()->subscriptionTier();
    }

    public function resumeSubscription(): void
    {
        $user = auth()->user();

        if (! $user->subscription('default') || ! $user->subscription('default')->onGracePeriod()) {
            session()->flash('error', 'Cannot resume subscription.');

            return;
        }

        $user->subscription('default')->resume();
        session()->flash('success', 'Your subscription has been resumed!');
    }
}; ?>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">
        Manage Subscription
    </h1>

    @if(session('success'))
        <div class="mb-6 bg-green-100 dark:bg-green-900/20 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-200 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-100 dark:bg-red-900/20 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    {{-- Current Plan --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
            Current Plan
        </h2>
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ ucfirst($currentTier) }} Plan
                </p>
                @if($currentTier === 'free')
                    <p class="text-gray-600 dark:text-gray-400">
                        Free forever - Upgrade anytime to unlock more features
                    </p>
                @elseif(auth()->user()->subscription('default') && auth()->user()->subscription('default')->onGracePeriod())
                    <p class="text-orange-600 dark:text-orange-400">
                        Cancelled - Access until {{ auth()->user()->subscription('default')->ends_at->format('F j, Y') }}
                    </p>
                @else
                    <p class="text-green-600 dark:text-green-400">
                        Active subscription
                    </p>
                @endif
            </div>
            <div class="flex gap-2">
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

        @if($currentTier !== 'free')
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Price</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            ${{ config("subscriptions.plans.{$currentTier}.price") }}/month
                        </p>
                    </div>
                    @if(auth()->user()->subscription('default'))
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Status</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ ucfirst(auth()->user()->subscription('default')->stripe_status) }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Actions --}}
    @if($currentTier !== 'free' && auth()->user()->subscribed('default'))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                Subscription Actions
            </h2>
            <div class="space-y-4">
                @if(auth()->user()->subscription('default')->onGracePeriod())
                    {{-- Resume Subscription --}}
                    <div class="flex items-center justify-between p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-700">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">Resume Subscription</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Your subscription is set to cancel. Resume to continue access.
                            </p>
                        </div>
                        <flux:button variant="primary" wire:click="resumeSubscription">
                            Resume
                        </flux:button>
                    </div>
                @else
                    {{-- Update Payment Method --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">Update Payment Method</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Manage your payment methods and billing information
                            </p>
                        </div>
                        <flux:button variant="outline" wire:click="updatePaymentMethod">
                            Update
                        </flux:button>
                    </div>

                    {{-- Cancel Subscription --}}
                    <div class="flex items-center justify-between p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-700">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">Cancel Subscription</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Cancel your subscription and downgrade to Free tier
                            </p>
                        </div>
                        <flux:button variant="danger" wire:click="openCancelModal">
                            Cancel
                        </flux:button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Invoices --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                Billing History
            </h2>
            @php
                $invoices = auth()->user()->invoices();
            @endphp
            @if(count($invoices) > 0)
                <div class="space-y-2">
                    @foreach($invoices as $invoice)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    ${{ $invoice->total() }} - {{ $invoice->date()->format('F j, Y') }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ ucfirst($currentTier) }} Plan
                                </p>
                            </div>
                            <a href="{{ $invoice->downloadUrl() }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">
                                Download PDF
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-600 dark:text-gray-400">No invoices yet.</p>
            @endif
        </div>
    @endif

    {{-- Cancellation Modal --}}
    <flux:modal wire:model="showCancelModal" class="space-y-6">
        <div>
            <flux:heading size="lg">Cancel Subscription</flux:heading>
            <flux:subheading>Choose how you'd like to cancel your subscription</flux:subheading>
        </div>

        <div class="space-y-4">
            <flux:radio.group wire:model="cancelType" label="Cancellation Option">
                <flux:radio value="end_of_period" label="Cancel at end of billing period" description="Keep access until your current billing period ends" />
                <flux:radio value="immediately" label="Cancel immediately" description="Cancel now and downgrade to Free tier" />
            </flux:radio.group>
        </div>

        <flux:separator />

        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                <strong>Note:</strong>
                @if($cancelType === 'immediately')
                    Your subscription will be cancelled immediately and you'll be downgraded to the Free tier right away.
                @else
                    You'll continue to have access to your {{ ucfirst($currentTier) }} features until the end of your current billing period.
                @endif
            </p>
        </div>

        <div class="flex gap-2 justify-end">
            <flux:button variant="ghost" wire:click="$set('showCancelModal', false)">
                Keep Subscription
            </flux:button>
            <flux:button variant="danger" wire:click="cancelSubscription">
                Confirm Cancellation
            </flux:button>
        </div>
    </flux:modal>
</div>
