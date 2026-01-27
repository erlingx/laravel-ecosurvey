<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SurveyZone>
 */
class SurveyZoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'area_km2' => fake()->randomFloat(2, 0.5, 50),
        ];
    }

    /**
     * Configure the model factory to set PostGIS geometry after creation
     */
    public function configure(): static
    {
        return $this->afterCreating(function ($surveyZone) {
            // Generate a simple rectangular polygon around Copenhagen area
            // Center: 55.6761, 12.5683
            $centerLat = 55.6761 + (fake()->randomFloat(4, -0.05, 0.05));
            $centerLon = 12.5683 + (fake()->randomFloat(4, -0.05, 0.05));
            $size = 0.01; // ~1km box

            $minLat = $centerLat - $size;
            $maxLat = $centerLat + $size;
            $minLon = $centerLon - $size;
            $maxLon = $centerLon + $size;

            // Create polygon using PostGIS (WKT format)
            $wkt = "POLYGON(({$minLon} {$minLat}, {$maxLon} {$minLat}, {$maxLon} {$maxLat}, {$minLon} {$maxLat}, {$minLon} {$minLat}))";

            DB::statement(
                'UPDATE survey_zones SET area = ST_GeogFromText(?) WHERE id = ?',
                [$wkt, $surveyZone->id]
            );

            // Update calculated area
            $calculatedArea = $surveyZone->calculateArea();
            $surveyZone->update(['area_km2' => $calculatedArea]);
        });
    }
}
