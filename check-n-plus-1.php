<?php

/**
 * N+1 Query Detection Script
 * Runs through key application queries to detect N+1 patterns
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\EnvironmentalMetric;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "🔍 N+1 Query Detection Script\n";
echo "==============================\n\n";

function testQuery(string $name, callable $callback): void
{
    echo "Testing: {$name}\n";
    echo str_repeat('-', 50)."\n";

    DB::enableQueryLog();

    $start = microtime(true);
    $callback();
    $duration = round((microtime(true) - $start) * 1000, 2);

    $queries = DB::getQueryLog();
    $queryCount = count($queries);

    // Detect potential N+1
    $suspiciousQueries = [];
    $queryPatterns = [];

    foreach ($queries as $query) {
        $sql = $query['query'];
        // Normalize query (remove specific IDs)
        $pattern = preg_replace('/\d+/', 'X', $sql);

        if (! isset($queryPatterns[$pattern])) {
            $queryPatterns[$pattern] = 0;
        }
        $queryPatterns[$pattern]++;

        // Check for repeated patterns (sign of N+1)
        if ($queryPatterns[$pattern] > 2) {
            $suspiciousQueries[$pattern] = $queryPatterns[$pattern];
        }
    }

    $status = empty($suspiciousQueries) ? '✅ GOOD' : '⚠️ POTENTIAL N+1';

    echo "Queries: {$queryCount} | Time: {$duration}ms | {$status}\n";

    if (! empty($suspiciousQueries)) {
        echo "\n⚠️ Repeated query patterns detected:\n";
        foreach ($suspiciousQueries as $pattern => $count) {
            $short = substr($pattern, 0, 80).'...';
            echo "  - Executed {$count}x: {$short}\n";
        }
    }

    echo "\n";
    DB::flushQueryLog();
}

// Test 1: Campaign Index
testQuery('Campaign Index (with data points count)', function () {
    Campaign::withCount('dataPoints')->get();
});

// Test 2: Campaign Show with relationships
testQuery('Campaign Show (with zones, metrics, data points)', function () {
    $campaign = Campaign::with(['surveyZones', 'dataPoints.environmentalMetric', 'dataPoints.user'])
        ->first();

    if ($campaign) {
        // Access relationships
        foreach ($campaign->dataPoints->take(5) as $point) {
            $metric = $point->environmentalMetric->name;
            $user = $point->user->name;
        }
    }
});

// Test 3: Data Points Index (common in tables)
testQuery('Data Points Index (paginated with relationships)', function () {
    $points = DataPoint::with(['campaign', 'environmentalMetric', 'user'])
        ->limit(20)
        ->get();

    foreach ($points as $point) {
        $campaign = $point->campaign->name;
        $metric = $point->environmentalMetric->name;
        $user = $point->user->name;
    }
});

// Test 4: Data Points WITHOUT eager loading (should show N+1)
testQuery('Data Points WITHOUT eager loading (BAD - expect N+1)', function () {
    $points = DataPoint::limit(10)->get();

    foreach ($points as $point) {
        $campaign = $point->campaign->name;
        $metric = $point->environmentalMetric->name;
        $user = $point->user->name;
    }
});

// Test 5: Users with data points count
testQuery('Users Index (with data points count)', function () {
    User::withCount('dataPoints')->get();
});

// Test 6: GeospatialService (already optimized)
testQuery('GeospatialService::getDataPointsAsGeoJSON', function () {
    $service = app(\App\Services\GeospatialService::class);
    $service->getDataPointsAsGeoJSON(null, null, false);
});

// Test 7: Quality Check Service (if used frequently)
testQuery('QualityCheckService::runQualityChecks', function () {
    $service = app(\App\Services\QualityCheckService::class);
    $point = DataPoint::with(['campaign', 'environmentalMetric'])->first();

    if ($point) {
        $service->runQualityChecks($point);
    }
});

// Test 8: Environmental Metrics with data points
testQuery('Environmental Metrics (with data points stats)', function () {
    EnvironmentalMetric::withCount('dataPoints')
        ->withAvg('dataPoints', 'value')
        ->get();
});

echo "\n✅ N+1 Detection Complete!\n";
echo "\nRecommendations:\n";
echo "- Look for tests marked with ⚠️ POTENTIAL N+1\n";
echo "- Check if eager loading is being used (with, withCount)\n";
echo "- Use Laravel Debugbar in browser for visual query inspection\n";
echo "- Run: ddev artisan debugbar:clear to reset debugbar storage\n";
