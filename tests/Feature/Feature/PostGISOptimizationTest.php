<?php

declare(strict_types=1);

use App\Models\DataPoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

test('coordinates are cached on model retrieval to prevent N+1 queries', function () {
    Queue::fake();

    $dataPoint = DataPoint::factory()->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(12.5683, 55.6761), 4326)'),
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $retrievedPoint = DataPoint::find($dataPoint->id);

    $lat1 = $retrievedPoint->latitude;
    $lat2 = $retrievedPoint->latitude;
    $lon1 = $retrievedPoint->longitude;
    $lon2 = $retrievedPoint->longitude;

    $queries = DB::getQueryLog();

    $coordinateQueries = collect($queries)->filter(function ($query) {
        return str_contains($query['query'], 'ST_Y') || str_contains($query['query'], 'ST_X');
    })->count();

    expect($coordinateQueries)->toBeLessThanOrEqual(1);
    expect($lat1)->toBeFloat();
    expect($lat1)->toBe($lat2);
    expect($lon1)->toBeFloat();
    expect($lon1)->toBe($lon2);
});

test('cached coordinates persist across multiple accesses', function () {
    Queue::fake();

    $dataPoint = DataPoint::factory()->create([
        'location' => DB::raw('ST_SetSRID(ST_MakePoint(10.0, 50.0), 4326)'),
    ]);

    $retrieved = DataPoint::find($dataPoint->id);

    for ($i = 0; $i < 10; $i++) {
        $lat = $retrieved->latitude;
        $lon = $retrieved->longitude;
    }

    expect($retrieved->latitude)->toBe(50.0);
    expect($retrieved->longitude)->toBe(10.0);
});

test('coordinates are null when location is null', function () {
    Queue::fake();

    $dataPoint = DataPoint::factory()->create([
        'location' => null,
    ]);

    $retrieved = DataPoint::find($dataPoint->id);

    expect($retrieved->latitude)->toBeNull();
    expect($retrieved->longitude)->toBeNull();
});
