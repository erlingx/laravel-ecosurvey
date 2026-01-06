<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Copernicus Data Space API Configuration...\n";
echo "=========================================\n\n";

// Check config
echo "Copernicus Data Space Config:\n";
echo "  Client ID: " . (config('services.copernicus_dataspace.client_id') ? substr(config('services.copernicus_dataspace.client_id'), 0, 10) . "..." : 'NOT SET') . "\n";
echo "  Client Secret: " . (config('services.copernicus_dataspace.client_secret') ? substr(config('services.copernicus_dataspace.client_secret'), 0, 10) . "..." : 'NOT SET') . "\n\n";

if (!config('services.copernicus_dataspace.client_id') || !config('services.copernicus_dataspace.client_secret')) {
    echo "⚠️  WARNING: Copernicus Data Space credentials not configured!\n\n";
    echo "To get credentials:\n";
    echo "1. Visit: https://dataspace.copernicus.eu/\n";
    echo "2. Sign up for free (no credit card needed)\n";
    echo "3. Go to User Settings → OAuth Clients → Create New\n";
    echo "4. Add to .env:\n";
    echo "   COPERNICUS_CLIENT_ID=your_client_id\n";
    echo "   COPERNICUS_CLIENT_SECRET=your_secret\n\n";
    echo "For now, the system will fallback to NASA API (with mock data).\n";
    exit(0);
}

// Test the service
echo "Testing Copernicus Data Space Service...\n";
echo "This will attempt OAuth authentication and fetch satellite imagery.\n";
echo "Expected time: 2-5 seconds if successful.\n\n";

$service = app(\App\Services\CopernicusDataSpaceService::class);

$startTime = microtime(true);
$data = $service->getSatelliteImagery(55.7072, 12.5704, '2025-08-15'); // Fælledparken, August 15, 2025 (confirmed good data)
$endTime = microtime(true);

$elapsed = round($endTime - $startTime, 2);

echo "\n";
echo "Response received in {$elapsed} seconds\n";
echo "=========================================\n\n";

if ($data) {
    echo "✅ SUCCESS! Copernicus Data Space is working!\n\n";
    echo "Date: " . $data['date'] . "\n";
    echo "Location: " . $data['latitude'] . ", " . $data['longitude'] . "\n";
    echo "Source: " . $data['source'] . "\n";
    echo "Resolution: " . $data['resolution'] . "\n";
    echo "Provider: " . $data['provider'] . "\n";
    echo "URL type: " . (strpos($data['url'], 'data:image') === 0 ? 'Base64 data URL (real imagery)' : 'External URL') . "\n";
    echo "Image size: " . strlen($data['url']) . " chars\n\n";

    echo "🎉 Real Sentinel-2 satellite imagery loaded from ESA Copernicus!\n";
    echo "Image preview: " . substr($data['url'], 0, 100) . "...\n\n";

    // Test NDVI
    echo "\nTesting NDVI data...\n";
    $ndviStartTime = microtime(true);
    $ndviData = $service->getNDVIData(55.7072, 12.5704, '2025-08-15'); // Fælledparken, August 15, 2025 (confirmed good data)
    $ndviEndTime = microtime(true);
    $ndviElapsed = round($ndviEndTime - $ndviStartTime, 2);

    if ($ndviData) {
        echo "✅ NDVI data received in {$ndviElapsed} seconds\n";
        echo "NDVI Value: " . ($ndviData['ndvi_value'] ?? 'N/A') . "\n";
        echo "Interpretation: " . ($ndviData['interpretation'] ?? 'N/A') . "\n\n";
    } else {
        echo "⚠️ NDVI data not available (may require different date or location)\n\n";
    }

} else {
    echo "❌ FAILED - Copernicus Data Space returned no data\n\n";
    echo "Possible reasons:\n";
    echo "1. OAuth credentials are incorrect\n";
    echo "2. No satellite imagery available for this date/location\n";
    echo "3. API quota exceeded (free tier: 1000 requests/month)\n";
    echo "4. Network connectivity issue\n\n";
    echo "Check Laravel logs for details: storage/logs/laravel.log\n\n";
    echo "The system will fallback to NASA API (with mock data).\n";
}

echo "\n";

