#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Campaign;

echo "Testing Satellite Viewer Data Sync\n";
echo "==================================\n\n";

// Test 1: Get campaigns and their coordinates
echo "1. Campaign Coordinates:\n";
$campaigns = Campaign::with('dataPoints')
    ->where('status', 'active')
    ->whereHas('dataPoints')
    ->get();

foreach ($campaigns as $campaign) {
    $dataPoint = $campaign->dataPoints()
        ->select([
            'data_points.*',
            DB::raw('ST_X(location::geometry) as longitude'),
            DB::raw('ST_Y(location::geometry) as latitude'),
        ])
        ->first();

    if ($dataPoint) {
        echo sprintf(
            "   %s (ID: %d)\n     → %.6f°N, %.6f°E\n",
            $campaign->name,
            $campaign->id,
            $dataPoint->latitude,
            $dataPoint->longitude
        );
    }
}

// Test 2: Test Copernicus service
echo "\n2. Testing Copernicus Data Space Service:\n";
$service = app(\App\Services\CopernicusDataSpaceService::class);

// Test with Fælledparken coordinates
$testLat = 55.7072;
$testLon = 12.5704;
$testDate = '2025-08-15';

echo "   Testing location: $testLat, $testLon\n";
echo "   Date: $testDate\n";

// Test NDVI overlay
echo '   Fetching NDVI overlay... ';
$ndviData = $service->getOverlayVisualization($testLat, $testLon, $testDate, 'ndvi', 100, 100);
if ($ndviData) {
    echo "✓ Success\n";
    echo sprintf(
        "     Returned coords: %.6f, %.6f (Match: %s)\n",
        $ndviData['latitude'],
        $ndviData['longitude'],
        ($ndviData['latitude'] == $testLat && $ndviData['longitude'] == $testLon) ? 'YES' : 'NO'
    );
} else {
    echo "✗ Failed\n";
}

// Test NDVI analysis
echo '   Fetching NDVI analysis... ';
$ndviAnalysis = $service->getNDVIData($testLat, $testLon, $testDate);
if ($ndviAnalysis) {
    echo "✓ Success\n";
    echo sprintf(
        "     NDVI Value: %.3f (%s)\n",
        $ndviAnalysis['ndvi_value'],
        $ndviAnalysis['interpretation']
    );
} else {
    echo "✗ Failed\n";
}

// Test Moisture overlay
echo '   Fetching Moisture overlay... ';
$moistureData = $service->getOverlayVisualization($testLat, $testLon, $testDate, 'moisture', 100, 100);
if ($moistureData) {
    echo "✓ Success\n";
    echo sprintf(
        "     Returned coords: %.6f, %.6f (Match: %s)\n",
        $moistureData['latitude'],
        $moistureData['longitude'],
        ($moistureData['latitude'] == $testLat && $moistureData['longitude'] == $testLon) ? 'YES' : 'NO'
    );
} else {
    echo "✗ Failed\n";
}

// Test 3: Check cache consistency
echo "\n3. Testing Cache Consistency:\n";
Cache::flush();
echo "   Cache cleared\n";

echo '   First fetch (should hit API)... ';
$first = $service->getOverlayVisualization($testLat, $testLon, $testDate, 'ndvi', 100, 100);
echo $first ? "✓\n" : "✗\n";

echo '   Second fetch (should hit cache)... ';
$second = $service->getOverlayVisualization($testLat, $testLon, $testDate, 'ndvi', 100, 100);
echo $second ? "✓\n" : "✗\n";

if ($first && $second) {
    echo sprintf(
        "   Coordinates match: %s\n",
        ($first['latitude'] == $second['latitude'] && $first['longitude'] == $second['longitude']) ? 'YES ✓' : 'NO ✗'
    );
}

echo "\n✅ Data sync test complete!\n";
