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
            $geoJson = $zone->toGeoJSON();

            return [
                'id' => $zone->id,
                'name' => $zone->name,
                'description' => $zone->description,
                'area_km2' => (float) $zone->area_km2,
                'geojson' => json_decode(json_encode($geoJson), true),
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

$saveZoneData = function (string $geoJsonString, string $name, string $description = ''): void {
    $geoJson = json_decode($geoJsonString, true);

    if (! is_array($geoJson)) {
        return;
    }

    $this->saveZone($geoJson, $name, $description !== '' ? $description : null);
};

on(['saveZoneData' => function (array $payload) {
    $geoJson = $payload['geoJson'] ?? null;
    $name = (string) ($payload['name'] ?? '');
    $description = (string) ($payload['description'] ?? '');

    if (is_array($geoJson)) {
        $this->saveZone($geoJson, $name, $description !== '' ? $description : null);
    }

    if (is_string($geoJson)) {
        $this->saveZoneData($geoJson, $name, $description);
    }
}]);

// Method called directly from JavaScript via Livewire.call()
$updateZone = function (int $zoneId, string $name, ?string $description = null): void {
    $zone = SurveyZone::findOrFail($zoneId);

    $zone->update([
        'name' => $name,
        'description' => $description,
    ]);

    $this->loadZones();
    $this->editingZoneId = null;

    session()->flash('success', "Survey zone '{$name}' updated successfully!");
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

// Get data points GeoJSON for the campaign (to overlay on map)
$dataPointsGeoJSON = computed(function () {
    $geospatialService = app(\App\Services\GeospatialService::class);
    $geoJson = $geospatialService->getDataPointsAsGeoJSON($this->campaignId);
    // Convert to JSON string and back to ensure it's a plain array
    return json_decode(json_encode($geoJson), true);
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
                        <strong>How to use:</strong>
                        Click the <strong>polygon icon</strong> in the toolbar, then click on the map to draw zone boundaries.
                        Double-click or click the first point again to complete the polygon.
                    </p>
                </div>

                {{-- Map Container --}}
                <div id="zone-editor-map" class="h-[600px] rounded-lg border border-zinc-200 dark:border-zinc-700" wire:ignore></div>

                {{-- Hidden data container for zones --}}
                <div id="zone-data-container" style="display: none;"
                     data-zones="{{ json_encode($zones) }}"
                     data-datapoints="{{ json_encode($this->dataPointsGeoJSON) }}"
                     data-campaign-id="{{ $campaignId }}"
                     wire:key="zones-{{ count($zones) }}">
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
                                                wire:click="updateZone({{ $zone['id'] }}, '{{ addslashes($zoneName) }}', '{{ addslashes($zoneDescription) }}')"
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
                                                size="sm"
                                                variant="danger"
                                                wire:click="$set('selectedZoneForDeletion', {{ $zone['id'] }}); $set('showDeleteModal', true)"
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
                wire:click="$set('showDeleteModal', false)"
            >
                Cancel
            </flux:button>
        </div>
    </flux:modal>
</div>

@script
<script>
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
</script>
@endscript

