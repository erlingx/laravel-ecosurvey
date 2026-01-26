<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking Noise Pollution Campaign Data...\n\n";

$campaign = App\Models\Campaign::where('name', 'Noise Pollution Study')->first();

if (! $campaign) {
    echo "❌ Campaign 'Noise Pollution Study' not found!\n";
    exit(1);
}

echo "✅ Campaign found: {$campaign->name}\n";
echo "   ID: {$campaign->id}\n\n";

$total = $campaign->dataPoints()->count();
echo "📊 Total data points: {$total}\n\n";

$byStatus = $campaign->dataPoints()
    ->select('status', DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();

echo "Status distribution:\n";
foreach ($byStatus as $stat) {
    echo "  - {$stat->status}: {$stat->count}\n";
}

echo "\n";

// Check unique locations
$uniqueLocations = $campaign->dataPoints()
    ->select(DB::raw('DISTINCT ST_AsText(location) as location_text'))
    ->get()
    ->count();

echo "📍 Unique locations: {$uniqueLocations}\n";

// Sample 5 data points
echo "\nSample data points:\n";
$samples = $campaign->dataPoints()
    ->select([
        'id',
        'status',
        'value',
        'collected_at',
        DB::raw('ST_X(location::geometry) as longitude'),
        DB::raw('ST_Y(location::geometry) as latitude'),
    ])
    ->limit(5)
    ->get();

foreach ($samples as $sample) {
    echo "  #{$sample->id}: {$sample->status} | {$sample->value} dB | {$sample->collected_at} | ({$sample->latitude}, {$sample->longitude})\n";
}
