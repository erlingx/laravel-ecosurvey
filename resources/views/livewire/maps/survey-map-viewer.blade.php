<?php

use App\Models\Campaign;
use App\Models\EnvironmentalMetric;
use App\Services\GeospatialService;

use function Livewire\Volt\computed;
use function Livewire\Volt\state;

state([
    'campaignId' => null,
    'metricId' => null,
]);

// Method called when filters change
$filterChanged = function () {
    $service = app(GeospatialService::class);
    $campaignId = $this->campaignId ? (int) $this->campaignId : null;
    $metricId = $this->metricId ? (int) $this->metricId : null;

    $dataPoints = $service->getDataPointsAsGeoJSON($campaignId, $metricId);
    $boundingBox = $service->getBoundingBox($campaignId);

    // Dispatch as individual parameters
    $this->dispatch('map-filter-changed',
        dataPoints: $dataPoints,
        boundingBox: $boundingBox
    );
};

$campaigns = computed(fn () => Campaign::query()
    ->select('id', 'name', 'status')
    ->where('status', 'active')
    ->orderBy('name')
    ->get()
);

$metrics = computed(fn () => EnvironmentalMetric::query()
    ->select('id', 'name', 'unit')
    ->where('is_active', true)
    ->orderBy('name')
    ->get()
);

$dataPoints = computed(function () {
    $service = app(GeospatialService::class);
    // Cast to int or null to avoid type errors with empty strings
    $campaignId = $this->campaignId ? (int) $this->campaignId : null;
    $metricId = $this->metricId ? (int) $this->metricId : null;

    return $service->getDataPointsAsGeoJSON($campaignId, $metricId);
});

$boundingBox = computed(function () {
    $service = app(GeospatialService::class);
    // Cast to int or null to avoid type errors with empty strings
    $campaignId = $this->campaignId ? (int) $this->campaignId : null;

    return $service->getBoundingBox($campaignId);
});

?>

<div class="min-h-screen">
    <div class="h-[calc(100vh-8rem)]">
        <x-card class="h-full flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <flux:heading size="lg">Survey Map</flux:heading>
                    <flux:subheading>Interactive data point visualization</flux:subheading>
                </div>

                <div class="flex gap-2">
                    <flux:badge variant="outline" id="map-point-count">
                        {{ count($this->dataPoints['features'] ?? []) }} points
                    </flux:badge>
                </div>
            </div>

            {{-- Filters --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <x-select
                    label="Select a campaign to filter data points"
                    wire:model.live="campaignId"
                    wire:change="filterChanged"
                >
                    <option value="">All Campaigns</option>
                    @foreach($this->campaigns as $campaign)
                        <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                    @endforeach
                </x-select>

                <x-select
                    label="Select an environmental metric"
                    wire:model.live="metricId"
                    wire:change="filterChanged"
                >
                    <option value="">All Metrics</option>
                    @foreach($this->metrics as $metric)
                        <option value="{{ $metric->id }}">{{ $metric->name }} ({{ $metric->unit }})</option>
                    @endforeach
                </x-select>
            </div>

            {{-- Map Container --}}
            <div class="flex-1 relative" wire:ignore>
                <div
                    id="survey-map"
                    class="absolute inset-0 rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700"
                ></div>
            </div>

            {{-- Map Controls --}}
            <div class="mt-4 flex gap-2 justify-between items-start">
                <div class="flex gap-2">
                    <flux:button variant="outline" size="sm" onclick="resetMapView()">
                        🔄 Reset View
                    </flux:button>
                    <flux:button variant="outline" size="sm" onclick="toggleClustering()">
                        🗺️ Toggle Clustering
                    </flux:button>
                </div>

                {{-- Map Legend --}}
                <div class="flex gap-4 text-xs text-zinc-600 dark:text-zinc-400">
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded-full bg-red-500 border-2 border-red-700"></div>
                        <span>QA Flags</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded-full bg-yellow-400 border-2 border-yellow-600" style="border-style: dashed;"></div>
                        <span>Low Accuracy</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded-full bg-green-500 border-2 border-green-700"></div>
                        <span>Approved</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded-full bg-blue-500 border-2 border-blue-800"></div>
                        <span>Pending</span>
                    </div>
                </div>
            </div>
    </x-card>
</div>

{{-- Hidden div that updates when filters change - triggers map update via mutation observer --}}
<div id="map-data-container" style="display: none;"
     data-points="{{ json_encode($this->dataPoints) }}"
     data-bounds="{{ json_encode($this->boundingBox) }}"
     wire:key="map-update-{{ $campaignId }}-{{ $metricId }}">
</div>
</div>

