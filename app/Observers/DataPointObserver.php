<?php

namespace App\Observers;

use App\Jobs\EnrichDataPointWithSatelliteData;
use App\Models\DataPoint;

class DataPointObserver
{
    /**
     * Handle the DataPoint "created" event.
     */
    public function created(DataPoint $dataPoint): void
    {
        EnrichDataPointWithSatelliteData::dispatch($dataPoint);
    }
}
