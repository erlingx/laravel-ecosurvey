<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\EnvironmentalMetric;
use App\Models\User;
use Illuminate\Console\Command;

class PopulateTestData extends Command
{
    protected $signature = 'ecosurvey:populate';

    protected $description = 'Populate campaigns and environmental metrics for testing';

    public function handle()
    {
        $this->info('Populating test data...');

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
            EnvironmentalMetric::updateOrCreate(
                ['name' => $metric['name']],
                $metric
            );
        }

        $this->info('✓ Created '.count($metrics).' environmental metrics');

        // Get or create a user for the campaigns
        $user = User::first();
        if (! $user) {
            $user = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
            $this->info('✓ Created test user (test@example.com / password)');
        }

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
        ];

        foreach ($campaigns as $campaign) {
            Campaign::updateOrCreate(
                ['name' => $campaign['name']],
                $campaign
            );
        }

        $this->info('✓ Created '.count($campaigns).' campaigns');
        $this->info('');
        $this->info('Total Campaigns: '.Campaign::count());
        $this->info('Total Metrics: '.EnvironmentalMetric::count());

        return 0;
    }
}
