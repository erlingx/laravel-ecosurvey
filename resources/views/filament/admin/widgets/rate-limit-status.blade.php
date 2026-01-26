<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            @if($isRateLimited)
                <!-- Rate Limited Warning -->
                <div class="rounded-lg border-2 border-orange-300 dark:border-orange-700 bg-orange-50 dark:bg-orange-900/20 p-4">
                    <div class="flex items-start gap-3">
                        <div class="text-2xl">⏱️</div>
                        <div class="flex-1">
                            <div class="font-semibold text-orange-900 dark:text-orange-100 mb-2">
                                Rate Limit Exceeded
                            </div>
                            <div class="text-sm text-orange-800 dark:text-orange-200 mb-2">
                                You've reached your hourly request limit. Please wait
                                <strong>{{ floor($retryAfter / 60) }} minutes</strong> before making more requests.
                            </div>
                            <div class="text-xs text-orange-700 dark:text-orange-300">
                                Your {{ ucfirst($tier) }} plan allows {{ $maxAttempts }} requests/hour
                                @if($tier === 'free')
                                    • <a href="{{ route('billing.plans') }}" class="underline hover:no-underline">Upgrade to Pro</a> for 300 requests/hour
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Rate Limit Status Card -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Requests Remaining -->
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Requests Remaining</div>
                            <div class="text-2xl font-bold {{ $isRateLimited ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">
                                {{ $remaining }}<span class="text-lg text-gray-500 dark:text-gray-400">/{{ $maxAttempts }}</span>
                            </div>
                        </div>
                        <svg class="size-10 {{ $isRateLimited ? 'text-red-500' : ($percentageUsed > 80 ? 'text-orange-500' : 'text-blue-500') }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Usage Percentage -->
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Usage</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                        {{ number_format($percentageUsed, 1) }}%
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all {{ $isRateLimited ? 'bg-red-500' : ($percentageUsed > 80 ? 'bg-orange-500' : 'bg-blue-500') }}"
                             style="width: {{ min($percentageUsed, 100) }}%"></div>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $used }} of {{ $maxAttempts }} used
                    </div>
                </div>

                <!-- Subscription Tier -->
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Subscription Tier</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">
                        {{ ucfirst($tier) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        @if($tier === 'free')
                            60 requests/hour
                        @elseif($tier === 'pro')
                            300 requests/hour
                        @else
                            1,000 requests/hour
                        @endif
                    </div>
                    @if($tier !== 'enterprise')
                        <a href="{{ route('billing.plans') }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline mt-1 inline-block">
                            Upgrade Plan →
                        </a>
                    @endif
                </div>
            </div>

            @if($isRateLimited)
                <!-- Reset Timer -->
                <div class="text-center py-2 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700">
                    <span class="text-sm text-orange-800 dark:text-orange-200">
                        ⏳ Rate limit resets in <strong>{{ floor($retryAfter / 60) }} minutes {{ $retryAfter % 60 }} seconds</strong>
                    </span>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
