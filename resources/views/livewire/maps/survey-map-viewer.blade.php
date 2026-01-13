<?php

use App\Models\Campaign;
use App\Models\EnvironmentalMetric;
use App\Services\GeospatialService;

use function Livewire\Volt\computed;
use function Livewire\Volt\state;
use function Livewire\Volt\on;

state([
    'campaignId' => null,
    'metricId' => null,
    'showEditModal' => false,
    'editDataPointId' => null,
]);

// Listen for edit request from map popup
on(['edit-data-point' => function ($dataPointId) {
    // Dispatch Alpine event to open modal instantly with loading spinner
    $this->dispatch('open-edit-modal', id: $dataPointId);
}]);

// Close modal method
$closeModal = function () {
    $this->showEditModal = false;
    $this->editDataPointId = null;
};

// Listen for close modal event
on(['close-edit-modal' => function () {
    $this->showEditModal = false;
    $this->editDataPointId = null;
}]);

// Listen for data point saved event
on(['data-point-saved' => function () {
    $this->showEditModal = false;
    $this->editDataPointId = null;
    $this->dispatch('edit-modal-close');
    // Trigger filter refresh to update map
    $this->filterChanged();
}]);

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

    $geoJSON = $service->getDataPointsAsGeoJSON($campaignId, $metricId);

    \Log::info('🗺️ Survey Map: Loaded data points', [
        'campaignId' => $campaignId,
        'metricId' => $metricId,
        'totalFeatures' => count($geoJSON['features'] ?? []),
    ]);

    return $geoJSON;
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
                </div>

                {{-- Map Legend --}}
                <div class="flex flex-col gap-2">
                    <div class="flex gap-4 text-xs text-zinc-600 dark:text-zinc-400">
                        <div class="flex items-center gap-1">
                            <div class="w-3 h-3 rounded-full bg-red-500 border-2 border-red-700"></div>
                            <span>QA Flags</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <div class="w-3 h-3 rounded-full bg-gray-500 border-2 border-gray-700" style="border-style: dashed;"></div>
                            <span>Rejected</span>
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
                    <div class="text-xs text-zinc-500 dark:text-zinc-500 italic">
                        Clusters show proportional data quality distribution
                    </div>
                </div>
            </div>
    </x-card>

    {{-- Edit Data Point Modal --}}
    <div
        x-data="{
            open: false,
            loading: true,
            dataPointId: null,
            init() {
                // Close modal and reset state
                this.$watch('open', (value) => {
                    if (!value) {
                        this.loading = true;
                        this.dataPointId = null;
                    }
                });
            }
        }"
        x-show="open"
        x-on:open-edit-modal.window="dataPointId = $event.detail.id; loading = true; open = true; $nextTick(() => { $wire.set('editDataPointId', dataPointId); $wire.set('showEditModal', true); })"
        x-on:edit-modal-close.window="open = false"
        x-on:data-point-loaded.window="loading = false"
        x-cloak
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
        style="display: none;"
    >
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 bg-black/60 backdrop-blur-sm"
            @click="open = false"
        ></div>

        {{-- Centered Modal --}}
        <div
            class="relative w-full max-w-2xl max-h-[90vh] bg-white dark:bg-zinc-900 rounded-lg shadow-2xl overflow-hidden flex flex-col"
            @click.stop
        >
            <div class="shrink-0 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 px-6 py-4 flex items-center justify-between">
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Edit Data Point</h2>
                <button
                    @click="open = false"
                    class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-6">
                {{-- Loading Spinner - shown immediately when modal opens --}}
                <div x-show="loading" class="flex flex-col items-center justify-center py-20">
                    <div class="relative">
                        <div class="w-16 h-16 border-4 border-blue-200 dark:border-blue-900 rounded-full"></div>
                        <div class="w-16 h-16 border-4 border-blue-600 dark:border-blue-400 border-t-transparent rounded-full animate-spin absolute top-0 left-0"></div>
                    </div>
                    <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">Loading data point...</p>
                </div>
                {{-- Form content - hidden until loaded --}}
                <div x-show="!loading" x-cloak>
                    @if($showEditModal && $editDataPointId)
                        <div x-init="$nextTick(() => window.dispatchEvent(new CustomEvent('data-point-loaded')))">
                            @livewire('data-collection.reading-form', ['dataPoint' => $editDataPointId, 'inModal' => true], key('edit-form-'.$editDataPointId))
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Hidden div that updates when filters change - triggers map update via mutation observer --}}
<div id="map-data-container" style="display: none;"
     data-points="{{ json_encode($this->dataPoints) }}"
     data-bounds="{{ json_encode($this->boundingBox) }}"
     wire:key="map-update-{{ $campaignId }}-{{ $metricId }}">
</div>
</div>

