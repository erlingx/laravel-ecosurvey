<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\EnvironmentalMetric;
use App\Models\User;
use Illuminate\Database\Seeder;

class EcoSurveySeeder extends Seeder
{
    /**
     * Nature photo paths for demo data
     * These are stored in public/files/seed-photos/ and accessible via /files/seed-photos/
     * Manually uploaded photos go to public/files/data-points/
     */
    private array $naturePhotos = [
        'files/seed-photos/forest-path.jpg',
        'files/seed-photos/mountain-lake.jpg',
        'files/seed-photos/tree-canopy.jpg',
        'files/seed-photos/misty-mountains.jpg',
        'files/seed-photos/green-meadow.jpg',
    ];

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
                    $temp = $baseTemp + rand(-40, 40) / 10; // Wider randomness to create more outliers

                    // Vary accuracy to demonstrate yellow markers (some > 50m)
                    $accuracy = rand(30, 800) / 10; // 3m to 80m

                    // Vary status to demonstrate different marker colors
                    // 50% approved, 25% pending, 15% draft, 10% rejected
                    $statusRoll = rand(1, 100);
                    if ($statusRoll <= 50) {
                        $status = 'approved';
                    } elseif ($statusRoll <= 75) {
                        $status = 'pending';
                    } elseif ($statusRoll <= 90) {
                        $status = 'draft';
                    } else {
                        $status = 'rejected';
                    }

                    // Flag outliers: temperature >2.5°C away from base (unusual deviation)
                    $qaFlags = [];
                    $deviation = abs($temp - $baseTemp);
                    if ($deviation > 2.5) {
                        $qaFlags[] = 'outlier';
                    }
                    // Flag suspicious values: temperature outside realistic range (-10 to 40°C)
                    if ($temp < -10 || $temp > 40) {
                        if (! in_array('suspicious_value', $qaFlags)) {
                            $qaFlags[] = 'suspicious_value';
                        }
                    }
                    // Add location_uncertainty for poor GPS accuracy
                    if ($accuracy > 80) {
                        $qaFlags[] = 'location_uncertainty';
                    }

                    // Assign random nature photo to all data points
                    $photoPath = $this->naturePhotos[array_rand($this->naturePhotos)];

                    \App\Models\DataPoint::create([
                        'campaign_id' => $parkCampaign->id,
                        'environmental_metric_id' => $temperatureMetric->id,
                        'user_id' => $user->id,
                        'value' => round($temp, 1),
                        'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                        'accuracy' => round($accuracy, 1),
                        'collected_at' => $date->copy()->addHours(rand(8, 18)),
                        'status' => $status,
                        'qa_flags' => $qaFlags,
                        'photo_path' => $photoPath,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }

                // Create 3-4 humidity readings per day (at least 3 for CI)
                $humidityReadings = rand(3, 4);
                for ($i = 0; $i < $humidityReadings; $i++) {
                    $location = $locations[array_rand($locations)];
                    $humidity = 60 + rand(-200, 200) / 10; // 40-80% range

                    // Vary accuracy to demonstrate yellow markers
                    $accuracy = rand(30, 800) / 10; // 3m to 80m

                    // Vary status
                    $statusRoll = rand(1, 100);
                    if ($statusRoll <= 50) {
                        $status = 'approved';
                    } elseif ($statusRoll <= 75) {
                        $status = 'pending';
                    } elseif ($statusRoll <= 90) {
                        $status = 'draft';
                    } else {
                        $status = 'rejected';
                    }

                    // Flag calibration overdue: randomly set a calibration date, flag if >90 days old
                    // Range: 20-110 days ago (so only ~18% will be >90 days = overdue)
                    $qaFlags = [];
                    $calibrationDate = $date->copy()->subDays(rand(20, 110));
                    $daysSinceCalibration = $calibrationDate->diffInDays($date);
                    if ($daysSinceCalibration > 90) {
                        $qaFlags[] = 'calibration_overdue';
                    }
                    // Add location_uncertainty for poor GPS accuracy
                    if ($accuracy > 80) {
                        $qaFlags[] = 'location_uncertainty';
                    }
                    // Flag suspicious humidity values outside realistic range (0-100%)
                    $finalHumidity = max(30, min(90, $humidity));
                    if ($finalHumidity < 10 || $finalHumidity > 95) {
                        $qaFlags[] = 'suspicious_value';
                    }

                    // Assign random nature photo to all data points
                    $photoPath = $this->naturePhotos[array_rand($this->naturePhotos)];

                    \App\Models\DataPoint::create([
                        'campaign_id' => $parkCampaign->id,
                        'environmental_metric_id' => $humidityMetric->id,
                        'user_id' => $user->id,
                        'value' => round(max(30, min(90, $humidity)), 1),
                        'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                        'accuracy' => round($accuracy, 1),
                        'collected_at' => $date->copy()->addHours(rand(8, 18)),
                        'calibration_at' => $calibrationDate,
                        'status' => $status,
                        'qa_flags' => $qaFlags,
                        'photo_path' => $photoPath,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }

                // Create 3 air quality readings per day (minimum for CI)
                for ($i = 0; $i < 3; $i++) {
                    $location = $locations[array_rand($locations)];
                    $aqi = 40 + rand(-100, 100) / 10; // 30-50 AQI (good to moderate)

                    // Vary accuracy
                    $accuracy = rand(30, 800) / 10;

                    // Vary status
                    $statusRoll = rand(1, 100);
                    if ($statusRoll <= 50) {
                        $status = 'approved';
                    } elseif ($statusRoll <= 75) {
                        $status = 'pending';
                    } elseif ($statusRoll <= 90) {
                        $status = 'draft';
                    } else {
                        $status = 'rejected';
                    }

                    // Flag suspicious AQI values outside expected range (0-150 for good to unhealthy)
                    $qaFlags = [];
                    $finalAqi = max(20, min(80, $aqi));
                    if ($finalAqi < 0 || $finalAqi > 150) {
                        $qaFlags[] = 'suspicious_value';
                    }
                    // Add location_uncertainty for poor GPS accuracy
                    if ($accuracy > 80) {
                        $qaFlags[] = 'location_uncertainty';
                    }

                    // Note: duplicate_reading would require checking existing records
                    // In production, this would be done via database query or observer

                    // Assign random nature photo to all data points
                    $photoPath = $this->naturePhotos[array_rand($this->naturePhotos)];

                    \App\Models\DataPoint::create([
                        'campaign_id' => $parkCampaign->id,
                        'environmental_metric_id' => $airQualityMetric->id,
                        'user_id' => $user->id,
                        'value' => round(max(20, min(80, $aqi)), 1),
                        'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                        'accuracy' => round($accuracy, 1),
                        'collected_at' => $date->copy()->addHours(rand(8, 18)),
                        'status' => $status,
                        'qa_flags' => $qaFlags,
                        'photo_path' => $photoPath,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
            }

            $this->command->info('✓ Created sample data points in Fælledparken (30 days of data)');

            // Create specific examples of each QA flag type for demonstration
            $demonstrationDate = now()->subDays(5);

            // Example 1: Pure outlier (temperature spike)
            \App\Models\DataPoint::create([
                'campaign_id' => $parkCampaign->id,
                'environmental_metric_id' => $temperatureMetric->id,
                'user_id' => $user->id,
                'value' => 35.0, // Unusually high for the season
                'location' => \DB::raw('ST_SetSRID(ST_MakePoint(12.5704, 55.7072), 4326)'),
                'accuracy' => 15.0,
                'collected_at' => $demonstrationDate->copy()->addHours(12),
                'status' => 'pending',
                'qa_flags' => ['outlier'],
                'photo_path' => $this->naturePhotos[array_rand($this->naturePhotos)],
                'notes' => 'Demonstration: Outlier flag only',
                'created_at' => $demonstrationDate,
                'updated_at' => $demonstrationDate,
            ]);

            // Example 2: Pure location_uncertainty (poor GPS)
            \App\Models\DataPoint::create([
                'campaign_id' => $parkCampaign->id,
                'environmental_metric_id' => $temperatureMetric->id,
                'user_id' => $user->id,
                'value' => 18.5,
                'location' => \DB::raw('ST_SetSRID(ST_MakePoint(12.5720, 55.7060), 4326)'),
                'accuracy' => 95.0, // Very poor accuracy
                'collected_at' => $demonstrationDate->copy()->addHours(14),
                'status' => 'pending',
                'qa_flags' => ['location_uncertainty'],
                'photo_path' => $this->naturePhotos[array_rand($this->naturePhotos)],
                'notes' => 'Demonstration: Location uncertainty only',
                'created_at' => $demonstrationDate,
                'updated_at' => $demonstrationDate,
            ]);

            // Example 3: Pure calibration_overdue
            \App\Models\DataPoint::create([
                'campaign_id' => $parkCampaign->id,
                'environmental_metric_id' => $humidityMetric->id,
                'user_id' => $user->id,
                'value' => 65.0,
                'location' => \DB::raw('ST_SetSRID(ST_MakePoint(12.5680, 55.7085), 4326)'),
                'accuracy' => 10.0,
                'collected_at' => $demonstrationDate->copy()->addHours(15),
                'calibration_at' => $demonstrationDate->copy()->subDays(120), // 120 days old
                'status' => 'pending',
                'qa_flags' => ['calibration_overdue'],
                'photo_path' => $this->naturePhotos[array_rand($this->naturePhotos)],
                'notes' => 'Demonstration: Calibration overdue only',
                'created_at' => $demonstrationDate,
                'updated_at' => $demonstrationDate,
            ]);

            // Example 4: Multiple flags (outlier + location_uncertainty)
            \App\Models\DataPoint::create([
                'campaign_id' => $parkCampaign->id,
                'environmental_metric_id' => $temperatureMetric->id,
                'user_id' => $user->id,
                'value' => 32.0, // Outlier
                'location' => \DB::raw('ST_SetSRID(ST_MakePoint(12.5650, 55.7078), 4326)'),
                'accuracy' => 110.0, // Poor accuracy
                'collected_at' => $demonstrationDate->copy()->addHours(16),
                'status' => 'pending',
                'qa_flags' => ['outlier', 'location_uncertainty'],
                'photo_path' => $this->naturePhotos[array_rand($this->naturePhotos)],
                'notes' => 'Demonstration: Multiple flags',
                'created_at' => $demonstrationDate,
                'updated_at' => $demonstrationDate,
            ]);

            // Example 5: Manual review flag
            \App\Models\DataPoint::create([
                'campaign_id' => $parkCampaign->id,
                'environmental_metric_id' => $airQualityMetric->id,
                'user_id' => $user->id,
                'value' => 55.0,
                'location' => \DB::raw('ST_SetSRID(ST_MakePoint(12.5740, 55.7065), 4326)'),
                'accuracy' => 8.0,
                'collected_at' => $demonstrationDate->copy()->addHours(17),
                'status' => 'pending',
                'qa_flags' => ['manual_review'],
                'photo_path' => $this->naturePhotos[array_rand($this->naturePhotos)],
                'notes' => 'Demonstration: Manually flagged for review',
                'created_at' => $demonstrationDate,
                'updated_at' => $demonstrationDate,
            ]);

            $this->command->info('✓ Created demonstration data points for each QA flag type');
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

                    // Vary accuracy
                    $accuracy = rand(30, 800) / 10;

                    // Vary status
                    $statusRoll = rand(1, 100);
                    if ($statusRoll <= 50) {
                        $status = 'approved';
                    } elseif ($statusRoll <= 75) {
                        $status = 'pending';
                    } elseif ($statusRoll <= 90) {
                        $status = 'draft';
                    } else {
                        $status = 'rejected';
                    }

                    // Only flag location_uncertainty if accuracy is genuinely poor (>80m)
                    $qaFlags = [];
                    if ($accuracy > 80) {
                        $qaFlags[] = 'location_uncertainty';
                    }
                    // Flag suspicious noise values outside realistic urban range (30-100 dB)
                    $finalNoise = max(40, min(85, $noise));
                    if ($finalNoise < 30 || $finalNoise > 100) {
                        $qaFlags[] = 'suspicious_value';
                    }
                    // Occasionally add manual_review flag (5% chance) for demonstration
                    if (rand(1, 100) <= 5 && empty($qaFlags)) {
                        $qaFlags[] = 'manual_review';
                    }

                    // Assign random nature photo to all data points
                    $photoPath = $this->naturePhotos[array_rand($this->naturePhotos)];

                    \App\Models\DataPoint::create([
                        'campaign_id' => $noiseCampaign->id,
                        'environmental_metric_id' => $noiseMetric->id,
                        'user_id' => $user->id,
                        'value' => round(max(40, min(85, $noise)), 1),
                        'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                        'accuracy' => round($accuracy, 1),
                        'collected_at' => $date->copy()->addHours($hour)->addMinutes(rand(0, 59)),
                        'status' => $status,
                        'qa_flags' => $qaFlags,
                        'photo_path' => $photoPath,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
            }

            $this->command->info('✓ Created sample data points for Urban Noise Pollution Study (14 days of data)');

            // Add Valby Parken cluster - all approved with good accuracy (green cluster)
            $valbyParkenLocations = [
                ['lat' => 55.6596, 'lon' => 12.5107, 'name' => 'Valby Park center'],
                ['lat' => 55.6601, 'lon' => 12.5095, 'name' => 'Valby Park north'],
                ['lat' => 55.6590, 'lon' => 12.5120, 'name' => 'Valby Park south'],
                ['lat' => 55.6598, 'lon' => 12.5088, 'name' => 'Valby Park west'],
                ['lat' => 55.6594, 'lon' => 12.5125, 'name' => 'Valby Park east'],
            ];

            // Create approved, high-quality noise readings in Valby Parken (last 7 days)
            for ($day = 0; $day <= 7; $day++) {
                $date = $startDate->copy()->addDays($day);

                // Create 2-3 readings per day
                $dailyReadings = rand(2, 3);
                for ($i = 0; $i < $dailyReadings; $i++) {
                    $location = $valbyParkenLocations[array_rand($valbyParkenLocations)];
                    $hour = rand(8, 20); // 8 AM to 8 PM

                    // Park noise levels are moderate (45-65 dB)
                    $baseNoise = 55;
                    $noise = $baseNoise + rand(-80, 80) / 10;

                    // High accuracy (< 50m for green markers)
                    $accuracy = rand(30, 450) / 10; // 3m to 45m

                    // Assign random nature photo
                    $photoPath = $this->naturePhotos[array_rand($this->naturePhotos)];

                    \App\Models\DataPoint::create([
                        'campaign_id' => $noiseCampaign->id,
                        'environmental_metric_id' => $noiseMetric->id,
                        'user_id' => $user->id,
                        'value' => round(max(45, min(65, $noise)), 1),
                        'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                        'accuracy' => round($accuracy, 1),
                        'collected_at' => $date->copy()->addHours($hour)->addMinutes(rand(0, 59)),
                        'status' => 'approved', // All approved
                        'qa_flags' => [], // No QA flags
                        'photo_path' => $photoPath,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
            }

            $this->command->info('✓ Created green cluster in Valby Parken (approved high-quality data)');

            // Add Fælledparken cluster - low accuracy (yellow cluster)
            $fælledparkenLocations = [
                ['lat' => 55.7072, 'lon' => 12.5704, 'name' => 'Fælledparken center'],
                ['lat' => 55.7080, 'lon' => 12.5690, 'name' => 'Fælledparken north'],
                ['lat' => 55.7065, 'lon' => 12.5718, 'name' => 'Fælledparken south'],
                ['lat' => 55.7075, 'lon' => 12.5680, 'name' => 'Fælledparken west'],
                ['lat' => 55.7068, 'lon' => 12.5728, 'name' => 'Fælledparken east'],
            ];

            // Create low accuracy noise readings in Fælledparken (last 7 days)
            for ($day = 0; $day <= 7; $day++) {
                $date = $startDate->copy()->addDays($day);

                // Create 2-3 readings per day
                $dailyReadings = rand(2, 3);
                for ($i = 0; $i < $dailyReadings; $i++) {
                    $location = $fælledparkenLocations[array_rand($fælledparkenLocations)];
                    $hour = rand(8, 20); // 8 AM to 8 PM

                    // Park noise levels are moderate (48-68 dB)
                    $baseNoise = 58;
                    $noise = $baseNoise + rand(-90, 90) / 10;

                    // Low accuracy (> 50m for yellow markers)
                    $accuracy = rand(510, 1200) / 10; // 51m to 120m

                    // Assign random nature photo
                    $photoPath = $this->naturePhotos[array_rand($this->naturePhotos)];

                    \App\Models\DataPoint::create([
                        'campaign_id' => $noiseCampaign->id,
                        'environmental_metric_id' => $noiseMetric->id,
                        'user_id' => $user->id,
                        'value' => round(max(48, min(68, $noise)), 1),
                        'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                        'accuracy' => round($accuracy, 1),
                        'collected_at' => $date->copy()->addHours($hour)->addMinutes(rand(0, 59)),
                        'status' => 'pending', // Pending status
                        'qa_flags' => [], // No QA flags (would make it red)
                        'photo_path' => $photoPath,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
            }

            $this->command->info('✓ Created yellow cluster in Fælledparken (low accuracy data)');
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
