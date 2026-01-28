#!/usr/bin/env php
<?php

/**
 * Check if DataPoint #628 was enriched with satellite data
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🛰️ Checking DataPoint Satellite Enrichment\n";
echo "==========================================\n\n";

$dataPoint = \App\Models\DataPoint::find(628);

if (! $dataPoint) {
    echo "❌ DataPoint #628 not found\n";
    exit(1);
}

echo "DataPoint #628:\n";
echo "  - ID: {$dataPoint->id}\n";
echo "  - Campaign: {$dataPoint->campaign_id}\n";
echo "  - Created: {$dataPoint->created_at}\n";
echo "  - Updated: {$dataPoint->updated_at}\n\n";

echo "Satellite Data:\n";
echo '  - NDVI Value: '.($dataPoint->ndvi_value ?? 'NULL')."\n";
echo '  - Satellite Image URL: '.($dataPoint->satellite_image_url ? 'YES' : 'NULL')."\n";
echo '  - NDMI Value: '.($dataPoint->ndmi_value ?? 'NULL')."\n";
echo '  - NDRE Value: '.($dataPoint->ndre_value ?? 'NULL')."\n";
echo '  - EVI Value: '.($dataPoint->evi_value ?? 'NULL')."\n\n";

// Check if satellite analysis record exists
$analysis = \App\Models\SatelliteAnalysis::where('data_point_id', 628)->latest()->first();

if ($analysis) {
    echo "Satellite Analysis Record:\n";
    echo "  - ID: {$analysis->id}\n";
    echo "  - Created: {$analysis->created_at}\n";
    echo "  - NDVI: {$analysis->ndvi}\n";
    echo "  - Status: {$analysis->processing_status}\n\n";
    echo "✅ DataPoint HAS been enriched with satellite data!\n";
} else {
    echo "❌ No satellite analysis record found\n";
    echo "\nThis means either:\n";
    echo "1. The job hasn't run yet\n";
    echo "2. The job failed silently\n";
    echo "3. The job is skipping enrichment due to business logic\n\n";

    echo "Check logs for satellite enrichment:\n";
    echo "  tail -200 storage/logs/laravel.log | grep -A5 'satellite enrichment'\n";
}
