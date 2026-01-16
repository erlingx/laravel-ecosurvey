<?php

use App\Models\Campaign;
use App\Models\SurveyZone;
use Illuminate\Support\Facades\DB;

use function Livewire\Volt\computed;
use function Livewire\Volt\mount;
use function Livewire\Volt\on;
use function Livewire\Volt\state;

state([
    'campaignId' => null,
    'zones' => [],
    'editingZoneId' => null,
    'zoneName' => '',
    'zoneDescription' => '',
    'selectedZoneForDeletion' => null,
    'showDeleteModal' => false,
]);

mount(function (int $campaignId): void {
    $this->campaignId = $campaignId;
    $this->loadZones();
});

$campaign = computed(function () {
    $campaign = Campaign::findOrFail($this->campaignId);

    return [
        'id' => $campaign->id,
        'name' => $campaign->name,
        'description' => $campaign->description,
    ];
});

$loadZones = function (): void {
    $this->zones = SurveyZone::where('campaign_id', $this->campaignId)
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function (SurveyZone $zone) {
            return [
                'id' => $zone->id,
                'name' => $zone->name,
                'description' => $zone->description,
                'area_km2' => (float) $zone->area_km2,
                'geojson' => $zone->toGeoJSON(),
            ];
        })
        ->toArray();
};

$saveZone = function (array $geoJson, string $name, ?string $description = null): void {
    $zone = SurveyZone::create([
        'campaign_id' => $this->campaignId,
        'name' => $name,
        'description' => $description,
        'area_km2' => 0,
    ]);

    $coordinates = $geoJson['geometry']['coordinates'][0];

    $points = array_map(function (array $coord) {
        return "{$coord[0]} {$coord[1]}";
    }, $coordinates);

    $wkt = 'POLYGON(('.implode(', ', $points).'))';

    DB::statement(
        'UPDATE survey_zones SET area = ST_GeogFromText(?) WHERE id = ?',
        [$wkt, $zone->id]
    );

    $calculatedArea = $zone->fresh()->calculateArea();
    $zone->update(['area_km2' => $calculatedArea]);

    $this->loadZones();

    session()->flash('success', "Survey zone '{$name}' created successfully!");
    $this->dispatch('zonesUpdated');
};

$updateZone = function (int $zoneId, string $name, ?string $description = null): void {
    $zone = SurveyZone::findOrFail($zoneId);

    $zone->update([
        'name' => $name,
        'description' => $description,
    ]);

    $this->loadZones();
    $this->editingZoneId = null;

    session()->flash('success', "Survey zone '{$name}' updated successfully!");
    $this->dispatch('zonesUpdated');
};

$deleteZone = function (int $zoneId): void {
    $zone = SurveyZone::findOrFail($zoneId);
    $zoneName = $zone->name;

    $zone->delete();

    $this->loadZones();
    $this->selectedZoneForDeletion = null;
    $this->showDeleteModal = false;

    session()->flash('success', "Survey zone '{$zoneName}' deleted successfully!");
};

$startEditing = function (int $zoneId): void {
    $zone = SurveyZone::findOrFail($zoneId);
    $this->editingZoneId = $zoneId;
    $this->zoneName = $zone->name;
    $this->zoneDescription = $zone->description ?? '';
};

$cancelEditing = function (): void {
    $this->editingZoneId = null;
    $this->zoneName = '';
    $this->zoneDescription = '';
};

$confirmDeleteZone = function (int $zoneId): void {
    $this->selectedZoneForDeletion = $zoneId;
    $this->showDeleteModal = true;
};

$cancelDeleteZone = function (): void {
    $this->showDeleteModal = false;
    $this->selectedZoneForDeletion = null;
};

// Get data points GeoJSON for the campaign (to overlay on map)
$dataPointsGeoJSON = computed(function () {
    $geospatialService = app(\App\Services\GeospatialService::class);

    return $geospatialService->getDataPointsAsGeoJSON($this->campaignId);
});

?>

<div class="min-h-screen">
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="lg">Manage Survey Zones</flux:heading>
                <flux:subheading>{{ $this->campaign['name'] }}</flux:subheading>
            </div>
            <flux:button href="{{ route('dashboard') }}" variant="ghost">
                ← Back to Dashboard
            </flux:button>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Map Editor (2/3 width on large screens) --}}
        <div class="lg:col-span-2">
            <x-card>
                <flux:heading size="md" class="mb-4">Draw Survey Zone</flux:heading>

                <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <strong>How to draw a zone:</strong>
                    </p>
                    <ol class="text-sm text-blue-800 dark:text-blue-200 list-decimal list-inside mt-2 space-y-1">
                        <li>Click the <strong>polygon icon (⬟)</strong> in the top-right toolbar</li>
                        <li>Click on the map to place each corner point (add as many as needed)</li>
                        <li><strong>To finish:</strong> Double-click the last point OR click on the first point</li>
                        <li>Enter a name for the zone when prompted</li>
                    </ol>
                </div>

                {{-- Map Container --}}
                <div id="zone-editor-map" class="h-[600px] rounded-lg border border-zinc-200 dark:border-zinc-700" wire:ignore></div>

                {{-- Hidden data container for zones --}}
                <div id="zone-data-container" style="display: none;"
                     data-zones="{{ json_encode($zones ?? []) }}"
                     data-datapoints="{{ json_encode($this->dataPointsGeoJSON ?? ['type' => 'FeatureCollection', 'features' => []]) }}"
                     data-campaign-id="{{ $campaignId }}"
                     wire:key="zones-{{ md5(json_encode($zones)) }}">
                </div>
            </x-card>
        </div>

        {{-- Zone List (1/3 width on large screens) --}}
        <div class="lg:col-span-1">
            <x-card>
                <flux:heading size="md" class="mb-4">Existing Zones ({{ count($zones) }})</flux:heading>

                @if (empty($zones))
                    <div class="text-center py-8 text-zinc-500 dark:text-zinc-400">
                        <p class="text-sm">No survey zones yet.</p>
                        <p class="text-xs mt-1">Draw a polygon on the map to create one.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($zones as $zone)
                            <div class="p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:border-blue-300 dark:hover:border-blue-700 transition-colors">
                                @if ($editingZoneId === $zone['id'])
                                    {{-- Edit Mode --}}
                                    <div class="space-y-2">
                                        <flux:input
                                            wire:model="zoneName"
                                            placeholder="Zone name"
                                            label="Name"
                                        />
                                        <flux:textarea
                                            wire:model="zoneDescription"
                                            placeholder="Optional description"
                                            label="Description"
                                            rows="2"
                                        />
                                        <div class="flex gap-2">
                                            <flux:button
                                                size="sm"
                                                variant="primary"
                                                wire:click="updateZone({{ $zone['id'] }}, $wire.zoneName, $wire.zoneDescription)"
                                            >
                                                Save
                                            </flux:button>
                                            <flux:button
                                                size="sm"
                                                variant="ghost"
                                                wire:click="cancelEditing"
                                            >
                                                Cancel
                                            </flux:button>
                                        </div>
                                    </div>
                                @else
                                    {{-- View Mode --}}
                                    <div>
                                        <h4 class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">
                                            {{ $zone['name'] }}
                                        </h4>
                                        @if ($zone['description'])
                                            <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">
                                                {{ $zone['description'] }}
                                            </p>
                                        @endif
                                        <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-1">
                                            Area: {{ number_format($zone['area_km2'], 2) }} km²
                                        </p>

                                        <div class="flex gap-2 mt-2">
                                            <flux:button
                                                size="sm"
                                                variant="ghost"
                                                wire:click="startEditing({{ $zone['id'] }})"
                                            >
                                                Edit
                                            </flux:button>
                                            <flux:button
                                                size="xs"
                                                variant="danger"
                                                wire:click="confirmDeleteZone({{ $zone['id'] }})"
                                            >
                                                Delete
                                            </flux:button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <flux:heading size="lg">Delete Survey Zone?</flux:heading>

        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-2">
            Are you sure you want to delete this survey zone? This action cannot be undone.
        </p>

        <div class="flex gap-2 mt-4">
            <flux:button
                variant="danger"
                wire:click="deleteZone({{ $selectedZoneForDeletion ?? 0 }})"
            >
                Delete Zone
            </flux:button>
            <flux:button
                variant="ghost"
                wire:click="cancelDeleteZone"
            >
                Cancel
            </flux:button>
        </div>
    </flux:modal>

    {{-- Zone Creation Modal (custom, not Flux) --}}
    <div id="zone-creation-modal" class="hidden fixed inset-0 bg-black/50 dark:bg-black/70 z-[9999] flex items-center justify-center">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full mx-4 p-6 relative z-[10000]">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Create Survey Zone</h3>

            <div class="space-y-4">
                <div>
                    <label for="zone-name-input" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Zone Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="zone-name-input"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="e.g., Downtown Area"
                        required
                    />
                </div>

                <div>
                    <label for="zone-description-input" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Description <span class="text-zinc-500 text-xs">(optional)</span>
                    </label>
                    <textarea
                        id="zone-description-input"
                        rows="3"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Add any notes about this zone..."
                    ></textarea>
                </div>
            </div>

            <div class="flex gap-2 mt-6">
                <button
                    id="save-zone-btn"
                    class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors"
                >
                    Save Zone
                </button>
                <button
                    id="cancel-zone-btn"
                    class="flex-1 px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-900 dark:text-zinc-100 font-medium rounded-md transition-colors"
                >
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

@script
<script>
    (function() {
        // Initialize zone editor map when Livewire component loads
        if (!window.zoneEditorInitialized) {
            window.initZoneEditorMap();
            window.zoneEditorInitialized = true;
        }

        // Listen for Livewire updates to refresh zones
        Livewire.on('zonesUpdated', () => {
            if (window.updateZoneEditorMap) {
                window.updateZoneEditorMap();
            }
        });
    })();
</script>
@endscript

