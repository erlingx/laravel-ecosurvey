<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

echo "Checking Campaign Locations:\n";
echo "=============================\n\n";

$campaigns = Campaign::where('status', 'active')->get();

foreach ($campaigns as $campaign) {
    $dataPoint = $campaign->dataPoints()
        ->select([
            'data_points.*',
            DB::raw('ST_X(location::geometry) as longitude'),
            DB::raw('ST_Y(location::geometry) as latitude'),
        ])
        ->first();

    if ($dataPoint) {
        echo "✅ {$campaign->name} (ID: {$campaign->id})\n";
        echo "   Location: {$dataPoint->latitude}, {$dataPoint->longitude}\n\n";
    } else {
        echo "⚠️  {$campaign->name} (ID: {$campaign->id})\n";
        echo "   NO DATA POINTS\n\n";
    }
}

