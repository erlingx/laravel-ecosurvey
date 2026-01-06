<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\EnvironmentalMetric;
use App\Models\User;
use Illuminate\Database\Seeder;

class EcoSurveySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Environmental Metrics
        $metrics = [
            ['name' => 'Air Quality Index', 'unit' => 'AQI', 'description' => 'Air quality measurement (0-500)', 'is_active' => true],
            ['name' => 'Temperature', 'unit' => '°C', 'description' => 'Ambient temperature in Celsius', 'is_active' => true],
            ['name' => 'Humidity', 'unit' => '%', 'description' => 'Relative humidity percentage', 'is_active' => true],
            ['name' => 'Noise Level', 'unit' => 'dB', 'description' => 'Sound level in decibels', 'is_active' => true],
            ['name' => 'PM2.5', 'unit' => 'µg/m³', 'description' => 'Fine particulate matter concentration', 'is_active' => true],
            ['name' => 'PM10', 'unit' => 'µg/m³', 'description' => 'Coarse particulate matter concentration', 'is_active' => true],
            ['name' => 'CO2', 'unit' => 'ppm', 'description' => 'Carbon dioxide concentration', 'is_active' => true],
            ['name' => 'Water pH', 'unit' => 'pH', 'description' => 'Water acidity/alkalinity level', 'is_active' => true],
        ];

        foreach ($metrics as $metric) {
            EnvironmentalMetric::firstOrCreate(
                ['name' => $metric['name']],
                $metric
            );
        }

        // Get the first user (created by UserSeeder)
        $user = User::first();

        // Create Sample Campaigns
        $campaigns = [
            [
                'name' => 'Copenhagen Air Quality 2026',
                'description' => 'Monitoring air quality across Copenhagen neighborhoods',
                'status' => 'active',
                'user_id' => $user->id,
                'start_date' => now()->subDays(30),
                'end_date' => now()->addDays(60),
            ],
            [
                'name' => 'Urban Noise Pollution Study',
                'description' => 'Measuring noise levels in high-traffic areas',
                'status' => 'active',
                'user_id' => $user->id,
                'start_date' => now()->subDays(14),
                'end_date' => now()->addDays(90),
            ],
            [
                'name' => 'Water Quality Assessment',
                'description' => 'Testing water quality in local lakes and streams',
                'status' => 'active',
                'user_id' => $user->id,
                'start_date' => now()->subDays(7),
                'end_date' => now()->addDays(120),
            ],
            [
                'name' => 'Fælledparken Green Space Study',
                'description' => 'Vegetation and air quality monitoring in Copenhagen\'s largest park',
                'status' => 'active',
                'user_id' => $user->id,
                'start_date' => now()->subDays(15),
                'end_date' => now()->addDays(75),
            ],
        ];

        foreach ($campaigns as $campaign) {
            Campaign::firstOrCreate(
                ['name' => $campaign['name']],
                $campaign
            );
        }

        // Add sample data points to Fælledparken campaign
        $parkCampaign = Campaign::where('name', 'Fælledparken Green Space Study')->first();
        if ($parkCampaign) {
            $temperatureMetric = EnvironmentalMetric::where('name', 'Temperature')->first();

            // Sample points across Fælledparken (Copenhagen's park with actual vegetation)
            // Location: 55.7072° N, 12.5704° E (center of Fælledparken)
            $dataPoints = [
                ['lat' => 55.7072, 'lon' => 12.5704, 'value' => 22.5], // Park center
                ['lat' => 55.7085, 'lon' => 12.5680, 'value' => 21.8], // North section
                ['lat' => 55.7060, 'lon' => 12.5720, 'value' => 23.1], // South section
                ['lat' => 55.7078, 'lon' => 12.5650, 'value' => 22.0], // West edge
            ];

            foreach ($dataPoints as $point) {
                \App\Models\DataPoint::firstOrCreate(
                    [
                        'campaign_id' => $parkCampaign->id,
                        'environmental_metric_id' => $temperatureMetric->id,
                    ],
                    [
                        'user_id' => $user->id,
                        'value' => $point['value'],
                        'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$point['lon']}, {$point['lat']}), 4326)"),
                        'accuracy' => 10.0,
                        'collected_at' => now()->subDays(rand(1, 10)),
                    ]
                );
            }

            $this->command->info('✓ Created sample data points in Fælledparken');
        }

        $this->command->info('✓ Created '.count($metrics).' environmental metrics');
        $this->command->info('✓ Created '.count($campaigns).' campaigns');
    }
}
