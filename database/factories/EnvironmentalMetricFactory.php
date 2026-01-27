<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EnvironmentalMetric>
 */
class EnvironmentalMetricFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $metrics = [
            ['name' => 'Air Quality Index', 'unit' => 'AQI'],
            ['name' => 'Temperature', 'unit' => '°C'],
            ['name' => 'Humidity', 'unit' => '%'],
            ['name' => 'Water pH', 'unit' => 'pH'],
            ['name' => 'Noise Level', 'unit' => 'dB'],
            ['name' => 'PM2.5', 'unit' => 'μg/m³'],
            ['name' => 'PM10', 'unit' => 'μg/m³'],
            ['name' => 'CO2', 'unit' => 'ppm'],
            ['name' => 'Ozone', 'unit' => 'ppb'],
        ];

        $metric = $this->faker->randomElement($metrics);

        return [
            'name' => $metric['name'],
            'unit' => $metric['unit'],
            'description' => $this->faker->sentence(),
            'expected_min' => null,
            'expected_max' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
