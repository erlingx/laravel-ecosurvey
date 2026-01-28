#!/usr/bin/env php
<?php

/**
 * Test Queue Dispatching on Production
 *
 * This script tests if jobs are being queued correctly.
 */
echo "🧪 Testing Queue System\n";
echo "======================\n\n";

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check queue connection
echo "1. Queue Configuration\n";
echo '   - Connection: '.config('queue.default')."\n";
echo '   - Driver: '.config('queue.connections.'.config('queue.default').'.driver')."\n";
echo '   - Database Host: '.config('database.connections.'.config('database.default').'.host')."\n";
echo '   - Database Name: '.config('database.connections.'.config('database.default').'.database')."\n\n";

// Check pending jobs before
echo "2. Current Queue Status\n";
$pendingBefore = DB::table('jobs')->count();
$failedCount = DB::table('failed_jobs')->count();
echo "   - Pending: $pendingBefore\n";
echo "   - Failed: $failedCount\n\n";

// Get a recent data point
echo "3. Testing with Real DataPoint\n";
$dataPoint = \App\Models\DataPoint::latest()->first();

if (! $dataPoint) {
    echo "   ❌ No data points found in database\n";
    echo "      Create a data point first\n";
    exit(1);
}

echo "   - Found DataPoint #{$dataPoint->id}\n";
echo "   - Campaign: {$dataPoint->campaign_id}\n";
echo "   - Created: {$dataPoint->created_at}\n\n";

// Dispatch the job manually
echo "4. Dispatching Satellite Enrichment Job\n";
try {
    \App\Jobs\EnrichDataPointWithSatelliteData::dispatch($dataPoint);
    echo "   ✅ Job dispatched successfully\n\n";
} catch (Exception $e) {
    echo '   ❌ Failed to dispatch: '.$e->getMessage()."\n\n";
    exit(1);
}

// Check queue after dispatch
sleep(1);
$pendingAfter = DB::table('jobs')->count();
$newJobs = $pendingAfter - $pendingBefore;

echo "5. Queue Status After Dispatch\n";
echo "   - Pending before: $pendingBefore\n";
echo "   - Pending after: $pendingAfter\n";
echo "   - New jobs: $newJobs\n";

// Show actual job details
if ($pendingAfter > 0) {
    $recentJob = DB::table('jobs')->orderBy('id', 'desc')->first();
    echo "   - Recent job ID: {$recentJob->id}\n";
    echo "   - Queue: {$recentJob->queue}\n";
    echo '   - Available at: '.date('Y-m-d H:i:s', $recentJob->available_at)."\n";
}
echo "\n";

if ($newJobs > 0) {
    echo "✅ SUCCESS: Job was queued!\n";
    echo "\nNext steps:\n";
    echo "1. Wait 1 minute for cron to run\n";
    echo "2. Check logs: tail -30 storage/logs/laravel.log\n";
    echo "3. Look for '🛰️ Starting satellite enrichment job'\n";
} else {
    echo "⚠️ WARNING: Job was not queued (might have run synchronously)\n";
    echo "\nCheck:\n";
    echo "1. .env has QUEUE_CONNECTION=database (not sync)\n";
    echo "2. Run: php artisan config:clear\n";
    echo "3. Check logs for '🛰️ Starting satellite enrichment job'\n";
}

echo "\n";
