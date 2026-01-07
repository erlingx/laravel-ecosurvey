<?php

use App\Models\Campaign;
use App\Services\CopernicusDataSpaceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function Livewire\Volt\computed;
use function Livewire\Volt\state;

state([
    'campaignId' => null,
    'selectedDate' => '2025-08-15', // August 15, 2025 (confirmed good Sentinel-2 data for Fælledparken)
    'overlayType' => 'ndvi', // Options: 'ndvi', 'moisture', 'truecolor'
    'selectedLat' => 55.7072, // Fælledparken (Copenhagen's park with vegetation)
    'selectedLon' => 12.5704,
    'updateRevision' => 0, // Track updates to force re-render
]);

// Update location when campaign changes
$updatedCampaignId = function (): void {
    Log::info('🎯 Campaign changed', ['id' => $this->campaignId]);

    $defaultLat = 55.7072;
    $defaultLon = 12.5704;

    $lat = $defaultLat;
    $lon = $defaultLon;

    if ($this->campaignId) {
        $campaign = Campaign::with('dataPoints')->find($this->campaignId);

        if ($campaign && $campaign->dataPoints->isNotEmpty()) {
            $dataPoint = $campaign->dataPoints()
                ->select([
                    'data_points.*',
                    DB::raw('ST_X(location::geometry) as longitude'),
                    DB::raw('ST_Y(location::geometry) as latitude'),
                ])
                ->first();

            if ($dataPoint && $dataPoint->latitude !== null && $dataPoint->longitude !== null) {
                $lat = (float) $dataPoint->latitude;
                $lon = (float) $dataPoint->longitude;
            }
        }
    }

    $this->selectedLat = $lat;
    $this->selectedLon = $lon;

    $this->updateRevision = (int) $this->updateRevision + 1;

    Log::info('✅ Coordinates updated', [
        'lat' => $this->selectedLat,
        'lon' => $this->selectedLon,
        'revision' => $this->updateRevision,
    ]);
};

// Update revision when overlay changes
$updatedOverlayType = function (): void {
    Log::info('🎨 Overlay changed', ['type' => $this->overlayType]);
    $this->updateRevision = (int) $this->updateRevision + 1;
};

// Update revision when date changes
$updatedSelectedDate = function (): void {
    Log::info('📅 Date changed', ['date' => $this->selectedDate]);
    $this->updateRevision = (int) $this->updateRevision + 1;
};

$campaigns = computed(function () {
    return Campaign::query()
        ->select('campaigns.id', 'campaigns.name', 'campaigns.status')
        ->where('campaigns.status', 'active')
        ->whereHas('dataPoints') // Only show campaigns with data points
        ->withCount('dataPoints')
        ->orderBy('campaigns.name')
        ->get()
        ->map(function ($campaign) {
            // Get first data point location for display
            $dataPoint = $campaign->dataPoints()
                ->select([
                    'data_points.*',
                    DB::raw('ST_X(location::geometry) as longitude'),
                    DB::raw('ST_Y(location::geometry) as latitude'),
                ])
                ->first();

            $campaign->location_preview = $dataPoint
                ? sprintf('%.4f°N, %.4f°E', $dataPoint->latitude, $dataPoint->longitude)
                : 'No location';

            return $campaign;
        });
});

$satelliteData = computed(function () {
    // Force recomputation when location changes by reading state
    $lat = $this->selectedLat;
    $lon = $this->selectedLon;
    $date = $this->selectedDate;
    $overlay = $this->overlayType;
    $revision = $this->updateRevision; // Force recomputation when revision changes

    Log::info('🛰️ Computing satelliteData', [
        'lat' => $lat,
        'lon' => $lon,
        'date' => $date,
        'overlay' => $overlay,
        'campaignId' => $this->campaignId,
        'updateRevision' => $revision,
    ]);

    // Use overlay visualization based on selected type
    $copernicusService = app(CopernicusDataSpaceService::class);
    $data = $copernicusService->getOverlayVisualization(
        $lat,
        $lon,
        $date,
        $overlay
    );

    if ($data) {
        Log::info('✅ Copernicus data loaded', [
            'provider' => $data['provider'] ?? 'unknown',
            'returned_lat' => $data['latitude'] ?? 'N/A',
            'returned_lon' => $data['longitude'] ?? 'N/A',
            'overlay_type' => $data['overlay_type'] ?? 'N/A',
        ]);

        return $data;
    }

    Log::warning('⚠️ No Copernicus data available for location/date', [
        'lat' => $lat,
        'lon' => $lon,
        'date' => $date,
    ]);

    return null;
});

// Load analysis data based on current overlay type
$analysisData = computed(function () {
    // Only load analysis data for overlays that need it
    $overlay = $this->overlayType;

    // Don't load any analysis data for true color
    if ($overlay === 'truecolor') {
        return null;
    }

    // Force recomputation when location changes by reading state
    $lat = $this->selectedLat;
    $lon = $this->selectedLon;
    $date = $this->selectedDate;
    $revision = $this->updateRevision; // Force recomputation when revision changes

    Log::info('📊 Computing analysisData', [
        'lat' => $lat,
        'lon' => $lon,
        'overlay' => $overlay,
        'updateRevision' => $revision,
    ]);

    $copernicusService = app(CopernicusDataSpaceService::class);

    // Only fetch the specific data type needed
    return match ($overlay) {
        'ndvi' => $copernicusService->getNDVIData($lat, $lon, $date),
        'moisture' => $copernicusService->getMoistureData($lat, $lon, $date),
        default => null,
    };
});

?>

<div class="min-h-screen">
    <div class="h-[calc(100vh-8rem)]">
        <x-card class="h-full flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <flux:heading size="lg">Satellite Data Viewer</flux:heading>
                    <flux:subheading>Copernicus Sentinel-2 imagery and NDVI analysis</flux:subheading>
                </div>

                <div class="flex gap-2 items-center">
                    {{-- Loading indicator --}}
                    <div wire:loading class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Loading satellite data...</span>
                    </div>

                    @if($this->satelliteData)
                        <flux:badge variant="outline">
                            {{ \Carbon\Carbon::parse($this->satelliteData['date'])->format('M d, Y') }}
                        </flux:badge>
                    @endif

                    @if($overlayType === 'ndvi')
                        <flux:badge color="green">🌿 Vegetation</flux:badge>
                    @elseif($overlayType === 'moisture')
                        <flux:badge color="blue">💧 Moisture</flux:badge>
                    @else
                        <flux:badge color="zinc">🌍 True Color</flux:badge>
                    @endif
                </div>
            </div>

            {{-- Filters --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <x-select
                    label="Campaign Location"
                    wire:model.live="campaignId"
                >
                    <option value="">Fælledparken (Default - 55.7072°N, 12.5704°E)</option>
                    @foreach($this->campaigns as $campaign)
                        <option value="{{ $campaign->id }}">
                            {{ $campaign->name }} ({{ $campaign->location_preview }})
                        </option>
                    @endforeach
                </x-select>

                <x-select
                    label="Data Overlay"
                    wire:model.live="overlayType"
                >
                    <option value="ndvi">🌿 NDVI - Vegetation Index</option>
                    <option value="moisture">💧 Moisture Index</option>
                    <option value="truecolor">🌍 True Color</option>
                </x-select>

                <div>
                    <label for="satellite-date" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Imagery Date
                    </label>
                    <flux:input
                        type="date"
                        id="satellite-date"
                        wire:model.live="selectedDate"
                        max="{{ now()->format('Y-m-d') }}"
                    />
                </div>
            </div>

            {{-- API Status Notice --}}
            @if($this->satelliteData)
                @if(isset($this->satelliteData['provider']) && $this->satelliteData['provider'] === 'copernicus_dataspace')
                    <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm text-green-800 dark:text-green-200">
                                <p class="font-medium">✅ Copernicus Data Space Active</p>
                                <p class="text-xs mt-1">Using real Sentinel-2 satellite imagery from ESA Copernicus (FREE UNLIMITED, 10m resolution)</p>
                            </div>
                        </div>
                    </div>
                @elseif(isset($this->satelliteData['mock']))
                    <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm text-blue-800 dark:text-blue-200">
                                <p class="font-medium">Demo Mode</p>
                                <p class="text-xs mt-1">Using placeholder imagery. Add Copernicus credentials to .env to enable real satellite data.</p>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            {{-- Coordinates Display --}}
            <div class="mb-4 p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center justify-between text-sm">
                    <div>
                        <span class="font-medium text-zinc-700 dark:text-zinc-300">Location:</span>
                        <span class="ml-2 text-zinc-600 dark:text-zinc-400">
                            {{ number_format($selectedLat, 6) }}°N, {{ number_format($selectedLon, 6) }}°E
                        </span>
                    </div>
                    @if($this->analysisData)
                        <div>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">Source:</span>
                            <span class="ml-2 text-zinc-600 dark:text-zinc-400">
                                {{ $this->analysisData['source'] ?? 'Satellite Data' }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Map Container --}}
            <div class="flex-1 relative min-h-125" wire:ignore>
                <div id="satellite-map" class="absolute inset-0 rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700"></div>
            </div>

            {{-- Analysis Panel - Shows based on overlay type --}}
            @if($this->analysisData)
                {{-- NDVI Analysis Panel --}}
                @if($overlayType === 'ndvi' && isset($this->analysisData['ndvi_value']))
                    <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <h3 class="text-sm font-semibold text-green-900 dark:text-green-100 mb-2">
                            🌿 NDVI Analysis - Vegetation Index
                        </h3>

                        <p class="text-sm text-green-800 dark:text-green-200">
                            NDVI Value: <span class="font-mono font-semibold">{{ number_format($this->analysisData['ndvi_value'], 3) }}</span>
                        </p>
                        <p class="text-sm text-green-800 dark:text-green-200 mt-1">
                            Interpretation: <span class="font-medium">{{ $this->analysisData['interpretation'] }}</span>
                        </p>

                        <div class="mt-3 text-xs text-green-700 dark:text-green-300">
                            <p class="font-medium mb-1">NDVI Scale Reference:</p>
                            <ul class="space-y-0.5 ml-4">
                                <li>• &lt; 0: Water</li>
                                <li>• 0 - 0.1: Barren rock, sand, or snow</li>
                                <li>• 0.1 - 0.2: Shrub and grassland</li>
                                <li>• 0.2 - 0.3: Sparse vegetation</li>
                                <li>• 0.3 - 0.6: Moderate vegetation</li>
                                <li>• &gt; 0.6: Dense vegetation</li>
                            </ul>
                        </div>
                    </div>
                @endif

                {{-- Moisture Analysis Panel --}}
                @if($overlayType === 'moisture' && isset($this->analysisData['moisture_value']))
                    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                            💧 Soil Moisture Analysis (NDMI)
                        </h3>

                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            Moisture Index: <span class="font-mono font-semibold">{{ number_format($this->analysisData['moisture_value'], 3) }}</span>
                        </p>
                        <p class="text-sm text-blue-800 dark:text-blue-200 mt-1">
                            Interpretation: <span class="font-medium">{{ $this->analysisData['interpretation'] }}</span>
                        </p>

                        <div class="mt-3 text-xs text-blue-700 dark:text-blue-300">
                            <p class="font-medium mb-1">Moisture Scale Reference:</p>
                            <ul class="space-y-0.5 ml-4">
                                <li>• &lt; -0.4: Very dry</li>
                                <li>• -0.4 to -0.2: Dry</li>
                                <li>• -0.2 to 0: Moderate dry</li>
                                <li>• 0 to 0.2: Moderate wet</li>
                                <li>• 0.2 to 0.4: Wet</li>
                                <li>• &gt; 0.4: Very wet / Water bodies</li>
                            </ul>
                            <p class="mt-2 text-xs italic">NDMI = (NIR - SWIR) / (NIR + SWIR)</p>
                        </div>
                    </div>
                @endif

                {{-- True Color - No analysis panel --}}
                @if($overlayType === 'truecolor')
                    <div class="mt-4 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-2">
                            🌍 True Color RGB
                        </h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            Displaying natural color satellite imagery from Sentinel-2 (Bands B04, B03, B02).
                        </p>
                    </div>
                @endif
            @endif
        </x-card>
    </div>

    {{-- Hidden div that updates when filters change - triggers map update via data attribute changes --}}
    <div id="satellite-data-container" style="display: none;"
         data-imagery="{{ json_encode($this->satelliteData) }}"
         data-analysis="{{ json_encode($this->analysisData) }}"
         data-lat="{{ $selectedLat }}"
         data-lon="{{ $selectedLon }}"
         data-overlay-type="{{ $overlayType }}"
         data-revision="{{ $updateRevision }}"
         wire:key="satellite-update-{{ $updateRevision }}">
    </div>
</div>
