<?php

namespace App\Jobs;

use App\Models\DataPoint;
use App\Models\SatelliteAnalysis;
use App\Services\CopernicusDataSpaceService;
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
    public function handle(CopernicusDataSpaceService $copernicusService): void
    {
        try {
            // Extract coordinates from PostGIS geometry
            $coordinates = DB::selectOne(
                'SELECT ST_Y(location::geometry) as latitude, ST_X(location::geometry) as longitude FROM data_points WHERE id = ?',
                [$this->dataPoint->id]
            );

            if (! $coordinates) {
                Log::warning("DataPoint {$this->dataPoint->id} has no valid location");

                return;
            }

            $latitude = $coordinates->latitude;
            $longitude = $coordinates->longitude;
            $date = $this->dataPoint->collected_at->format('Y-m-d');

            // Fetch NDVI data
            try {
                $ndviData = $copernicusService->getNDVIData($latitude, $longitude, $date);

                if ($ndviData) {
                    SatelliteAnalysis::create([
                        'data_point_id' => $this->dataPoint->id,
                        'campaign_id' => $this->dataPoint->campaign_id,
                        'ndvi_value' => $ndviData['ndvi'],
                        'ndvi_interpretation' => $ndviData['interpretation'],
                        'image_url' => $ndviData['image_url'] ?? null,
                        'acquisition_date' => $date,
                        'satellite_source' => 'Copernicus Sentinel-2',
                        'cloud_coverage_percent' => $ndviData['cloud_coverage'] ?? null,
                        'location' => DB::raw("ST_SetSRID(ST_MakePoint({$longitude}, {$latitude}), 4326)"),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to fetch NDVI for DataPoint {$this->dataPoint->id}: ".$e->getMessage());
            }

            // Fetch moisture data (NDMI)
            try {
                $moistureData = $copernicusService->getMoistureData($latitude, $longitude, $date);

                if ($moistureData) {
                    SatelliteAnalysis::create([
                        'data_point_id' => $this->dataPoint->id,
                        'campaign_id' => $this->dataPoint->campaign_id,
                        'moisture_index' => $moistureData['moisture'],
                        'image_url' => $moistureData['image_url'] ?? null,
                        'acquisition_date' => $date,
                        'satellite_source' => 'Copernicus Sentinel-2',
                        'cloud_coverage_percent' => $moistureData['cloud_coverage'] ?? null,
                        'location' => DB::raw("ST_SetSRID(ST_MakePoint({$longitude}, {$latitude}), 4326)"),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to fetch moisture data for DataPoint {$this->dataPoint->id}: ".$e->getMessage());
            }

            Log::info("Successfully enriched DataPoint {$this->dataPoint->id} with satellite data");

        } catch (\Exception $e) {
            Log::error("Failed to enrich DataPoint {$this->dataPoint->id}: ".$e->getMessage());
            throw $e;
        }
    }
}
