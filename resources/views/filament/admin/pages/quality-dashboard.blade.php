<x-filament-panels::page>
    {{-- Widgets are automatically rendered via getHeaderWidgets() --}}

    {{-- Quality Assurance Guidelines --}}
    <div class="mt-6 grid gap-6 md:grid-cols-2">
        {{-- Data Quality Guidelines --}}
        <div class="rounded-lg bg-white dark:bg-gray-800 shadow p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-start gap-3 mb-4">
                <div class="shrink-0">
                    <svg class="w-6 h-6 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Quality Guidelines
                    </h3>
                </div>
            </div>

            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                <div class="flex items-start gap-2">
                    <span class="text-green-500 mt-0.5">✓</span>
                    <p><strong class="text-gray-900 dark:text-white">GPS Accuracy:</strong> Data points with accuracy ≤ 10m are considered high quality</p>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-green-500 mt-0.5">✓</span>
                    <p><strong class="text-gray-900 dark:text-white">Review Workflow:</strong> Approve quality data, reject suspicious readings, flag for follow-up</p>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-green-500 mt-0.5">✓</span>
                    <p><strong class="text-gray-900 dark:text-white">Validation:</strong> Environmental metrics are checked against expected ranges automatically</p>
                </div>
            </div>
        </div>

        {{-- Quick Actions Info --}}
        <div class="rounded-lg bg-white dark:bg-gray-800 shadow p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-start gap-3 mb-4">
                <div class="shrink-0">
                    <svg class="w-6 h-6 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Reviewing Data
                    </h3>
                </div>
            </div>

            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                <div class="flex items-start gap-2">
                    <span class="text-blue-500 mt-0.5">→</span>
                    <p>Navigate to <strong class="text-gray-900 dark:text-white">Data Points</strong> to review pending submissions</p>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-blue-500 mt-0.5">→</span>
                    <p>Use <strong class="text-gray-900 dark:text-white">Status Filter</strong> to view pending, approved, or rejected data</p>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-blue-500 mt-0.5">→</span>
                    <p>Check <strong class="text-gray-900 dark:text-white">QA Flags</strong> column for automatically detected quality issues</p>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-blue-500 mt-0.5">→</span>
                    <p>Use <strong class="text-gray-900 dark:text-white">Bulk Actions</strong> to clear flags or update multiple records</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
