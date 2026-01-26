<div class="mb-6">
    <div class="rounded-lg border-2 border-orange-300 dark:border-orange-700 bg-orange-50 dark:bg-orange-900/20 p-4">
        <div class="flex items-start gap-3">
            <div class="text-2xl">⏱️</div>
            <div class="flex-1">
                <div class="font-semibold text-orange-900 dark:text-orange-100 mb-2">
                    Export Rate Limit Exceeded
                </div>
                <div class="text-sm text-orange-800 dark:text-orange-200 mb-2">
                    You've made too many requests. Export buttons are disabled. Please wait
                    <strong>{{ $minutes }} minutes</strong> before exporting campaigns.
                </div>
                <div class="text-xs text-orange-700 dark:text-orange-300">
                    📊 Free: 60/hour | 📈 Pro: 300/hour | 🚀 Enterprise: 1000/hour
                    <a href="{{ route('billing.plans') }}" class="ml-2 underline hover:no-underline">Upgrade for higher limits</a>
                </div>
            </div>
        </div>
    </div>
</div>

