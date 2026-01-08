<?php

namespace App\Services;

use App\Models\DataPoint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class OutlierDetectionService
{
    /**
     * Detect outliers using IQR (Interquartile Range) method
     * Returns array of DataPoint IDs that are statistical outliers
     */
    public function detectOutliersIQR(int $campaignId, int $metricId, float $multiplier = 1.5): array
    {
        $dataPoints = DataPoint::where('campaign_id', $campaignId)
            ->where('environmental_metric_id', $metricId)
            ->where('status', '!=', 'rejected')
            ->get();

        if ($dataPoints->count() < 4) {
            Log::info('Not enough data points for outlier detection', [
                'campaign_id' => $campaignId,
                'metric_id' => $metricId,
                'count' => $dataPoints->count(),
            ]);

            return [];
        }

        $values = $dataPoints->pluck('value')->sort()->values();

        $q1 = $this->percentile($values, 25);
        $q3 = $this->percentile($values, 75);
        $iqr = $q3 - $q1;

        $lowerBound = $q1 - ($multiplier * $iqr);
        $upperBound = $q3 + ($multiplier * $iqr);

        $outliers = $dataPoints->filter(function ($point) use ($lowerBound, $upperBound) {
            return $point->value < $lowerBound || $point->value > $upperBound;
        });

        Log::info('Outlier detection completed', [
            'campaign_id' => $campaignId,
            'metric_id' => $metricId,
            'total_points' => $dataPoints->count(),
            'outliers_found' => $outliers->count(),
            'q1' => $q1,
            'q3' => $q3,
            'iqr' => $iqr,
            'lower_bound' => $lowerBound,
            'upper_bound' => $upperBound,
        ]);

        return $outliers->pluck('id')->toArray();
    }

    /**
     * Detect outliers using Z-score method
     * Returns array of DataPoint IDs with |z-score| > threshold
     */
    public function detectOutliersZScore(int $campaignId, int $metricId, float $threshold = 3.0): array
    {
        $dataPoints = DataPoint::where('campaign_id', $campaignId)
            ->where('environmental_metric_id', $metricId)
            ->where('status', '!=', 'rejected')
            ->get();

        if ($dataPoints->count() < 3) {
            return [];
        }

        $values = $dataPoints->pluck('value');
        $mean = $values->avg();
        $stdDev = $this->standardDeviation($values);

        if ($stdDev == 0) {
            Log::info('Standard deviation is zero - all values are identical');

            return [];
        }

        $outliers = $dataPoints->filter(function ($point) use ($mean, $stdDev, $threshold) {
            $zScore = abs(($point->value - $mean) / $stdDev);

            return $zScore > $threshold;
        });

        Log::info('Z-score outlier detection completed', [
            'campaign_id' => $campaignId,
            'metric_id' => $metricId,
            'mean' => $mean,
            'std_dev' => $stdDev,
            'outliers_found' => $outliers->count(),
        ]);

        return $outliers->pluck('id')->toArray();
    }

    /**
     * Flag all detected outliers in the database
     */
    public function flagOutliers(int $campaignId, int $metricId, string $method = 'iqr'): int
    {
        $outlierIds = match ($method) {
            'zscore' => $this->detectOutliersZScore($campaignId, $metricId),
            default => $this->detectOutliersIQR($campaignId, $metricId),
        };

        $flaggedCount = 0;
        foreach ($outlierIds as $id) {
            $dataPoint = DataPoint::find($id);
            if ($dataPoint) {
                $dataPoint->flagAsOutlier("Statistical outlier detected using {$method} method");
                $flaggedCount++;
            }
        }

        Log::info('Outliers flagged in database', [
            'campaign_id' => $campaignId,
            'metric_id' => $metricId,
            'method' => $method,
            'flagged_count' => $flaggedCount,
        ]);

        return $flaggedCount;
    }

    /**
     * Calculate percentile of a sorted collection
     */
    private function percentile(Collection $values, float $percentile): float
    {
        $index = ($percentile / 100) * ($values->count() - 1);
        $lower = floor($index);
        $upper = ceil($index);
        $weight = $index - $lower;

        if ($lower == $upper) {
            return $values[$lower];
        }

        return $values[$lower] * (1 - $weight) + $values[$upper] * $weight;
    }

    /**
     * Calculate standard deviation
     */
    private function standardDeviation(Collection $values): float
    {
        $mean = $values->avg();
        $variance = $values->map(function ($value) use ($mean) {
            return pow($value - $mean, 2);
        })->avg();

        return sqrt($variance);
    }
}
