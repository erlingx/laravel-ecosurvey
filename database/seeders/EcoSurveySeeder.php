<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\EnvironmentalMetric;
use App\Models\SurveyZone;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class EcoSurveySeeder extends Seeder
{
    /**
     * Nature photo paths for demo data
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
     *
     * Creates 3 campaigns with data centered around August 15, 2025 (known good Sentinel-2 coverage):
     * 1. Fælledparken Green Space Study - Vegetation monitoring
     * 2. Noise Pollution Study - Urban noise measurements
     * 3. Copenhagen Air Quality 2025 - Air quality across city
     */
    public function run(): void
    {
        // Create Environmental Metrics
        $metrics = [
            ['name' => 'Air Quality Index', 'unit' => 'AQI', 'description' => 'Air quality measurement (0-500)', 'expected_min' => 0, 'expected_max' => 500, 'is_active' => true],
            ['name' => 'Temperature', 'unit' => '°C', 'description' => 'Ambient temperature in Celsius', 'expected_min' => -40, 'expected_max' => 50, 'is_active' => true],
            ['name' => 'Humidity', 'unit' => '%', 'description' => 'Relative humidity percentage', 'expected_min' => 0, 'expected_max' => 100, 'is_active' => true],
            ['name' => 'Noise Level', 'unit' => 'dB', 'description' => 'Sound level in decibels', 'expected_min' => 30, 'expected_max' => 120, 'is_active' => true],
            ['name' => 'PM2.5', 'unit' => 'µg/m³', 'description' => 'Fine particulate matter concentration', 'expected_min' => 0, 'expected_max' => 500, 'is_active' => true],
            ['name' => 'PM10', 'unit' => 'µg/m³', 'description' => 'Coarse particulate matter concentration', 'expected_min' => 0, 'expected_max' => 600, 'is_active' => true],
            ['name' => 'CO2', 'unit' => 'ppm', 'description' => 'Carbon dioxide concentration', 'expected_min' => 300, 'expected_max' => 5000, 'is_active' => true],
        ];

        foreach ($metrics as $metric) {
            EnvironmentalMetric::updateOrCreate(['name' => $metric['name']], $metric);
        }

        $adminUser = User::where('email', 'admin@admin.com')->first();
        $firstUser = User::first();

        // CAMPAIGN 1: Fælledparken Green Space Study (owned by admin)
        $this->createFalledparkenCampaign($adminUser);

        // CAMPAIGN 2: Noise Pollution Study (owned by admin)
        $this->createNoisePollutionCampaign($adminUser);

        // CAMPAIGN 3: Copenhagen Air Quality 2025 (owned by first user)
        $this->createCopenhagenAirQualityCampaign($firstUser);

        $this->command->info('✅ Created 3 campaigns with survey zones and data points around August 15, 2025');
    }

    /**
     * Campaign 1: Fælledparken Green Space Study
     * Location: Copenhagen's largest park (55.7072°N, 12.5704°E)
     * Focus: Vegetation health, temperature, humidity
     */
    private function createFalledparkenCampaign(User $user): void
    {
        $campaign = Campaign::firstOrCreate(
            ['name' => 'Fælledparken Green Space Study'],
            [
                'description' => 'Vegetation and air quality monitoring in Copenhagen\'s largest park',
                'status' => 'active',
                'user_id' => $user->id,
                'start_date' => '2025-08-01',
                'end_date' => '2025-08-31',
            ]
        );

        // Create survey zone for Fælledparken
        SurveyZone::firstOrCreate(
            [
                'campaign_id' => $campaign->id,
                'name' => 'Fælledparken Core Area'
            ],
            [
                'description' => 'Central park area with dense vegetation',
                'area' => \DB::raw("ST_GeomFromText('POLYGON((12.5650 55.7085, 12.5750 55.7085, 12.5750 55.7055, 12.5650 55.7055, 12.5650 55.7085))', 4326)"),
                'area_km2' => 0.84,
            ]
        );

        $tempMetric = EnvironmentalMetric::where('name', 'Temperature')->first();
        $humidityMetric = EnvironmentalMetric::where('name', 'Humidity')->first();
        $aqiMetric = EnvironmentalMetric::where('name', 'Air Quality Index')->first();

        $locations = [
            ['lat' => 55.7072, 'lon' => 12.5704, 'name' => 'Park center'],
            ['lat' => 55.7080, 'lon' => 12.5690, 'name' => 'North section'],
            ['lat' => 55.7065, 'lon' => 12.5718, 'name' => 'South section'],
            ['lat' => 55.7075, 'lon' => 12.5680, 'name' => 'West edge'],
            ['lat' => 55.7068, 'lon' => 12.5728, 'name' => 'East edge'],
        ];

        // Create data points from Aug 1-30, 2025 (centered on Aug 15 with known satellite coverage)
        for ($day = 1; $day <= 30; $day++) {
            $date = Carbon::parse("2025-08-{$day}");

            // 3-5 temperature readings per day with varied statuses
            for ($i = 0; $i < rand(3, 5); $i++) {
                $location = $locations[array_rand($locations)];
                $temp = 20 + sin($day / 30 * pi()) * 3 + rand(-20, 20) / 10;

                // Vary status: 60% approved, 20% pending, 10% draft, 10% rejected
                $statusRoll = rand(1, 100);
                if ($statusRoll <= 60) {
                    $status = 'approved';
                } elseif ($statusRoll <= 80) {
                    $status = 'pending';
                } elseif ($statusRoll <= 90) {
                    $status = 'draft';
                } else {
                    $status = 'rejected';
                }

                // Add QA flags for testing
                $qaFlags = [];
                if (abs($temp - 20) > 5) {
                    $qaFlags[] = 'outlier';
                }

                $accuracy = rand(30, 150) / 10; // 3m to 15m
                if ($accuracy > 10) {
                    $qaFlags[] = 'location_uncertainty';
                }

                \App\Models\DataPoint::create([
                    'campaign_id' => $campaign->id,
                    'environmental_metric_id' => $tempMetric->id,
                    'user_id' => $user->id,
                    'value' => round($temp, 1),
                    'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                    'accuracy' => round($accuracy, 1),
                    'collected_at' => $date->copy()->addHours(rand(8, 18)),
                    'status' => $status,
                    'qa_flags' => $qaFlags,
                    'photo_path' => $this->naturePhotos[array_rand($this->naturePhotos)],
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }

            // 3-4 humidity readings per day with varied statuses
            for ($i = 0; $i < rand(3, 4); $i++) {
                $location = $locations[array_rand($locations)];
                $humidity = 60 + rand(-15, 15);

                // Vary status
                $statusRoll = rand(1, 100);
                if ($statusRoll <= 60) {
                    $status = 'approved';
                } elseif ($statusRoll <= 80) {
                    $status = 'pending';
                } elseif ($statusRoll <= 90) {
                    $status = 'draft';
                } else {
                    $status = 'rejected';
                }

                // Add calibration_overdue flag occasionally
                $qaFlags = [];
                $calibrationDate = null;
                if (rand(1, 100) <= 30) {
                    $calibrationDate = $date->copy()->subDays(rand(91, 150));
                    $qaFlags[] = 'calibration_overdue';
                }

                \App\Models\DataPoint::create([
                    'campaign_id' => $campaign->id,
                    'environmental_metric_id' => $humidityMetric->id,
                    'user_id' => $user->id,
                    'value' => round($humidity, 1),
                    'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                    'accuracy' => rand(30, 80) / 10,
                    'collected_at' => $date->copy()->addHours(rand(8, 18)),
                    'calibration_at' => $calibrationDate,
                    'status' => $status,
                    'qa_flags' => $qaFlags,
                    'photo_path' => $this->naturePhotos[array_rand($this->naturePhotos)],
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }

        // Add specific test cases for each status on August 15, 2025
        $testDate = Carbon::parse('2025-08-15');

        // Test: Draft status
        \App\Models\DataPoint::create([
            'campaign_id' => $campaign->id,
            'environmental_metric_id' => $tempMetric->id,
            'user_id' => $user->id,
            'value' => 21.5,
            'location' => \DB::raw("ST_SetSRID(ST_MakePoint(12.5704, 55.7072), 4326)"),
            'accuracy' => 5.0,
            'collected_at' => $testDate->copy()->setTime(10, 0),
            'status' => 'draft',
            'notes' => 'TEST: Draft status example',
            'photo_path' => $this->naturePhotos[0],
        ]);

        // Test: Pending status with outlier flag
        \App\Models\DataPoint::create([
            'campaign_id' => $campaign->id,
            'environmental_metric_id' => $tempMetric->id,
            'user_id' => $user->id,
            'value' => 32.0,
            'location' => \DB::raw("ST_SetSRID(ST_MakePoint(12.5690, 55.7080), 4326)"),
            'accuracy' => 7.0,
            'collected_at' => $testDate->copy()->setTime(14, 0),
            'status' => 'pending',
            'qa_flags' => ['outlier'],
            'notes' => 'TEST: Pending with outlier flag',
            'photo_path' => $this->naturePhotos[1],
        ]);

        // Test: Rejected status with multiple flags
        \App\Models\DataPoint::create([
            'campaign_id' => $campaign->id,
            'environmental_metric_id' => $humidityMetric->id,
            'user_id' => $user->id,
            'value' => 95.0,
            'location' => \DB::raw("ST_SetSRID(ST_MakePoint(12.5718, 55.7065), 4326)"),
            'accuracy' => 25.0,
            'collected_at' => $testDate->copy()->setTime(16, 0),
            'status' => 'rejected',
            'reviewed_by' => $user->id,
            'reviewed_at' => $testDate->copy()->setTime(17, 0),
            'review_notes' => 'Value outside realistic range for this season',
            'qa_flags' => ['suspicious_value', 'location_uncertainty'],
            'notes' => 'TEST: Rejected with multiple QA flags',
            'photo_path' => $this->naturePhotos[2],
        ]);

        // Test: Approved with manual_review flag
        \App\Models\DataPoint::create([
            'campaign_id' => $campaign->id,
            'environmental_metric_id' => $aqiMetric->id,
            'user_id' => $user->id,
            'value' => 45.0,
            'location' => \DB::raw("ST_SetSRID(ST_MakePoint(12.5680, 55.7075), 4326)"),
            'accuracy' => 6.0,
            'collected_at' => $testDate->copy()->setTime(12, 0),
            'status' => 'approved',
            'reviewed_by' => $user->id,
            'reviewed_at' => $testDate->copy()->setTime(13, 0),
            'review_notes' => 'Manually reviewed and approved',
            'qa_flags' => ['manual_review'],
            'notes' => 'TEST: Approved after manual review',
            'photo_path' => $this->naturePhotos[3],
        ]);

        // Add MORE YELLOW DOTS (low accuracy >50m on Aug 15)
        for ($i = 0; $i < 15; $i++) {
            $location = $locations[array_rand($locations)];
            \App\Models\DataPoint::create([
                'campaign_id' => $campaign->id,
                'environmental_metric_id' => $tempMetric->id,
                'user_id' => $user->id,
                'value' => rand(180, 240) / 10,
                'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                'accuracy' => rand(510, 1500) / 10, // 51m to 150m = YELLOW
                'collected_at' => $testDate->copy()->addHours(rand(8, 18)),
                'status' => 'approved',
                'notes' => 'TEST: Low accuracy (yellow marker)',
                'photo_path' => $this->naturePhotos[array_rand($this->naturePhotos)],
            ]);
        }

        // Add MORE RED DOTS (rejected status on Aug 15)
        for ($i = 0; $i < 12; $i++) {
            $location = $locations[array_rand($locations)];
            \App\Models\DataPoint::create([
                'campaign_id' => $campaign->id,
                'environmental_metric_id' => $humidityMetric->id,
                'user_id' => $user->id,
                'value' => rand(400, 850) / 10,
                'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                'accuracy' => rand(30, 80) / 10,
                'collected_at' => $testDate->copy()->addHours(rand(8, 18)),
                'status' => 'rejected',
                'reviewed_by' => $user->id,
                'reviewed_at' => $testDate->copy()->addHours(rand(18, 20)),
                'review_notes' => 'Data quality issues - rejected',
                'qa_flags' => rand(0, 1) ? ['outlier'] : ['suspicious_value'],
                'notes' => 'TEST: Rejected status (red marker)',
                'photo_path' => $this->naturePhotos[array_rand($this->naturePhotos)],
            ]);
        }

        $this->command->info("✓ Created Fælledparken campaign with survey zone and data points (Aug 1-30, 2025)");
    }

    /**
     * Campaign 2: Noise Pollution Study
     * Location: Central Copenhagen high-traffic areas
     * Focus: Urban noise levels
     */
    private function createNoisePollutionCampaign(User $user): void
    {
        $campaign = Campaign::firstOrCreate(
            ['name' => 'Noise Pollution Study'],
            [
                'description' => 'Measuring urban noise levels across Copenhagen high-traffic areas',
                'status' => 'active',
                'user_id' => $user->id,
                'start_date' => '2025-08-01',
                'end_date' => '2025-08-31',
            ]
        );

        // Create survey zone for Central Copenhagen
        SurveyZone::firstOrCreate(
            [
                'campaign_id' => $campaign->id,
                'name' => 'Central Copenhagen Zone'
            ],
            [
                'description' => 'High-traffic urban area including Central Station and Nørreport',
                'area' => \DB::raw("ST_GeomFromText('POLYGON((12.5650 55.6870, 12.5730 55.6870, 12.5730 55.6750, 12.5650 55.6750, 12.5650 55.6870))', 4326)"),
                'area_km2' => 1.12,
            ]
        );

        $noiseMetric = EnvironmentalMetric::where('name', 'Noise Level')->first();

        $locations = [
            ['lat' => 55.6761, 'lon' => 12.5683, 'name' => 'Copenhagen Central Station'],
            ['lat' => 55.6867, 'lon' => 12.5700, 'name' => 'Nørreport Station'],
            ['lat' => 55.6828, 'lon' => 12.5878, 'name' => 'Østerport area'],
            ['lat' => 55.6759, 'lon' => 12.5655, 'name' => 'Tivoli Gardens area'],
            ['lat' => 55.6736, 'lon' => 12.5681, 'name' => 'Vesterbrogade'],
        ];

        // Create data points from Aug 1-30, 2025
        for ($day = 1; $day <= 30; $day++) {
            $date = Carbon::parse("2025-08-{$day}");

            // 3-4 noise readings per day at different times with varied statuses
            for ($i = 0; $i < rand(3, 4); $i++) {
                $location = $locations[array_rand($locations)];
                $hour = rand(6, 22);
                $isRushHour = ($hour >= 7 && $hour <= 9) || ($hour >= 16 && $hour <= 19);
                $noise = $isRushHour ? 70 + rand(-5, 5) : 55 + rand(-5, 5);

                // Vary status: 60% approved, 20% pending, 10% draft, 10% rejected
                $statusRoll = rand(1, 100);
                if ($statusRoll <= 60) {
                    $status = 'approved';
                } elseif ($statusRoll <= 80) {
                    $status = 'pending';
                } elseif ($statusRoll <= 90) {
                    $status = 'draft';
                } else {
                    $status = 'rejected';
                }

                // Add QA flags
                $qaFlags = [];
                if ($noise > 95 || $noise < 35) {
                    $qaFlags[] = 'suspicious_value';
                }

                \App\Models\DataPoint::create([
                    'campaign_id' => $campaign->id,
                    'environmental_metric_id' => $noiseMetric->id,
                    'user_id' => $user->id,
                    'value' => round($noise, 1),
                    'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                    'accuracy' => rand(30, 80) / 10,
                    'collected_at' => $date->copy()->addHours($hour),
                    'status' => $status,
                    'qa_flags' => $qaFlags,
                    'photo_path' => $this->naturePhotos[array_rand($this->naturePhotos)],
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }

        // Add MORE YELLOW DOTS (low accuracy on Aug 15)
        $testDate = Carbon::parse('2025-08-15');
        for ($i = 0; $i < 10; $i++) {
            $location = $locations[array_rand($locations)];
            \App\Models\DataPoint::create([
                'campaign_id' => $campaign->id,
                'environmental_metric_id' => $noiseMetric->id,
                'user_id' => $user->id,
                'value' => rand(500, 800) / 10,
                'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                'accuracy' => rand(510, 2000) / 10, // 51m to 200m = YELLOW
                'collected_at' => $testDate->copy()->addHours(rand(8, 18)),
                'status' => 'approved',
                'notes' => 'TEST: Low accuracy (yellow marker)',
                'photo_path' => $this->naturePhotos[array_rand($this->naturePhotos)],
            ]);
        }

        // Add MORE RED DOTS (rejected status on Aug 15)
        for ($i = 0; $i < 8; $i++) {
            $location = $locations[array_rand($locations)];
            \App\Models\DataPoint::create([
                'campaign_id' => $campaign->id,
                'environmental_metric_id' => $noiseMetric->id,
                'user_id' => $user->id,
                'value' => rand(400, 900) / 10,
                'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                'accuracy' => rand(30, 80) / 10,
                'collected_at' => $testDate->copy()->addHours(rand(8, 18)),
                'status' => 'rejected',
                'reviewed_by' => $user->id,
                'reviewed_at' => $testDate->copy()->addHours(rand(18, 20)),
                'review_notes' => 'Invalid reading - equipment malfunction suspected',
                'qa_flags' => ['suspicious_value'],
                'notes' => 'TEST: Rejected status (red marker)',
                'photo_path' => $this->naturePhotos[array_rand($this->naturePhotos)],
            ]);
        }

        $this->command->info("✓ Created Noise Pollution campaign with survey zone and data points (Aug 1-30, 2025)");
    }

    /**
     * Campaign 3: Copenhagen Air Quality 2025
     * Location: Multiple neighborhoods across Copenhagen
     * Focus: PM2.5, PM10, CO2, AQI
     */
    private function createCopenhagenAirQualityCampaign(User $user): void
    {
        $campaign = Campaign::firstOrCreate(
            ['name' => 'Copenhagen Air Quality 2025'],
            [
                'description' => 'Monitoring air quality across Copenhagen neighborhoods',
                'status' => 'active',
                'user_id' => $user->id,
                'start_date' => '2025-08-01',
                'end_date' => '2025-08-31',
            ]
        );

        // Create survey zone covering multiple Copenhagen neighborhoods
        SurveyZone::firstOrCreate(
            [
                'campaign_id' => $campaign->id,
                'name' => 'Greater Copenhagen Area'
            ],
            [
                'description' => 'Urban and suburban areas covering key monitoring stations',
                'area' => \DB::raw("ST_GeomFromText('POLYGON((12.5200 55.7000, 12.6000 55.7000, 12.6000 55.6600, 12.5200 55.6600, 12.5200 55.7000))', 4326)"),
                'area_km2' => 3.52,
            ]
        );

        $pm25Metric = EnvironmentalMetric::where('name', 'PM2.5')->first();
        $pm10Metric = EnvironmentalMetric::where('name', 'PM10')->first();
        $co2Metric = EnvironmentalMetric::where('name', 'CO2')->first();
        $aqiMetric = EnvironmentalMetric::where('name', 'Air Quality Index')->first();

        $locations = [
            ['lat' => 55.6761, 'lon' => 12.5683, 'name' => 'City Center'],
            ['lat' => 55.6867, 'lon' => 12.5700, 'name' => 'Nørrebro'],
            ['lat' => 55.6596, 'lon' => 12.5107, 'name' => 'Valby'],
            ['lat' => 55.6828, 'lon' => 12.5878, 'name' => 'Østerbro'],
            ['lat' => 55.6950, 'lon' => 12.5500, 'name' => 'Bispebjerg'],
            ['lat' => 55.6720, 'lon' => 12.5900, 'name' => 'Christianshavn'],
        ];

        // Create data points from Aug 1-30, 2025
        for ($day = 1; $day <= 30; $day++) {
            $date = Carbon::parse("2025-08-{$day}");

            // 3-4 readings per metric per day with varied statuses
            foreach ([$pm25Metric, $pm10Metric, $co2Metric, $aqiMetric] as $metric) {
                for ($i = 0; $i < rand(3, 4); $i++) {
                    $location = $locations[array_rand($locations)];

                    // Generate realistic values based on metric
                    $value = match($metric->name) {
                        'PM2.5' => 12 + rand(-5, 15),
                        'PM10' => 20 + rand(-8, 20),
                        'CO2' => 400 + rand(-20, 50),
                        'Air Quality Index' => 35 + rand(-10, 25),
                        default => 50,
                    };

                    // Vary status: 60% approved, 20% pending, 10% draft, 10% rejected
                    $statusRoll = rand(1, 100);
                    if ($statusRoll <= 60) {
                        $status = 'approved';
                    } elseif ($statusRoll <= 80) {
                        $status = 'pending';
                    } elseif ($statusRoll <= 90) {
                        $status = 'draft';
                    } else {
                        $status = 'rejected';
                    }

                    // Add QA flags based on metric
                    $qaFlags = [];
                    if ($metric->name === 'PM2.5' && $value > 35) {
                        $qaFlags[] = 'outlier';
                    }
                    if ($metric->name === 'Air Quality Index' && $value > 100) {
                        $qaFlags[] = 'suspicious_value';
                    }

                    \App\Models\DataPoint::create([
                        'campaign_id' => $campaign->id,
                        'environmental_metric_id' => $metric->id,
                        'user_id' => $user->id,
                        'value' => round($value, 1),
                        'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                        'accuracy' => rand(30, 80) / 10,
                        'collected_at' => $date->copy()->addHours(rand(8, 18)),
                        'status' => $status,
                        'qa_flags' => $qaFlags,
                        'photo_path' => $this->naturePhotos[array_rand($this->naturePhotos)],
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
            }
        }

        // Add specific test cases on August 15, 2025 for different QA scenarios
        $testDate = Carbon::parse('2025-08-15');

        // Test: Duplicate reading scenario
        $duplicateLocation = ['lat' => 55.6761, 'lon' => 12.5683];
        \App\Models\DataPoint::create([
            'campaign_id' => $campaign->id,
            'environmental_metric_id' => $pm25Metric->id,
            'user_id' => $user->id,
            'value' => 18.5,
            'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$duplicateLocation['lon']}, {$duplicateLocation['lat']}), 4326)"),
            'accuracy' => 5.0,
            'collected_at' => $testDate->copy()->setTime(10, 30),
            'status' => 'pending',
            'qa_flags' => ['duplicate_reading'],
            'notes' => 'TEST: Duplicate reading at same location and time',
            'photo_path' => $this->naturePhotos[0],
        ]);

        // Test: High pollution outlier
        \App\Models\DataPoint::create([
            'campaign_id' => $campaign->id,
            'environmental_metric_id' => $aqiMetric->id,
            'user_id' => $user->id,
            'value' => 125.0,
            'location' => \DB::raw("ST_SetSRID(ST_MakePoint(12.5700, 55.6867), 4326)"),
            'accuracy' => 8.0,
            'collected_at' => $testDate->copy()->setTime(15, 0),
            'status' => 'pending',
            'qa_flags' => ['outlier', 'manual_review'],
            'notes' => 'TEST: Unusually high AQI reading requiring review',
            'photo_path' => $this->naturePhotos[1],
        ]);

        // Add MORE YELLOW DOTS (low accuracy on Aug 15)
        for ($i = 0; $i < 20; $i++) {
            $location = $locations[array_rand($locations)];
            $metric = [$pm25Metric, $pm10Metric, $co2Metric, $aqiMetric][array_rand([$pm25Metric, $pm10Metric, $co2Metric, $aqiMetric])];

            $value = match($metric->name) {
                'PM2.5' => rand(80, 200) / 10,
                'PM10' => rand(150, 350) / 10,
                'CO2' => rand(3800, 4500) / 10,
                'Air Quality Index' => rand(250, 550) / 10,
                default => 50,
            };

            \App\Models\DataPoint::create([
                'campaign_id' => $campaign->id,
                'environmental_metric_id' => $metric->id,
                'user_id' => $user->id,
                'value' => $value,
                'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                'accuracy' => rand(510, 3000) / 10, // 51m to 300m = YELLOW
                'collected_at' => $testDate->copy()->addHours(rand(8, 18)),
                'status' => 'approved',
                'notes' => 'TEST: Low accuracy (yellow marker)',
                'photo_path' => $this->naturePhotos[array_rand($this->naturePhotos)],
            ]);
        }

        // Add MORE RED DOTS (rejected status on Aug 15)
        for ($i = 0; $i < 15; $i++) {
            $location = $locations[array_rand($locations)];
            $metric = [$pm25Metric, $pm10Metric, $co2Metric, $aqiMetric][array_rand([$pm25Metric, $pm10Metric, $co2Metric, $aqiMetric])];

            $value = match($metric->name) {
                'PM2.5' => rand(50, 250) / 10,
                'PM10' => rand(100, 400) / 10,
                'CO2' => rand(3500, 5000) / 10,
                'Air Quality Index' => rand(200, 600) / 10,
                default => 50,
            };

            \App\Models\DataPoint::create([
                'campaign_id' => $campaign->id,
                'environmental_metric_id' => $metric->id,
                'user_id' => $user->id,
                'value' => $value,
                'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$location['lon']}, {$location['lat']}), 4326)"),
                'accuracy' => rand(30, 80) / 10,
                'collected_at' => $testDate->copy()->addHours(rand(8, 18)),
                'status' => 'rejected',
                'reviewed_by' => $user->id,
                'reviewed_at' => $testDate->copy()->addHours(rand(18, 22)),
                'review_notes' => 'Failed quality check - sensor calibration issue',
                'qa_flags' => rand(0, 1) ? ['outlier', 'calibration_overdue'] : ['suspicious_value'],
                'notes' => 'TEST: Rejected status (red marker)',
                'photo_path' => $this->naturePhotos[array_rand($this->naturePhotos)],
            ]);
        }

        $this->command->info("✓ Created Copenhagen Air Quality campaign with survey zone and data points (Aug 1-30, 2025)");
    }
}
