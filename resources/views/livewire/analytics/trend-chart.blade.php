<?php

use App\Models\Campaign;
use App\Models\EnvironmentalMetric;
use App\Services\AnalyticsService;

use function Livewire\Volt\computed;
use function Livewire\Volt\state;

state([
    'campaignId' => null,
    'metricId' => null,
    'interval' => 'day', // day, week, month
    'updateRevision' => 0, // Track updates to force re-render
]);

// Initialize with first metric if none selected
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

$updatedInterval = function (): void {
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

$trendData = computed(function () {
    $campaignId = $this->campaignId ? (int) $this->campaignId : null;
    $metricId = $this->metricId ? (int) $this->metricId : null;
    $revision = $this->updateRevision; // Force recalculation when revision changes

    $service = app(AnalyticsService::class);

    return $service->getTrendData($campaignId, $metricId, $this->interval);
});

$distributionData = computed(function () {
    $campaignId = $this->campaignId ? (int) $this->campaignId : null;
    $metricId = $this->metricId ? (int) $this->metricId : null;
    $revision = $this->updateRevision; // Force recalculation when revision changes

    $service = app(AnalyticsService::class);

    return $service->getDistributionData($campaignId, $metricId);
});

$statistics = computed(function () {
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
            Trend Analysis
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

                        {{-- Interval Filter --}}
                        <div>
                            <label for="interval" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Interval
                            </label>
                            <select
                                wire:model.live="interval"
                                id="interval"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >
                                <option value="day">Daily</option>
                                <option value="week">Weekly</option>
                                <option value="month">Monthly</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Statistics Panel or No Data Message --}}
            @if($this->statistics['count'] > 0)
                <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                            Statistics
                            @if($this->selectedMetric)
                                <span class="text-base font-normal text-gray-600 dark:text-gray-400">
                                    - {{ $this->selectedMetric->name }}
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
            @else
                <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <div class="p-6">
                        <div class="rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center dark:border-gray-600 dark:bg-gray-900">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No data points found</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                @if($this->selectedMetric && $this->campaignId)
                                    No {{ $this->selectedMetric->name }} measurements found for this campaign.
                                @elseif($this->selectedMetric)
                                    No {{ $this->selectedMetric->name }} measurements found.
                                @else
                                    Select a metric to view data.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Trend Chart --}}
            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Time Series Trend
                                @if($this->selectedMetric)
                                    <span class="text-base font-normal text-gray-600 dark:text-gray-400">
                                        - {{ $this->selectedMetric->name }} ({{ $this->selectedMetric->unit }})
                                    </span>
                                @endif
                            </h3>
                            @if(count($this->trendData) > 0)
                                <span class="inline-flex items-center gap-1.5 rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/20 dark:text-blue-400">
                                    <span class="h-3 w-8 rounded" style="background: linear-gradient(to bottom, rgba(59, 130, 246, 0.3), rgba(59, 130, 246, 0.1));"></span>
                                    95% CI
                                </span>
                            @endif
                        </div>
                        @if(count($this->trendData) > 0)
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    onclick="window.trendChartInstance && window.trendChartInstance.resetZoom()"
                                    class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                    title="Reset zoom to show all data"
                                >
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"></path>
                                        </svg>
                                        Reset Zoom
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    onclick="toggleTrendLine('Maximum')"
                                    class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                    id="toggle-max-btn"
                                >
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="h-2 w-2 rounded-full bg-red-500"></span>
                                        Show Maximum
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    onclick="toggleTrendLine('Minimum')"
                                    class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                                    id="toggle-min-btn"
                                >
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="h-2 w-2 rounded-full bg-green-500"></span>
                                        Show Minimum
                                    </span>
                                </button>
                            </div>
                        @endif
                    </div>
                    @if(count($this->trendData) > 0)
                        <div class="relative h-[400px] w-full">
                            <canvas
                                id="trend-chart"
                                wire:ignore
                            ></canvas>
                        </div>
                    @else
                        <div class="flex h-[400px] items-center justify-center rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No data available</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    No time series data found for the selected filters.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Distribution Chart --}}
            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6">
                    <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                        Value Distribution
                        @if($this->selectedMetric)
                            <span class="text-base font-normal text-gray-600 dark:text-gray-400">
                                - {{ $this->selectedMetric->name }} ({{ $this->selectedMetric->unit }})
                            </span>
                        @endif
                    </h3>
                    @if(count($this->distributionData) > 0)
                        <div class="relative h-[400px] w-full">
                            <canvas
                                id="distribution-chart"
                                wire:ignore
                            ></canvas>
                        </div>
                    @else
                        <div class="flex h-[400px] items-center justify-center rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No data available</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    No distribution data found for the selected filters.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden div that updates when filters change - triggers chart update via data attribute changes --}}
    <div id="trend-data-container" style="display: none;"
         data-trend-data="{{ json_encode($this->trendData) }}"
         data-distribution-data="{{ json_encode($this->distributionData) }}"
         data-revision="{{ $updateRevision }}"
         wire:key="trend-update-{{ $updateRevision }}">
    </div>
</div>

