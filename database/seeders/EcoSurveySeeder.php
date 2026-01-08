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
            $humidityMetric = EnvironmentalMetric::where('name', 'Humidity')->first();
            $airQualityMetric = EnvironmentalMetric::where('name', 'Air Quality Index')->first();

            // Sample points across Fælledparken (Copenhagen's park)
            // Location: 55.7072° N, 12.5704° E (center of Fælledparken)
            $locations = [
                ['lat' => 55.7072, 'lon' => 12.5704, 'name' => 'Park center'],
                ['lat' => 55.7085, 'lon' => 12.5680, 'name' => 'North section'],
                ['lat' => 55.7060, 'lon' => 12.5720, 'name' => 'South section'],
                ['lat' => 55.7078, 'lon' => 12.5650, 'name' => 'West edge'],
                ['lat' => 55.7065, 'lon' => 12.5740, 'name' => 'East edge'],
            ];

            // Create data points over the last 30 days for better trend visualization
            $startDate = now()->subDays(30);
            $endDate = now();

            for ($day = 0; $day <= 30; $day++) {
                $date = $startDate->copy()->addDays($day);

                // Create 3-5 temperature readings per day at different locations
                // n >= 3 ensures meaningful confidence intervals
                $dailyReadings = rand(3, 5);
                for ($i = 0; $i < $dailyReadings; $i++) {
                    $location = $locations[array_rand($locations)];

                    // Temperature varies by season and time (15-25°C range)
                    $baseTemp = 18 + sin($day / 30 * pi()) * 5; // Seasonal variation
                    $temp = $baseTemp + rand(-20, 20) / 10; // Add some randomness

                    \App\Models\DataPoint::create([
                        'campaign_id' => $parkCampaign->id,
                        'environmental_metric_id' => $temperatureMetric->id,
                        'user_id' => $user->id,
                        'value' => round($temp, 1),
                        'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                        'accuracy' => rand(50, 150) / 10,
                        'collected_at' => $date->copy()->addHours(rand(8, 18)),
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }

                // Create 3-4 humidity readings per day (at least 3 for CI)
                $humidityReadings = rand(3, 4);
                for ($i = 0; $i < $humidityReadings; $i++) {
                    $location = $locations[array_rand($locations)];
                    $humidity = 60 + rand(-200, 200) / 10; // 40-80% range

                    \App\Models\DataPoint::create([
                        'campaign_id' => $parkCampaign->id,
                        'environmental_metric_id' => $humidityMetric->id,
                        'user_id' => $user->id,
                        'value' => round(max(30, min(90, $humidity)), 1),
                        'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                        'accuracy' => rand(50, 150) / 10,
                        'collected_at' => $date->copy()->addHours(rand(8, 18)),
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }

                // Create 3 air quality readings per day (minimum for CI)
                for ($i = 0; $i < 3; $i++) {
                    $location = $locations[array_rand($locations)];
                    $aqi = 40 + rand(-100, 100) / 10; // 30-50 AQI (good to moderate)

                    \App\Models\DataPoint::create([
                        'campaign_id' => $parkCampaign->id,
                        'environmental_metric_id' => $airQualityMetric->id,
                        'user_id' => $user->id,
                        'value' => round(max(20, min(80, $aqi)), 1),
                        'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                        'accuracy' => rand(50, 150) / 10,
                        'collected_at' => $date->copy()->addHours(rand(8, 18)),
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
            }

            $this->command->info('✓ Created sample data points in Fælledparken (30 days of data)');
        }

        // Add sample data points to Urban Noise Pollution Study campaign
        $noiseCampaign = Campaign::where('name', 'Urban Noise Pollution Study')->first();
        if ($noiseCampaign) {
            $noiseMetric = EnvironmentalMetric::where('name', 'Noise Level')->first();

            // High-traffic locations in Copenhagen
            $locations = [
                ['lat' => 55.6761, 'lon' => 12.5683, 'name' => 'Copenhagen Central Station'],
                ['lat' => 55.6867, 'lon' => 12.5700, 'name' => 'Nørreport Station'],
                ['lat' => 55.6828, 'lon' => 12.5878, 'name' => 'Østerport area'],
                ['lat' => 55.6759, 'lon' => 12.5655, 'name' => 'Tivoli Gardens area'],
                ['lat' => 55.6736, 'lon' => 12.5681, 'name' => 'Vesterbrogade'],
            ];

            // Create data points over the last 14 days (campaign started 14 days ago)
            $startDate = now()->subDays(14);

            for ($day = 0; $day <= 14; $day++) {
                $date = $startDate->copy()->addDays($day);

                // Create 3-4 noise readings per day at different locations and times
                $dailyReadings = rand(3, 4);
                for ($i = 0; $i < $dailyReadings; $i++) {
                    $location = $locations[array_rand($locations)];
                    $hour = rand(6, 22); // 6 AM to 10 PM

                    // Noise varies by time of day and location (40-85 dB range)
                    // Higher during rush hours (7-9 AM, 4-7 PM)
                    $isRushHour = ($hour >= 7 && $hour <= 9) || ($hour >= 16 && $hour <= 19);
                    $baseNoise = $isRushHour ? 70 : 55;
                    $noise = $baseNoise + rand(-100, 100) / 10;

                    \App\Models\DataPoint::create([
                        'campaign_id' => $noiseCampaign->id,
                        'environmental_metric_id' => $noiseMetric->id,
                        'user_id' => $user->id,
                        'value' => round(max(40, min(85, $noise)), 1),
                        'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                        'accuracy' => rand(50, 150) / 10,
                        'collected_at' => $date->copy()->addHours($hour)->addMinutes(rand(0, 59)),
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
            }

            $this->command->info('✓ Created sample data points for Urban Noise Pollution Study (14 days of data)');
        }

        // Add sample satellite analyses for Fælledparken campaign
        if ($parkCampaign) {
            $this->command->info('Creating satellite analyses for Fælledparken...');

            // Create satellite analyses for the park center location
            $parkCenter = ['lat' => 55.7072, 'lon' => 12.5704];

            // Create analyses for the last 30 days (every 5 days to match Sentinel-2 revisit time)
            for ($day = 0; $day <= 30; $day += 5) {
                $date = now()->subDays(30 - $day);

                // NDVI value varies by season (higher in summer)
                $seasonalFactor = 0.6 + (sin($day / 30 * pi()) * 0.2); // 0.4 to 0.8 range
                $ndviValue = round($seasonalFactor + (rand(-5, 5) / 100), 4);

                \App\Models\SatelliteAnalysis::create([
                    'campaign_id' => $parkCampaign->id,
                    'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$parkCenter['lon']}, {$parkCenter['lat']}), 4326)"),
                    'ndvi_value' => $ndviValue,
                    'ndvi_interpretation' => $this->interpretNDVI($ndviValue),
                    'moisture_index' => round(rand(30, 70) / 100, 4),
                    'temperature_kelvin' => round(273.15 + rand(15, 25), 2),
                    'acquisition_date' => $date,
                    'satellite_source' => 'Copernicus',
                    'processing_level' => 'L2A',
                    'cloud_coverage_percent' => round(rand(0, 30), 2),
                    'metadata' => [
                        'platform' => 'Sentinel-2A',
                        'instrument' => 'MSI',
                        'resolution' => '10m',
                    ],
                ]);
            }

            $this->command->info('✓ Created satellite analyses for Fælledparken (7 analyses)');
        }

        $this->command->info('✓ Created '.count($metrics).' environmental metrics');
        $this->command->info('✓ Created '.count($campaigns).' campaigns');
    }

    /**
     * Interpret NDVI value
     */
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
