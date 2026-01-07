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

// Initialize with first metric on boot
$boot = function () {
    if (! $this->metricId) {
        $firstMetric = EnvironmentalMetric::where('is_active', true)->orderBy('name')->first();
        if ($firstMetric) {
            $this->metricId = $firstMetric->id;
        }
    }
};

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

$selectedMetric = computed(function () {
    if (! $this->metricId) {
        return null;
    }

    return EnvironmentalMetric::find($this->metricId);
});

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
                                Metric *
                            </label>
                            <select
                                wire:model.live="metricId"
                                id="metric"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                required
                            >
                                @foreach($this->metrics as $metric)
                                    <option value="{{ $metric->id }}">{{ $metric->name }} ({{ $metric->unit }})</option>
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
                        <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                            Statistics
                            @if($this->selectedMetric)
                                <span class="text-base font-normal text-gray-600 dark:text-gray-400">
                                    - {{ $this->selectedMetric->name }} ({{ $this->selectedMetric->unit }})
                                </span>
                            @endif
                        </h3>
                        <div class="grid grid-cols-2 gap-4 md:grid-cols-6">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Count</p>
                                <p class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $this->statistics['count'] }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Min</p>
                                <p class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ number_format($this->statistics['min'], 2) }}
                                    @if($this->selectedMetric)
                                        <span class="text-sm font-normal text-gray-600 dark:text-gray-400">{{ $this->selectedMetric->unit }}</span>
                                    @endif
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Max</p>
                                <p class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ number_format($this->statistics['max'], 2) }}
                                    @if($this->selectedMetric)
                                        <span class="text-sm font-normal text-gray-600 dark:text-gray-400">{{ $this->selectedMetric->unit }}</span>
                                    @endif
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Average</p>
                                <p class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ number_format($this->statistics['average'], 2) }}
                                    @if($this->selectedMetric)
                                        <span class="text-sm font-normal text-gray-600 dark:text-gray-400">{{ $this->selectedMetric->unit }}</span>
                                    @endif
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Median</p>
                                <p class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ number_format($this->statistics['median'], 2) }}
                                    @if($this->selectedMetric)
                                        <span class="text-sm font-normal text-gray-600 dark:text-gray-400">{{ $this->selectedMetric->unit }}</span>
                                    @endif
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Std Dev</p>
                                <p class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ number_format($this->statistics['std_dev'], 2) }}
                                    @if($this->selectedMetric)
                                        <span class="text-sm font-normal text-gray-600 dark:text-gray-400">{{ $this->selectedMetric->unit }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Heatmap --}}
            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Heatmap
                            @if($this->selectedMetric)
                                <span class="text-base font-normal text-gray-600 dark:text-gray-400">
                                    - {{ $this->selectedMetric->name }} ({{ $this->selectedMetric->unit }})
                                </span>
                            @endif
                        </h3>
                        @if(count($this->heatmapData) > 0 && $this->selectedMetric)
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Intensity:</span>
                                <div class="flex items-center gap-1">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Low</span>
                                    <div class="h-4 w-32 rounded" style="background: linear-gradient(to right, blue, lime, red);"></div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">High</span>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div
                        id="heatmap"
                        class="h-[600px] w-full rounded-lg {{ count($this->heatmapData) > 0 ? '' : 'hidden' }}"
                        wire:ignore
                    ></div>
                    @if(count($this->heatmapData) === 0)
                        <div class="flex h-[600px] items-center justify-center rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No data available</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    @if($this->selectedMetric && $this->campaignId)
                                        No {{ $this->selectedMetric->name }} measurements found for this campaign.
                                    @elseif($this->selectedMetric)
                                        No {{ $this->selectedMetric->name }} measurements found.
                                    @else
                                        Select a metric to view heatmap data.
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif
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

