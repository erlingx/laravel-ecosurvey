<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\CopernicusDataSpaceService;
use Illuminate\Support\Facades\Cache;

echo "🧪 Testing Copernicus Token Auto-Refresh\n";
echo str_repeat('=', 50)."\n\n";

// Step 1: Set an expired token
echo "Step 1: Setting expired token in cache...\n";
Cache::put('copernicus_dataspace_token_data', [
    'token' => 'fake_expired_token_12345',
    'expires_at' => time() - 100, // Expired 100 seconds ago
], 60);
echo "✅ Expired token set\n\n";

// Step 2: Try to fetch NDVI data (should trigger refresh)
echo "Step 2: Attempting to fetch NDVI data...\n";
echo "This should trigger automatic token refresh!\n\n";

$service = new CopernicusDataSpaceService;
$result = $service->getNDVIData(55.7072, 12.5704, '2025-08-15');

echo "\n".str_repeat('=', 50)."\n";
echo "📊 Result:\n";
if ($result) {
    echo "✅ SUCCESS! NDVI data fetched successfully\n";
    echo 'NDVI Value: '.($result['ndvi_value'] ?? 'N/A')."\n";
    echo 'Interpretation: '.($result['interpretation'] ?? 'N/A')."\n";
    echo 'Provider: '.($result['provider'] ?? 'N/A')."\n";
} else {
    echo "❌ FAILED! Could not fetch NDVI data\n";
}

echo "\n".str_repeat('=', 50)."\n";
echo "Check the logs above for these messages:\n";
echo "  - 'Copernicus token expired or near expiry, refreshing...'\n";
echo "  - 'Fetching new Copernicus access token...'\n";
echo "  - '✅ New Copernicus token cached'\n";
echo str_repeat('=', 50)."\n";
