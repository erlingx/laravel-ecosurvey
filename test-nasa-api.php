<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing NASA API Configuration...\n";
echo "=================================\n\n";

// Check config
echo "NASA_USE_MOCK: " . (config('services.nasa_earth.use_mock') ? 'true' : 'false') . "\n";
echo "NASA_API_KEY: " . substr(config('services.nasa_earth.api_key'), 0, 10) . "...\n\n";

// Test the service
echo "Fetching satellite imagery (this may take 60-120 seconds)...\n";
$service = app(\App\Services\SatelliteService::class);

$startTime = microtime(true);
$data = $service->getSatelliteImagery(55.6761, 12.5683, '2020-01-01');
$endTime = microtime(true);

$elapsed = round($endTime - $startTime, 2);

echo "\n";
echo "Response received in {$elapsed} seconds\n";
echo "=================================\n\n";

if ($data) {
    echo "✅ SUCCESS!\n\n";
    echo "Date: " . $data['date'] . "\n";
    echo "Location: " . $data['latitude'] . ", " . $data['longitude'] . "\n";
    echo "Source: " . $data['source'] . "\n";
    echo "URL type: " . (strpos($data['url'], 'data:image') === 0 ? 'Base64 data URL (real NASA image)' : 'External URL') . "\n";
    echo "URL length: " . strlen($data['url']) . " chars\n";
    echo "Is Mock: " . (isset($data['mock']) && $data['mock'] ? 'YES (using fallback)' : 'NO (real NASA data)') . "\n\n";

    if (isset($data['mock']) && $data['mock']) {
        echo "⚠️  WARNING: Still using mock data!\n";
        echo "Mock URL: " . $data['url'] . "\n";
    } else {
        echo "🎉 Real NASA satellite imagery loaded!\n";
        echo "Image preview: " . substr($data['url'], 0, 100) . "...\n";
    }
} else {
    echo "❌ FAILED - No data returned\n";
}

echo "\n";

