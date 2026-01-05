<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\EnvironmentalMetric;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DataPoint>
 */
class DataPointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Colorado coordinates (Denver area)
        $latitude = fake()->latitude(39.5, 40.0);
        $longitude = fake()->longitude(-105.5, -104.5);

        return [
            'campaign_id' => Campaign::factory(),
            'environmental_metric_id' => EnvironmentalMetric::factory(),
            'user_id' => User::factory(),
            'value' => fake()->randomFloat(2, 0, 100),
            'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$longitude}, {$latitude}), 4326)"),
            'accuracy' => fake()->randomFloat(2, 5, 50),
            'notes' => fake()->optional()->sentence(),
            'collected_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
