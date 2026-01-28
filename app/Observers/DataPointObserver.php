<?php

namespace App\Observers;

use App\Jobs\EnrichDataPointWithSatelliteData;
use App\Models\DataPoint;
use Illuminate\Support\Facades\Cache;

class DataPointObserver
{
    /**
     * Handle the DataPoint "created" event.
     */
    public function created(DataPoint $dataPoint): void
    {
        \Log::info('📍 DataPoint created, dispatching satellite enrichment job', [
            'data_point_id' => $dataPoint->id,
            'campaign_id' => $dataPoint->campaign_id,
            'queue_connection' => config('queue.default'),
        ]);

        EnrichDataPointWithSatelliteData::dispatch($dataPoint);

        \Log::info('✅ Satellite enrichment job dispatched to queue', [
            'data_point_id' => $dataPoint->id,
        ]);

        $this->clearMapCache($dataPoint);
    }

    /**
     * Handle the DataPoint "updated" event.
     */
    public function updated(DataPoint $dataPoint): void
    {
        $this->clearMapCache($dataPoint);
    }

    /**
     * Handle the DataPoint "deleted" event.
     */
    public function deleted(DataPoint $dataPoint): void
    {
        $this->clearMapCache($dataPoint);
    }

    /**
     * Clear map cache for affected campaigns and metrics
     */
    protected function clearMapCache(DataPoint $dataPoint): void
    {
        // Clear all campaign-related caches
        Cache::forget('survey_map_data_'.$dataPoint->campaign_id.'_all');
        Cache::forget('survey_map_bbox_'.$dataPoint->campaign_id);

        // Clear metric-specific cache
        Cache::forget('survey_map_data_'.$dataPoint->campaign_id.'_'.$dataPoint->environmental_metric_id);

        // Clear "all campaigns" cache
        Cache::forget('survey_map_data_all_all');
        Cache::forget('survey_map_data_all_'.$dataPoint->environmental_metric_id);
        Cache::forget('survey_map_bbox_all');
    }
}
