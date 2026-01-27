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
        // Copenhagen coordinates (default area)
        $latitude = $this->faker->latitude(55.6, 55.7);
        $longitude = $this->faker->longitude(12.5, 12.6);

        return [
            'campaign_id' => Campaign::factory(),
            'environmental_metric_id' => EnvironmentalMetric::factory(),
            'user_id' => User::factory(),
            'value' => $this->faker->randomFloat(2, 0, 100),
            'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$longitude}, {$latitude}), 4326)"),
            'accuracy' => $this->faker->randomFloat(2, 5, 50),
            'notes' => $this->faker->optional()->sentence(),
            'collected_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'status' => $this->faker->randomElement(['draft', 'pending', 'approved', 'rejected']),
            'review_notes' => $this->faker->optional()->sentence(),
            'device_model' => $this->faker->optional()->randomElement(['iPhone 14', 'Samsung Galaxy S23', 'Pixel 7', 'Manual Entry']),
            'sensor_type' => $this->faker->optional()->randomElement(['GPS', 'Mobile Device', 'Survey Equipment', 'Manual']),
            'calibration_at' => $this->faker->optional()->dateTimeBetween('-3 months', 'now'),
            'protocol_version' => '1.0',
        ];
    }

    /**
     * Set specific coordinates for the data point.
     */
    public function withCoordinates(float $latitude, float $longitude): static
    {
        return $this->state(fn (array $attributes) => [
            'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$longitude}, {$latitude}), 4326)"),
        ]);
    }

    /**
     * Indicate that the data point is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_by' => User::factory(),
            'reviewed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'review_notes' => 'Data quality verified and approved.',
        ]);
    }

    /**
     * Indicate that the data point is high quality (approved with good accuracy).
     */
    public function highQuality(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'accuracy' => $this->faker->randomFloat(2, 5, 20),
            'reviewed_by' => User::factory(),
            'reviewed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'review_notes' => 'High quality data - approved.',
        ]);
    }
}
