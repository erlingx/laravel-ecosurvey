<?php

use App\Models\Campaign;
use App\Models\EnvironmentalMetric;
use App\Services\AnalyticsService;

use function Livewire\Volt\computed;
use function Livewire\Volt\state;

state([
    'campaignId' => null,
    'metricId' => null,
    'mapType' => 'street', // 'street' or 'satellite'
    'updateRevision' => 0, // Track updates to force re-render
]);

// Watch for filter changes and increment revision
$updatedCampaignId = function (): void {
    $this->updateRevision = (int) $this->updateRevision + 1;
};

$updatedMetricId = function (): void {
    $this->updateRevision = (int) $this->updateRevision + 1;
};

$updatedMapType = function (): void {
    $this->updateRevision = (int) $this->updateRevision + 1;
};

$campaigns = computed(fn () => Campaign::where('status', 'active')->orderBy('name')->get());

$metrics = computed(fn () => EnvironmentalMetric::where('is_active', true)->orderBy('name')->get());

$heatmapData = computed(function () {
    // Force recomputation by reading state variables
    $campaignId = $this->campaignId ? (int) $this->campaignId : null;
    $metricId = $this->metricId ? (int) $this->metricId : null;
    $revision = $this->updateRevision; // Force recalculation when revision changes

    $service = app(AnalyticsService::class);

    return $service->getHeatmapData($campaignId, $metricId);
});

$statistics = computed(function () {
    // Force recomputation by reading state variables
    $campaignId = $this->campaignId ? (int) $this->campaignId : null;
    $metricId = $this->metricId ? (int) $this->metricId : null;
    $revision = $this->updateRevision; // Force recalculation when revision changes

    $service = app(AnalyticsService::class);

    return $service->calculateStatistics($campaignId, $metricId);
});

?>

<div>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            Heatmap Analytics
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            {{-- Filters --}}
            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        {{-- Campaign Filter --}}
                        <div>
                            <label for="campaign" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Campaign
                            </label>
                            <select
                                wire:model.live="campaignId"
                                id="campaign"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >
                                <option value="">All Campaigns</option>
                                @foreach($this->campaigns as $campaign)
                                    <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Metric Filter --}}
                        <div>
                            <label for="metric" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Metric
                            </label>
                            <select
                                wire:model.live="metricId"
                                id="metric"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >
                                <option value="">All Metrics</option>
                                @foreach($this->metrics as $metric)
                                    <option value="{{ $metric->id }}">{{ $metric->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Map Type Toggle --}}
                        <div>
                            <label for="mapType" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Base Map
                            </label>
                            <select
                                wire:model.live="mapType"
                                id="mapType"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >
                                <option value="street">Street View</option>
                                <option value="satellite">Satellite View</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Statistics Panel --}}
            @if($this->statistics['count'] > 0)
                <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">Statistics</h3>
                        <div class="grid grid-cols-2 gap-4 md:grid-cols-6">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Count</p>
                                <p class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $this->statistics['count'] }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Min</p>
                                <p class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->statistics['min'], 2) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Max</p>
                                <p class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->statistics['max'], 2) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Average</p>
                                <p class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->statistics['average'], 2) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Median</p>
                                <p class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->statistics['median'], 2) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Std Dev</p>
                                <p class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($this->statistics['std_dev'], 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Heatmap --}}
            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6">
                    <div
                        id="heatmap"
                        class="h-[600px] w-full rounded-lg"
                        wire:ignore
                    ></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden div that updates when filters change - triggers map update via data attribute changes --}}
    <div id="heatmap-data-container" style="display: none;"
         data-heatmap-data="{{ json_encode($this->heatmapData) }}"
         data-map-type="{{ $mapType }}"
         data-revision="{{ $updateRevision }}"
         wire:key="heatmap-update-{{ $updateRevision }}">
    </div>
</div>

