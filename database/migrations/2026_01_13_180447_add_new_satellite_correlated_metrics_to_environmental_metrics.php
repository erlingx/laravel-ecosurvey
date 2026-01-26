<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new environmental metrics that have excellent satellite coverage
        // These complement existing manual surveys with satellite validation opportunities

        $newMetrics = [
            // TIER 1: HIGH PRIORITY - Direct satellite correlation, low cost
            [
                'name' => 'Land Surface Temperature',
                'unit' => '°C',
                'description' => 'Surface temperature measured with IR thermometer (validates Landsat-8/9 LST)',
                'is_active' => true,
            ],
            [
                'name' => 'Nitrogen Dioxide (NO₂)',
                'unit' => 'µg/m³',
                'description' => 'NO₂ concentration from passive samplers or sensor (validates Sentinel-5P)',
                'is_active' => true,
            ],
            [
                'name' => 'Chlorophyll Content',
                'unit' => 'µg/cm²',
                'description' => 'Leaf chlorophyll content via SPAD meter (validates Sentinel-2 Red Edge bands)',
                'is_active' => true,
            ],

            // TIER 2: MEDIUM PRIORITY - Satellite products available
            [
                'name' => 'Leaf Area Index (LAI)',
                'unit' => 'm²/m²',
                'description' => 'Leaf area per ground area (validates Copernicus LAI product)',
                'is_active' => true,
            ],
            [
                'name' => 'Soil Moisture',
                'unit' => '% VWC',
                'description' => 'Volumetric water content (validates Sentinel-1 SAR and NDMI)',
                'is_active' => true,
            ],
            [
                'name' => 'Aerosol Optical Depth (AOD)',
                'unit' => 'dimensionless',
                'description' => 'Atmospheric aerosol measurement (validates Sentinel-5P and PM2.5 algorithms)',
                'is_active' => true,
            ],

            // TIER 3: ADDITIONAL ATMOSPHERIC METRICS
            [
                'name' => 'Ozone (O₃)',
                'unit' => 'ppb',
                'description' => 'Tropospheric ozone concentration (validates Sentinel-5P O₃)',
                'is_active' => true,
            ],
            [
                'name' => 'Sulfur Dioxide (SO₂)',
                'unit' => 'µg/m³',
                'description' => 'SO₂ concentration (validates Sentinel-5P SO₂)',
                'is_active' => true,
            ],

            // WATER QUALITY METRICS
            [
                'name' => 'Water Turbidity',
                'unit' => 'NTU',
                'description' => 'Water clarity measurement (validates Sentinel-2 water quality indices)',
                'is_active' => true,
            ],
            [
                'name' => 'Chlorophyll-a (Aquatic)',
                'unit' => 'mg/m³',
                'description' => 'Algae/phytoplankton concentration (validates Sentinel-2/3 ocean color)',
                'is_active' => true,
            ],

            // VEGETATION BIOPHYSICAL PARAMETERS
            [
                'name' => 'FAPAR',
                'unit' => 'dimensionless',
                'description' => 'Fraction of Absorbed Photosynthetically Active Radiation (validates Copernicus FAPAR)',
                'is_active' => true,
            ],
            [
                'name' => 'Canopy Chlorophyll Content',
                'unit' => 'g/m²',
                'description' => 'Total canopy chlorophyll (validates Sentinel-2 CCC product)',
                'is_active' => true,
            ],
        ];

        foreach ($newMetrics as $metric) {
            // Check if metric already exists before inserting
            $exists = DB::table('environmental_metrics')
                ->where('name', $metric['name'])
                ->exists();

            if (! $exists) {
                DB::table('environmental_metrics')->insert([
                    'name' => $metric['name'],
                    'unit' => $metric['unit'],
                    'description' => $metric['description'],
                    'is_active' => $metric['is_active'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the metrics added in up()
        $metricsToRemove = [
            'Land Surface Temperature',
            'Nitrogen Dioxide (NO₂)',
            'Chlorophyll Content',
            'Leaf Area Index (LAI)',
            'Soil Moisture',
            'Aerosol Optical Depth (AOD)',
            'Ozone (O₃)',
            'Sulfur Dioxide (SO₂)',
            'Water Turbidity',
            'Chlorophyll-a (Aquatic)',
            'FAPAR',
            'Canopy Chlorophyll Content',
        ];

        DB::table('environmental_metrics')
            ->whereIn('name', $metricsToRemove)
            ->delete();
    }
};
