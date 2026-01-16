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
        // Require metric selection to prevent mixing incompatible data
        if (! $metricId) {
            return [];
        }

        // Build WHERE conditions
        $where = ['deleted_at IS NULL'];
        $bindings = [];

        if ($campaignId) {
            $where[] = 'campaign_id = ?';
            $bindings[] = $campaignId;
        }

        $where[] = 'environmental_metric_id = ?';
        $bindings[] = $metricId;

        $whereClause = 'WHERE '.implode(' AND ', $where);

        // Use raw SQL to extract coordinates efficiently in single query
        $results = DB::select(
            "SELECT
                ST_Y(location::geometry) as latitude,
                ST_X(location::geometry) as longitude,
                value
            FROM data_points
            {$whereClause}",
            $bindings
        );

        // Format: [[lat, lng, intensity], ...]
        return array_map(function ($row) {
            return [
                (float) $row->latitude,
                (float) $row->longitude,
                (float) $row->value,
            ];
        }, $results);
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
                DB::raw('STDDEV(value) as std_dev'),
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
            $n = (int) $row->count;
            $avg = (float) $row->average;
            $min = (float) $row->minimum;
            $max = (float) $row->maximum;
            $stdDev = $row->std_dev ? (float) $row->std_dev : 0.0;

            // Calculate 95% confidence interval: CI = mean ± (1.96 * SE)
            // Standard Error (SE) = std_dev / sqrt(n)
            // IMPORTANT: CI is only meaningful with sufficient sample size (n >= 3)

            if ($n < 3) {
                // With n=1 or n=2, CI is unreliable/undefined
                // Don't display CI band - just show the point estimate
                // Users can toggle min/max lines to see the actual range
                $ciLower = $avg;
                $ciUpper = $avg;
                $standardError = 0.0;
            } else {
                // Standard CI calculation for n >= 3
                $standardError = $stdDev / sqrt($n);
                $marginOfError = 1.96 * $standardError; // 95% CI

                // Calculate CI for the mean
                // Note: CI can extend beyond min/max because it estimates the population mean,
                // not the range of individual observations
                $ciLower = $avg - $marginOfError;
                $ciUpper = $avg + $marginOfError;
            }

            return [
                'period' => $row->period,
                'average' => $avg,
                'minimum' => $min,
                'maximum' => $max,
                'std_dev' => $stdDev,
                'count' => $n,
                'ci_lower' => $ciLower,
                'ci_upper' => $ciUpper,
                'standard_error' => $standardError,
            ];
        })->toArray();
    }

    /**
     * Get distribution histogram data with scientifically optimal bin width
     */
    public function getDistributionData(?int $campaignId = null, ?int $metricId = null, ?int $bins = null): array
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

        // Use Freedman-Diaconis rule if bins not specified
        if ($bins === null) {
            sort($values);
            $iqr = $this->calculateIQR($values);
            $n = count($values);
            // Freedman-Diaconis: bin width = 2 * IQR / n^(1/3)
            $binWidth = (2 * $iqr) / pow($n, 1 / 3);

            if ($binWidth > 0) {
                $bins = max(1, (int) ceil(($max - $min) / $binWidth));
                // Cap at reasonable maximum
                $bins = min($bins, 50);
            } else {
                $bins = 10; // Fallback
            }
        }

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
     * Calculate Interquartile Range (IQR) for Freedman-Diaconis rule
     */
    private function calculateIQR(array $sortedValues): float
    {
        $count = count($sortedValues);

        if ($count < 4) {
            return max($sortedValues) - min($sortedValues);
        }

        // Q1 (25th percentile)
        $q1Index = (int) floor($count * 0.25);
        $q1 = $sortedValues[$q1Index];

        // Q3 (75th percentile)
        $q3Index = (int) floor($count * 0.75);
        $q3 = $sortedValues[$q3Index];

        return (float) ($q3 - $q1);
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
