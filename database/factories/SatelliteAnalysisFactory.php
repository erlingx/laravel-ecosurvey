<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\DataPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SatelliteAnalysis>
 */
class SatelliteAnalysisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Copenhagen area coordinates
        $latitude = $this->faker->latitude(55.5, 55.8);
        $longitude = $this->faker->longitude(12.3, 12.7);

        $ndviValue = $this->faker->randomFloat(4, -0.2, 0.9);

        return [
            'data_point_id' => null, // Can be linked to a DataPoint or standalone
            'campaign_id' => Campaign::factory(),
            'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$longitude}, {$latitude}), 4326)"),
            'image_url' => $this->faker->optional()->url(),
            'ndvi_value' => $ndviValue,
            'ndvi_interpretation' => $this->interpretNDVI($ndviValue),
            'moisture_index' => $this->faker->randomFloat(4, 0, 1),
            'temperature_kelvin' => $this->faker->randomFloat(2, 270, 310),
            'acquisition_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'satellite_source' => $this->faker->randomElement(['Copernicus', 'Sentinel-2', 'Landsat-8']),
            'processing_level' => $this->faker->randomElement(['L1C', 'L2A']),
            'cloud_coverage_percent' => $this->faker->randomFloat(2, 0, 30),
            'metadata' => [
                'platform' => 'Sentinel-2A',
                'instrument' => 'MSI',
                'resolution' => '10m',
            ],
        ];
    }

    private function interpretNDVI(float $ndvi): string
    {
        if ($ndvi < 0) {
            return 'Water or snow';
        } elseif ($ndvi < 0.2) {
            return 'Bare soil or urban area';
        } elseif ($ndvi < 0.4) {
            return 'Sparse vegetation';
        } elseif ($ndvi < 0.6) {
            return 'Moderate vegetation';
        } else {
            return 'Dense vegetation';
        }
    }
}
