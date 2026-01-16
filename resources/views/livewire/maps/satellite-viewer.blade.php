<?php

use App\Models\Campaign;
use App\Services\CopernicusDataSpaceService;
use App\Services\GeospatialService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function Livewire\Volt\computed;
use function Livewire\Volt\state;

state([
    'campaignId' => null,
    'selectedDate' => '2025-08-15', // August 15, 2025 (confirmed good Sentinel-2 data for Fælledparken)
    'overlayType' => '', // Start with no overlay selected
    'selectedLat' => 55.7072, // Fælledparken (Copenhagen's park with vegetation)
    'selectedLon' => 12.5704,
    'updateRevision' => 0, // Track updates to force re-render
    'showDataPoints' => false, // Start with data points hidden (no campaign selected)
]);

// Update location when campaign changes
$updatedCampaignId = function (): void {
    Log::info('🎯 Campaign changed', ['id' => $this->campaignId]);

    $defaultLat = 55.7072;
    $defaultLon = 12.5704;

    $lat = $defaultLat;
    $lon = $defaultLon;
    $source = 'default';

    if ($this->campaignId) {
        $campaign = Campaign::with(['surveyZones', 'dataPoints'])->find($this->campaignId);

        if ($campaign) {
            // Priority 1: Use survey zone centroid if exists
            if ($campaign->surveyZones->isNotEmpty()) {
                $zone = $campaign->surveyZones->first();
                $zone->refresh(); // Ensure geometry is loaded
                $centroid = $zone->getCentroid();

                if ($centroid && count($centroid) === 2) {
                    $lon = (float) $centroid[0]; // longitude
                    $lat = (float) $centroid[1]; // latitude
                    $source = 'survey_zone';

                    Log::info('✅ Using survey zone centroid', [
                        'zone_id' => $zone->id,
                        'zone_name' => $zone->name,
                        'lat' => $lat,
                        'lon' => $lon,
                    ]);
                }
            }
            // Priority 2: Use first datapoint location
            elseif ($campaign->dataPoints->isNotEmpty()) {
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
                    $source = 'datapoint';

                    Log::info('✅ Using datapoint location', [
                        'datapoint_id' => $dataPoint->id,
                        'lat' => $lat,
                        'lon' => $lon,
                    ]);
                }
            }
        }
    }

    // Log final decision
    Log::info('📍 Location selected', [
        'source' => $source,
        'lat' => $lat,
        'lon' => $lon,
    ]);

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

// Jump to data point (called from map click)
// Always syncs date for temporal correlation analysis (scientific best practice)
$jumpToDataPoint = function (float $latitude, float $longitude, string $date): void {
    Log::info('🎯 Jumping to data point for temporal correlation', [
        'lat' => $latitude,
        'lon' => $longitude,
        'date' => $date,
    ]);

    // Update coordinates
    $this->selectedLat = $latitude;
    $this->selectedLon = $longitude;

    // Always update date for temporal correlation (scientific best practice)
    $this->selectedDate = $date;
    Log::info('📅 Date synced to field data collection date for temporal analysis', ['date' => $date]);

    // Force revision update to trigger map refresh
    $this->updateRevision = (int) $this->updateRevision + 1;

    Log::info('✅ Jump completed - ready for temporal correlation analysis', [
        'newLat' => $this->selectedLat,
        'newLon' => $this->selectedLon,
        'newDate' => $this->selectedDate,
        'revision' => $this->updateRevision,
    ]);
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

    // Don't load satellite data if no overlay type is selected
    if (empty($overlay)) {
        Log::info('ℹ️ No overlay type selected - skipping satellite data load');

        return null;
    }

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

    // Don't load analysis data if no overlay type is selected
    if (empty($overlay)) {
        Log::info('ℹ️ No overlay type selected - skipping analysis data load');

        return null;
    }

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
        'ndre' => $copernicusService->getNDREData($lat, $lon, $date),
        'evi' => $copernicusService->getEVIData($lat, $lon, $date),
        'msi' => $copernicusService->getMSIData($lat, $lon, $date),
        'savi' => $copernicusService->getSAVIData($lat, $lon, $date),
        'gndvi' => $copernicusService->getGNDVIData($lat, $lon, $date),
        default => null,
    };
});

// Load data points GeoJSON for selected campaign
$dataPointsGeoJSON = computed(function () {
    if (! $this->campaignId || ! $this->showDataPoints) {
        Log::info('🗺️ Satellite Viewer: Not loading data points', [
            'campaignId' => $this->campaignId,
            'showDataPoints' => $this->showDataPoints,
        ]);

        return null;
    }

    $geospatialService = app(GeospatialService::class);
    $geoJSON = $geospatialService->getDataPointsAsGeoJSON($this->campaignId);

    Log::info('🗺️ Satellite Viewer: Loaded data points', [
        'campaignId' => $this->campaignId,
        'totalFeatures' => count($geoJSON['features'] ?? []),
    ]);

    return $geoJSON;
});

// Load survey zones GeoJSON for selected campaign
$surveyZonesGeoJSON = computed(function () {
    if (! $this->campaignId) {
        return null;
    }

    $campaign = Campaign::with('surveyZones')->find($this->campaignId);

    if (! $campaign || $campaign->surveyZones->isEmpty()) {
        return null;
    }

    $features = [];
    foreach ($campaign->surveyZones as $zone) {
        $feature = $zone->toGeoJSON();
        if ($feature && isset($feature['geometry'])) {
            $features[] = $feature;
        }
    }

    if (empty($features)) {
        return null;
    }

    Log::info('🗺️ Satellite Viewer: Loaded survey zones', [
        'campaignId' => $this->campaignId,
        'zoneCount' => count($features),
    ]);

    return [
        'type' => 'FeatureCollection',
        'features' => $features,
    ];
});

// Save satellite analysis to database
$saveSatelliteAnalysis = function (): void {
    $satelliteData = $this->satelliteData;
    $analysisData = $this->analysisData;

    if (! $satelliteData) {
        Log::warning('No satellite data to save');

        return;
    }

    try {
        $analysis = \App\Models\SatelliteAnalysis::create([
            'campaign_id' => $this->campaignId,
            'data_point_id' => null, // Can be linked later
            'location' => DB::raw("ST_SetSRID(ST_MakePoint({$this->selectedLon}, {$this->selectedLat}), 4326)"),
            'image_url' => $satelliteData['image_url'] ?? null,
            'ndvi_value' => $analysisData['ndvi'] ?? null,
            'ndvi_interpretation' => $analysisData['interpretation'] ?? null,
            'moisture_index' => $analysisData['moisture_index'] ?? null,
            'temperature_kelvin' => $analysisData['temperature'] ?? null,
            'acquisition_date' => $this->selectedDate,
            'satellite_source' => 'Copernicus',
            'processing_level' => $satelliteData['processing_level'] ?? 'L2A',
            'cloud_coverage_percent' => $satelliteData['cloud_coverage'] ?? null,
            'metadata' => [
                'overlay_type' => $this->overlayType,
                'platform' => $satelliteData['platform'] ?? 'Sentinel-2',
                'provider' => $satelliteData['provider'] ?? 'Copernicus Data Space',
            ],
        ]);

        Log::info('✅ Satellite analysis saved', ['id' => $analysis->id]);

        $this->dispatch('satellite-saved', id: $analysis->id);
    } catch (\Exception $e) {
        Log::error('Failed to save satellite analysis', ['error' => $e->getMessage()]);
    }
};

?>

<div class="min-h-screen"
     x-data
     @jump-to-datapoint.window="$wire.jumpToDataPoint($event.detail.latitude, $event.detail.longitude, $event.detail.date)">
    <div class="h-[calc(100vh-8rem)]">
        <x-card class="h-full flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <flux:heading size="lg">Satellite Data Viewer</flux:heading>
                    <flux:subheading>Copernicus Sentinel-2 imagery and NDVI analysis</flux:subheading>
                </div>

                <div class="flex gap-2 items-center">
                    @if($this->satelliteData)
                        <flux:badge variant="outline">
                            {{ \Carbon\Carbon::parse($this->satelliteData['date'])->format('M d, Y') }}
                        </flux:badge>
                    @endif

                    @if($overlayType === 'ndvi')
                        <flux:badge color="green">🌿 Vegetation</flux:badge>
                    @elseif($overlayType === 'moisture')
                        <flux:badge color="blue">💧 Moisture</flux:badge>
                    @elseif($overlayType === 'truecolor')
                        <flux:badge color="zinc">🌍 True Color</flux:badge>
                    @endif
                </div>
            </div>

            {{-- Filters --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div>
                    <label for="campaign-select" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Campaign Location
                        <flux:tooltip content="Filter view to specific research campaign">
                            <span class="ml-1 text-zinc-400 cursor-help">ⓘ</span>
                        </flux:tooltip>
                    </label>
                    <x-select
                        id="campaign-select"
                        wire:model.live="campaignId"
                    >
                        <option value="">Select a campaign...</option>
                        @foreach($this->campaigns as $campaign)
                            <option value="{{ $campaign->id }}">
                                {{ $campaign->name }} ({{ $campaign->location_preview }})
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <div>
                    <label for="overlay-select" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Data Overlay
                        <flux:tooltip content="Choose satellite visualization type: vegetation health, soil moisture, or natural color">
                            <span class="ml-1 text-zinc-400 cursor-help">ⓘ</span>
                        </flux:tooltip>
                    </label>
                    <x-select
                        id="overlay-select"
                        wire:model.live="overlayType"
                    >
                        <option value="">Select overlay type...</option>
                        <option value="ndvi">🌿 NDVI - Vegetation Index</option>
                        <option value="moisture">💧 Moisture Index (NDMI)</option>
                        <option value="ndre">🌱 NDRE - Chlorophyll Content (R²=0.85)</option>
                        <option value="evi">🌳 EVI - Enhanced Vegetation (Dense Canopy)</option>
                        <option value="msi">🏜️ MSI - Moisture Stress</option>
                        <option value="savi">🌾 SAVI - Soil-Adjusted Vegetation</option>
                        <option value="gndvi">💚 GNDVI - Green Vegetation</option>
                        <option value="truecolor">🌍 True Color</option>
                    </x-select>
                </div>

                <div>
                    <label for="satellite-date" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Imagery Date
                        <flux:tooltip content="Select satellite image acquisition date (cloud-free images may be limited)">
                            <span class="ml-1 text-zinc-400 cursor-help">ⓘ</span>
                        </flux:tooltip>
                    </label>
                    <flux:input
                        type="date"
                        id="satellite-date"
                        wire:model.live="selectedDate"
                        max="{{ now()->format('Y-m-d') }}"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                        Display Options
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 p-2.5 bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <input
                                type="checkbox"
                                wire:model.live="showDataPoints"
                                class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500"
                            />
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Show Field Data</span>
                            <flux:tooltip content="Overlay manual measurements on satellite imagery. Click 'View satellite on [DATE]' in marker popup to compare field data with satellite from that day.">
                                <span class="ml-auto text-zinc-400 cursor-help text-xs">ⓘ</span>
                            </flux:tooltip>
                        </label>
                    </div>
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
                    @if($this->analysisData || $this->satelliteData)
                        <div>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">Source:</span>
                            <span class="ml-2 text-zinc-600 dark:text-zinc-400">
                                {{ $this->analysisData['source'] ?? $this->satelliteData['source'] ?? 'Satellite Data' }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Map Container --}}
            <div class="flex-1 relative min-h-125">
                <div id="satellite-map" class="absolute inset-0 rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700" wire:ignore></div>

                {{-- Loading Overlay - hidden by default, shows only during Livewire loading --}}
                <div wire:loading.class.remove="hidden" class="hidden absolute inset-0 bg-black/20 backdrop-blur-sm rounded-lg flex items-center justify-center" style="z-index: 9999;">
                    <div class="flex items-center gap-3 px-6 py-4 bg-white/10 rounded-lg backdrop-blur-md">
                        <svg class="animate-spin h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-medium text-white drop-shadow-lg">Loading satellite data...</span>
                    </div>
                </div>


                {{-- Temporal Proximity Legend - Shows when data points are visible --}}
                @if($showDataPoints && $this->dataPointsGeoJSON)
                    <div class="absolute top-4 right-4 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border border-zinc-200 dark:border-zinc-700 p-3 z-[1000] max-w-xs">
                        <div class="flex items-center gap-2 mb-2">
                            <h4 class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Temporal Alignment</h4>
                            <flux:tooltip content="Shows how close satellite observation is to field measurement (closer = better correlation)">
                                <span class="text-zinc-400 cursor-help text-xs">ⓘ</span>
                            </flux:tooltip>
                        </div>
                        <div class="space-y-1.5 text-xs">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full" style="background-color: #10b981; border: 2px solid #059669;"></div>
                                <span class="text-zinc-700 dark:text-zinc-300">0-3 days (Excellent)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full" style="background-color: #fbbf24; border: 2px solid #f59e0b;"></div>
                                <span class="text-zinc-700 dark:text-zinc-300">4-7 days (Good)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full" style="background-color: #fb923c; border: 2px solid #f97316;"></div>
                                <span class="text-zinc-700 dark:text-zinc-300">8-14 days (Acceptable)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full" style="background-color: #ef4444; border: 2px solid #dc2626;"></div>
                                <span class="text-zinc-700 dark:text-zinc-300">15+ days (Poor)</span>
                            </div>
                        </div>
                    </div>
                @endif
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

                {{-- NDRE Analysis Panel --}}
                @if($overlayType === 'ndre' && isset($this->analysisData['value']))
                    <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <h3 class="text-sm font-semibold text-green-900 dark:text-green-100 mb-2">
                            🌱 NDRE Analysis - Chlorophyll Content
                        </h3>

                        <p class="text-sm text-green-800 dark:text-green-200">
                            NDRE Value: <span class="font-mono font-semibold">{{ number_format($this->analysisData['value'], 3) }}</span>
                        </p>
                        <p class="text-sm text-green-800 dark:text-green-200 mt-1">
                            R² Correlation: <span class="font-medium">0.80-0.90</span>
                        </p>

                        <div class="mt-3 text-xs text-green-700 dark:text-green-300">
                            <p class="font-medium mb-1">NDRE Scale Reference:</p>
                            <ul class="space-y-0.5 ml-4">
                                <li>• &lt; 0: Low/no chlorophyll</li>
                                <li>• 0 - 0.3: Moderate chlorophyll</li>
                                <li>• 0.3 - 0.6: Good chlorophyll content</li>
                                <li>• &gt; 0.6: High chlorophyll (healthy vegetation)</li>
                            </ul>
                            <p class="mt-2 text-xs italic">NDRE = (NIR - RedEdge) / (NIR + RedEdge)</p>
                            <p class="text-xs">Bands: B05 (Red Edge 705nm), B08 (NIR)</p>
                        </div>
                    </div>
                @endif

                {{-- EVI Analysis Panel --}}
                @if($overlayType === 'evi' && isset($this->analysisData['value']))
                    <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <h3 class="text-sm font-semibold text-green-900 dark:text-green-100 mb-2">
                            🌳 EVI Analysis - Enhanced Vegetation Index
                        </h3>

                        <p class="text-sm text-green-800 dark:text-green-200">
                            EVI Value: <span class="font-mono font-semibold">{{ number_format($this->analysisData['value'], 3) }}</span>
                        </p>
                        <p class="text-sm text-green-800 dark:text-green-200 mt-1">
                            R² Correlation: <span class="font-medium">0.75-0.85 (LAI, FAPAR)</span>
                        </p>

                        <div class="mt-3 text-xs text-green-700 dark:text-green-300">
                            <p class="font-medium mb-1">EVI Scale Reference:</p>
                            <ul class="space-y-0.5 ml-4">
                                <li>• &lt; 0: Non-vegetated areas</li>
                                <li>• 0 - 0.3: Sparse vegetation</li>
                                <li>• 0.3 - 0.6: Moderate vegetation</li>
                                <li>• &gt; 0.6: Dense canopy (better than NDVI)</li>
                            </ul>
                            <p class="mt-2 text-xs italic">EVI = 2.5 × ((NIR - Red) / (NIR + 6×Red - 7.5×Blue + 1))</p>
                            <p class="text-xs">Bands: B02 (Blue), B04 (Red), B08 (NIR)</p>
                        </div>
                    </div>
                @endif

                {{-- MSI Analysis Panel --}}
                @if($overlayType === 'msi' && isset($this->analysisData['value']))
                    <div class="mt-4 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
                        <h3 class="text-sm font-semibold text-orange-900 dark:text-orange-100 mb-2">
                            🏜️ MSI Analysis - Moisture Stress Index
                        </h3>

                        <p class="text-sm text-orange-800 dark:text-orange-200">
                            MSI Value: <span class="font-mono font-semibold">{{ number_format($this->analysisData['value'], 3) }}</span>
                        </p>
                        <p class="text-sm text-orange-800 dark:text-orange-200 mt-1">
                            R² Correlation: <span class="font-medium">0.70-0.80 (Soil Moisture)</span>
                        </p>

                        <div class="mt-3 text-xs text-orange-700 dark:text-orange-300">
                            <p class="font-medium mb-1">MSI Scale Reference (inverse of moisture):</p>
                            <ul class="space-y-0.5 ml-4">
                                <li>• &lt; 0.4: Low stress (wet)</li>
                                <li>• 0.4 - 0.8: Moderate stress</li>
                                <li>• 0.8 - 1.6: High stress</li>
                                <li>• &gt; 1.6: Severe stress (very dry)</li>
                            </ul>
                            <p class="mt-2 text-xs italic">MSI = SWIR1 / NIR (higher = more stress)</p>
                            <p class="text-xs">Bands: B08 (NIR 842nm), B11 (SWIR1 1610nm)</p>
                        </div>
                    </div>
                @endif

                {{-- SAVI Analysis Panel --}}
                @if($overlayType === 'savi' && isset($this->analysisData['value']))
                    <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                        <h3 class="text-sm font-semibold text-amber-900 dark:text-amber-100 mb-2">
                            🌾 SAVI Analysis - Soil-Adjusted Vegetation
                        </h3>

                        <p class="text-sm text-amber-800 dark:text-amber-200">
                            SAVI Value: <span class="font-mono font-semibold">{{ number_format($this->analysisData['value'], 3) }}</span>
                        </p>
                        <p class="text-sm text-amber-800 dark:text-amber-200 mt-1">
                            R² Correlation: <span class="font-medium">0.70-0.80 (Sparse LAI)</span>
                        </p>

                        <div class="mt-3 text-xs text-amber-700 dark:text-amber-300">
                            <p class="font-medium mb-1">SAVI Scale Reference:</p>
                            <ul class="space-y-0.5 ml-4">
                                <li>• &lt; 0: Bare soil</li>
                                <li>• 0 - 0.2: Sparse vegetation</li>
                                <li>• 0.2 - 0.4: Moderate vegetation</li>
                                <li>• &gt; 0.4: Dense vegetation</li>
                            </ul>
                            <p class="mt-2 text-xs italic">SAVI = ((NIR - Red) / (NIR + Red + 0.5)) × 1.5</p>
                            <p class="text-xs">Corrects for soil brightness in sparse canopy</p>
                        </div>
                    </div>
                @endif

                {{-- GNDVI Analysis Panel --}}
                @if($overlayType === 'gndvi' && isset($this->analysisData['value']))
                    <div class="mt-4 p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-200 dark:border-emerald-800">
                        <h3 class="text-sm font-semibold text-emerald-900 dark:text-emerald-100 mb-2">
                            💚 GNDVI Analysis - Green Vegetation Index
                        </h3>

                        <p class="text-sm text-emerald-800 dark:text-emerald-200">
                            GNDVI Value: <span class="font-mono font-semibold">{{ number_format($this->analysisData['value'], 3) }}</span>
                        </p>
                        <p class="text-sm text-emerald-800 dark:text-emerald-200 mt-1">
                            R² Correlation: <span class="font-medium">0.75-0.85 (Chlorophyll)</span>
                        </p>

                        <div class="mt-3 text-xs text-emerald-700 dark:text-emerald-300">
                            <p class="font-medium mb-1">GNDVI Scale Reference:</p>
                            <ul class="space-y-0.5 ml-4">
                                <li>• &lt; 0: Low/no chlorophyll</li>
                                <li>• 0 - 0.3: Low chlorophyll</li>
                                <li>• 0.3 - 0.6: Moderate chlorophyll</li>
                                <li>• &gt; 0.6: High chlorophyll content</li>
                            </ul>
                            <p class="mt-2 text-xs italic">GNDVI = (NIR - Green) / (NIR + Green)</p>
                            <p class="text-xs">More sensitive to chlorophyll than NDVI</p>
                        </div>
                    </div>
                @endif

            {{-- Close analysisData condition --}}
            @endif

            {{-- True Color Info Panel - Outside analysisData condition since it has no analysis data --}}
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
        </x-card>
    </div>

    {{-- Hidden div that updates when filters change - triggers map update via data attribute changes --}}
    <div id="satellite-data-container" style="display: none;"
         data-imagery="{{ json_encode($this->satelliteData) }}"
         data-analysis="{{ json_encode($this->analysisData) }}"
         data-datapoints="{{ json_encode($this->dataPointsGeoJSON) }}"
         data-surveyzones="{{ json_encode($this->surveyZonesGeoJSON) }}"
         data-lat="{{ $selectedLat }}"
         data-lon="{{ $selectedLon }}"
         data-date="{{ $selectedDate }}"
         data-overlay-type="{{ $overlayType }}"
         data-revision="{{ $updateRevision }}"
         wire:key="satellite-update-{{ $updateRevision }}">
    </div>
</div>
