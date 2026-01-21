<?php
use App\Services\UsageTrackingService;

use function Livewire\Volt\computed;

$usage = computed(function () {
    $service = app(UsageTrackingService::class);

    return $service->getCurrentUsage(auth()->user());
});
$limits = computed(function () {
    $tier = auth()->user()->subscriptionTier();

    return config("subscriptions.plans.{$tier}.limits");
});
$tierName = computed(function () {
    $tier = auth()->user()->subscriptionTier();

    return config("subscriptions.plans.{$tier}.name");
});
$getPercentage = function (int $used, int $limit): int {
    if ($limit === PHP_INT_MAX) {
        return 0; // Unlimited
    }

    return $limit > 0 ? min(100, (int) (($used / $limit) * 100)) : 0;
};
$getColorClass = function (int $percentage): string {
    if ($percentage >= 90) {
        return 'bg-red-500';
    }
    if ($percentage >= 80) {
        return 'bg-yellow-500';
    }
    if ($percentage >= 50) {
        return 'bg-orange-500';
    }

    return 'bg-green-500';
};
$formatLimit = function (int $limit): string {
    return $limit === PHP_INT_MAX ? 'Unlimited' : number_format($limit);
};
?>
<div>
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Usage Dashboard</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Track your monthly usage across all features
            </p>
        </div>
        <!-- Current Plan Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Current Plan: {{ $this->tierName }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Billing cycle: {{ $this->usage['cycle_start']->format('M d') }} - {{ $this->usage['cycle_end']->format('M d, Y') }}
                    </p>
                </div>
                @if(auth()->user()->subscriptionTier() === 'free')
                    <flux:button wire:navigate href="{{ route('billing.plans') }}" variant="primary">
                        Upgrade to Pro
                    </flux:button>
                @else
                    <flux:button wire:navigate href="{{ route('billing.manage') }}" variant="outline">
                        Manage Plan
                    </flux:button>
                @endif
            </div>
        </div>
        <!-- Usage Meters -->
        <div class="grid gap-6 md:grid-cols-3 mb-8">
            <!-- Data Points -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Data Points</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($this->usage['data_points']) }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ number_format($this->usage['data_points']) }} / {{ $this->formatLimit($this->limits['data_points']) }}
                        </span>
                        @if($this->limits['data_points'] !== PHP_INT_MAX)
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $this->getPercentage($this->usage['data_points'], $this->limits['data_points']) }}%
                            </span>
                        @endif
                    </div>
                    @if($this->limits['data_points'] !== PHP_INT_MAX)
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="{{ $this->getColorClass($this->getPercentage($this->usage['data_points'], $this->limits['data_points'])) }} h-2 rounded-full transition-all duration-300"
                                 style="width: {{ $this->getPercentage($this->usage['data_points'], $this->limits['data_points']) }}%">
                            </div>
                        </div>
                    @else
                        <div class="text-sm text-green-600 dark:text-green-400 font-medium">
                            ✓ Unlimited
                        </div>
                    @endif
                </div>
                @if($this->getPercentage($this->usage['data_points'], $this->limits['data_points']) >= 80 && $this->limits['data_points'] !== PHP_INT_MAX)
                    <div class="mt-4 text-xs text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 p-2 rounded">
                        ⚠️ You're approaching your limit!
                    </div>
                @endif
            </div>
            <!-- Satellite Analyses -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Satellite Analyses</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($this->usage['satellite_analyses']) }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ number_format($this->usage['satellite_analyses']) }} / {{ $this->formatLimit($this->limits['satellite_analyses']) }}
                        </span>
                        @if($this->limits['satellite_analyses'] !== PHP_INT_MAX)
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $this->getPercentage($this->usage['satellite_analyses'], $this->limits['satellite_analyses']) }}%
                            </span>
                        @endif
                    </div>
                    @if($this->limits['satellite_analyses'] !== PHP_INT_MAX)
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="{{ $this->getColorClass($this->getPercentage($this->usage['satellite_analyses'], $this->limits['satellite_analyses'])) }} h-2 rounded-full transition-all duration-300"
                                 style="width: {{ $this->getPercentage($this->usage['satellite_analyses'], $this->limits['satellite_analyses']) }}%">
                            </div>
                        </div>
                    @else
                        <div class="text-sm text-green-600 dark:text-green-400 font-medium">
                            ✓ Unlimited
                        </div>
                    @endif
                </div>
                @if($this->getPercentage($this->usage['satellite_analyses'], $this->limits['satellite_analyses']) >= 80 && $this->limits['satellite_analyses'] !== PHP_INT_MAX)
                    <div class="mt-4 text-xs text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 p-2 rounded">
                        ⚠️ You're approaching your limit!
                    </div>
                @endif
            </div>
            <!-- Report Exports -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Report Exports</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ number_format($this->usage['report_exports']) }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ number_format($this->usage['report_exports']) }} / {{ $this->formatLimit($this->limits['report_exports']) }}
                        </span>
                        @if($this->limits['report_exports'] !== PHP_INT_MAX)
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $this->getPercentage($this->usage['report_exports'], $this->limits['report_exports']) }}%
                            </span>
                        @endif
                    </div>
                    @if($this->limits['report_exports'] !== PHP_INT_MAX)
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="{{ $this->getColorClass($this->getPercentage($this->usage['report_exports'], $this->limits['report_exports'])) }} h-2 rounded-full transition-all duration-300"
                                 style="width: {{ $this->getPercentage($this->usage['report_exports'], $this->limits['report_exports']) }}%">
                            </div>
                        </div>
                    @else
                        <div class="text-sm text-green-600 dark:text-green-400 font-medium">
                            ✓ Unlimited
                        </div>
                    @endif
                </div>
                @if($this->getPercentage($this->usage['report_exports'], $this->limits['report_exports']) >= 80 && $this->limits['report_exports'] !== PHP_INT_MAX)
                    <div class="mt-4 text-xs text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 p-2 rounded">
                        ⚠️ You're approaching your limit!
                    </div>
                @endif
            </div>
        </div>
        <!-- Upgrade CTA (only for free tier users approaching limits) -->
        @if(auth()->user()->subscriptionTier() === 'free')
            @php
                $anyNearLimit = $this->getPercentage($this->usage['data_points'], $this->limits['data_points']) >= 50 ||
                                $this->getPercentage($this->usage['satellite_analyses'], $this->limits['satellite_analyses']) >= 50 ||
                                $this->getPercentage($this->usage['report_exports'], $this->limits['report_exports']) >= 50;
            @endphp
            @if($anyNearLimit)
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-8 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold mb-2">Running low on resources?</h3>
                            <p class="text-blue-100 mb-4">
                                Upgrade to Pro for 10x more data points, satellite analyses, and exports!
                            </p>
                            <ul class="space-y-2 text-sm text-blue-100">
                                <li>✓ 500 data points/month (10x more)</li>
                                <li>✓ 100 satellite analyses/month (10x more)</li>
                                <li>✓ 20 report exports/month (10x more)</li>
                                <li>✓ Priority support</li>
                            </ul>
                        </div>
                        <div>
                            <flux:button wire:navigate href="{{ route('billing.plans') }}" variant="primary" class="bg-white text-blue-600 hover:bg-gray-100">
                                Upgrade to Pro - $29/mo
                            </flux:button>
                        </div>
                    </div>
                </div>
            @endif
        @endif
        <!-- Info Box -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mt-8">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <p class="text-sm text-blue-900 dark:text-blue-100 font-medium">About usage tracking</p>
                    <p class="text-sm text-blue-800 dark:text-blue-200 mt-1">
                        Usage resets automatically at the start of each billing cycle. Your current cycle ends on 
                        <strong>{{ $this->usage['cycle_end']->format('F d, Y') }}</strong>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
