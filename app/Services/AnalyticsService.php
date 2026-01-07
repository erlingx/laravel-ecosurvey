<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DataPoint;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get heatmap data for Leaflet.heat plugin
     */
    public function getHeatmapData(?int $campaignId = null, ?int $metricId = null): array
    {
        $query = DataPoint::query()
            ->select([
                DB::raw('ST_Y(location::geometry) as latitude'),
                DB::raw('ST_X(location::geometry) as longitude'),
                'value',
            ]);

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        if ($metricId) {
            $query->where('environmental_metric_id', $metricId);
        }

        $dataPoints = $query->get();

        // Format: [[lat, lng, intensity], ...]
        return $dataPoints->map(function ($point) {
            return [
                (float) $point->latitude,
                (float) $point->longitude,
                (float) $point->value,
            ];
        })->toArray();
    }

    /**
     * Calculate statistics for a metric
     */
    public function calculateStatistics(?int $campaignId = null, ?int $metricId = null): array
    {
        $query = DataPoint::query();

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        if ($metricId) {
            $query->where('environmental_metric_id', $metricId);
        }

        $values = $query->pluck('value')->toArray();

        if (empty($values)) {
            return [
                'count' => 0,
                'min' => null,
                'max' => null,
                'average' => null,
                'median' => null,
                'std_dev' => null,
            ];
        }

        sort($values);
        $count = count($values);

        // Cast values to floats
        $values = array_map('floatval', $values);

        return [
            'count' => $count,
            'min' => (float) min($values),
            'max' => (float) max($values),
            'average' => (float) (array_sum($values) / $count),
            'median' => $this->calculateMedian($values),
            'std_dev' => $this->calculateStdDev($values),
        ];
    }

    /**
     * Get time-series data for trend analysis
     */
    public function getTrendData(?int $campaignId = null, ?int $metricId = null, string $interval = 'day'): array
    {
        $query = DataPoint::query()
            ->select([
                DB::raw("DATE_TRUNC('{$interval}', created_at) as period"),
                DB::raw('AVG(value) as average'),
                DB::raw('MIN(value) as minimum'),
                DB::raw('MAX(value) as maximum'),
                DB::raw('COUNT(*) as count'),
            ])
            ->groupBy('period')
            ->orderBy('period');

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        if ($metricId) {
            $query->where('environmental_metric_id', $metricId);
        }

        return $query->get()->map(function ($row) {
            return [
                'period' => $row->period,
                'average' => (float) $row->average,
                'minimum' => (float) $row->minimum,
                'maximum' => (float) $row->maximum,
                'count' => (int) $row->count,
            ];
        })->toArray();
    }

    /**
     * Get distribution histogram data
     */
    public function getDistributionData(?int $campaignId = null, ?int $metricId = null, int $bins = 10): array
    {
        $query = DataPoint::query();

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        if ($metricId) {
            $query->where('environmental_metric_id', $metricId);
        }

        $values = $query->pluck('value')->toArray();

        if (empty($values)) {
            return [];
        }

        $min = min($values);
        $max = max($values);
        $binWidth = ($max - $min) / $bins;

        // Handle case where all values are the same
        if ($binWidth == 0) {
            return [[
                'range' => sprintf('%.1f', $min),
                'count' => count($values),
            ]];
        }

        // Initialize bins
        $histogram = array_fill(0, $bins, 0);
        $binRanges = [];

        for ($i = 0; $i < $bins; $i++) {
            $binRanges[$i] = [
                'min' => $min + ($i * $binWidth),
                'max' => $min + (($i + 1) * $binWidth),
            ];
        }

        // Count values in each bin
        foreach ($values as $value) {
            $binIndex = min((int) floor(($value - $min) / $binWidth), $bins - 1);
            $histogram[$binIndex]++;
        }

        // Format for Chart.js
        return array_map(function ($count, $index) use ($binRanges) {
            return [
                'range' => sprintf('%.1f - %.1f', $binRanges[$index]['min'], $binRanges[$index]['max']),
                'count' => $count,
            ];
        }, $histogram, array_keys($histogram));
    }

    /**
     * Calculate median from sorted array
     */
    private function calculateMedian(array $sortedValues): float
    {
        $count = count($sortedValues);
        $middle = (int) floor($count / 2);

        if ($count % 2 == 0) {
            return (float) (($sortedValues[$middle - 1] + $sortedValues[$middle]) / 2);
        }

        return (float) $sortedValues[$middle];
    }

    /**
     * Calculate standard deviation
     */
    private function calculateStdDev(array $values): float
    {
        $count = count($values);
        if ($count === 0) {
            return 0.0;
        }

        $mean = array_sum($values) / $count;
        $squaredDiffs = array_map(function ($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);

        return sqrt(array_sum($squaredDiffs) / $count);
    }
}
