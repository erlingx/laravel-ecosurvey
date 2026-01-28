<?php

namespace App\Jobs;

use App\Models\DataPoint;
use App\Models\SatelliteAnalysis;
use App\Services\CopernicusDataSpaceService;
use App\Services\UsageTrackingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnrichDataPointWithSatelliteData implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public DataPoint $dataPoint
    ) {}

    /**
     * Execute the job.
     */
    public function handle(CopernicusDataSpaceService $copernicusService, UsageTrackingService $usageService): void
    {
        Log::info('🛰️ Starting satellite enrichment job', [
            'data_point_id' => $this->dataPoint->id,
            'latitude' => $this->dataPoint->latitude ?? 'not set',
            'longitude' => $this->dataPoint->longitude ?? 'not set',
        ]);

        // Check if user can perform satellite analysis
        $user = $this->dataPoint->user;
        if (! $usageService->canPerformAction($user, 'satellite_analyses')) {
            Log::warning("User {$user->id} has reached satellite analysis limit for DataPoint {$this->dataPoint->id}");

            return;
        }

        try {
            // Extract coordinates from PostGIS geometry
            $coordinates = DB::selectOne(
                'SELECT ST_Y(location::geometry) as latitude, ST_X(location::geometry) as longitude FROM data_points WHERE id = ?',
                [$this->dataPoint->id]
            );

            if (! $coordinates || $coordinates->latitude === null || $coordinates->longitude === null) {
                Log::warning("DataPoint {$this->dataPoint->id} has no valid location");

                return;
            }

            $latitude = (float) $coordinates->latitude;
            $longitude = (float) $coordinates->longitude;
            $date = $this->dataPoint->collected_at->format('Y-m-d');

            // Fetch all 7 satellite indices
            $ndvi = $copernicusService->getNDVIData($latitude, $longitude, $date);
            $ndmi = $copernicusService->getMoistureData($latitude, $longitude, $date);
            $ndre = $copernicusService->getNDREData($latitude, $longitude, $date);
            $evi = $copernicusService->getEVIData($latitude, $longitude, $date);
            $msi = $copernicusService->getMSIData($latitude, $longitude, $date);
            $savi = $copernicusService->getSAVIData($latitude, $longitude, $date);
            $gndvi = $copernicusService->getGNDVIData($latitude, $longitude, $date);

            // Check if we got at least one index
            if (! $ndvi && ! $ndmi && ! $ndre && ! $evi && ! $msi && ! $savi && ! $gndvi) {
                Log::warning("No satellite data available for DataPoint {$this->dataPoint->id}");

                return;
            }

            // Create single SatelliteAnalysis record with all indices
            SatelliteAnalysis::create([
                'data_point_id' => $this->dataPoint->id,
                'campaign_id' => $this->dataPoint->campaign_id,
                'ndvi_value' => $ndvi['value'] ?? null,
                'moisture_index' => $ndmi['value'] ?? null,
                'ndre_value' => $ndre['value'] ?? null,
                'evi_value' => $evi['value'] ?? null,
                'msi_value' => $msi['value'] ?? null,
                'savi_value' => $savi['value'] ?? null,
                'gndvi_value' => $gndvi['value'] ?? null,
                'acquisition_date' => $ndvi['date'] ?? $ndmi['date'] ?? $date,
                'satellite_source' => 'Sentinel-2 L2A',
                'cloud_coverage_percent' => $ndvi['cloud_coverage'] ?? $ndmi['cloud_coverage'] ?? null,
                'location' => DB::raw("ST_SetSRID(ST_MakePoint({$longitude}, {$latitude}), 4326)"),
                'metadata' => [
                    'indices_fetched' => array_keys(array_filter([
                        'ndvi' => $ndvi,
                        'ndmi' => $ndmi,
                        'ndre' => $ndre,
                        'evi' => $evi,
                        'msi' => $msi,
                        'savi' => $savi,
                        'gndvi' => $gndvi,
                    ])),
                    'fetch_date' => now()->toIso8601String(),
                ],
            ]);

            // Record usage for satellite analysis
            $usageService->recordSatelliteAnalysis($user, 'all_indices');

            // Log which indices were fetched successfully
            $fetched = collect(compact('ndvi', 'ndmi', 'ndre', 'evi', 'msi', 'savi', 'gndvi'))
                ->filter(fn ($v) => ! is_null($v))
                ->keys()
                ->map(fn ($k) => strtoupper($k))
                ->implode(', ');

            Log::info("✅ Enriched DataPoint {$this->dataPoint->id} with satellite indices: {$fetched}");

        } catch (\Exception $e) {
            Log::error("Failed to enrich DataPoint {$this->dataPoint->id}: ".$e->getMessage());
            throw $e;
        }
    }
}
