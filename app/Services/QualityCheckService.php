<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\EnvironmentalMetric;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QualityCheckService
{
    /**
     * Run automated quality checks on a data point
     */
    public function runQualityChecks(DataPoint $dataPoint): array
    {
        $flags = [];

        if ($this->hasHighGPSError($dataPoint)) {
            $flags[] = [
                'type' => 'high_gps_error',
                'reason' => "GPS accuracy {$dataPoint->accuracy}m exceeds threshold (50m)",
                'severity' => 'warning',
                'flagged_at' => now(),
            ];
        }

        if ($outlierCheck = $this->isStatisticalOutlier($dataPoint)) {
            $flags[] = [
                'type' => 'statistical_outlier',
                'reason' => $outlierCheck['reason'],
                'severity' => 'warning',
                'flagged_at' => now(),
                'details' => $outlierCheck['details'],
            ];
        }

        if ($this->isOutsideCampaignZone($dataPoint)) {
            $flags[] = [
                'type' => 'outside_zone',
                'reason' => 'Data point location is outside campaign survey zones',
                'severity' => 'error',
                'flagged_at' => now(),
            ];
        }

        if ($rangeCheck = $this->isOutsideExpectedRange($dataPoint)) {
            $flags[] = [
                'type' => 'unexpected_range',
                'reason' => $rangeCheck['reason'],
                'severity' => 'warning',
                'flagged_at' => now(),
            ];
        }

        return $flags;
    }

    /**
     * Check if GPS accuracy exceeds threshold
     */
    protected function hasHighGPSError(DataPoint $dataPoint): bool
    {
        return $dataPoint->accuracy > 50; // 50m threshold
    }

    /**
     * Check if value is a statistical outlier using IQR method
     */
    protected function isStatisticalOutlier(DataPoint $dataPoint): ?array
    {
        $recentData = DataPoint::where('campaign_id', $dataPoint->campaign_id)
            ->where('environmental_metric_id', $dataPoint->environmental_metric_id)
            ->where('status', 'approved')
            ->where('id', '!=', $dataPoint->id)
            ->whereBetween('collected_at', [
                now()->subDays(30),
                now(),
            ])
            ->pluck('value')
            ->sort()
            ->values();

        if ($recentData->count() < 10) {
            return null; // Not enough data for statistical analysis
        }

        $q1Index = (int) floor($recentData->count() * 0.25);
        $q3Index = (int) floor($recentData->count() * 0.75);

        $q1 = $recentData[$q1Index];
        $q3 = $recentData[$q3Index];
        $iqr = $q3 - $q1;

        $lowerBound = $q1 - (1.5 * $iqr);
        $upperBound = $q3 + (1.5 * $iqr);

        if ($dataPoint->value < $lowerBound || $dataPoint->value > $upperBound) {
            return [
                'reason' => sprintf(
                    'Value %.2f outside expected range [%.2f - %.2f] (IQR method)',
                    $dataPoint->value,
                    $lowerBound,
                    $upperBound
                ),
                'details' => [
                    'value' => $dataPoint->value,
                    'lower_bound' => round($lowerBound, 2),
                    'upper_bound' => round($upperBound, 2),
                    'q1' => round($q1, 2),
                    'q3' => round($q3, 2),
                    'iqr' => round($iqr, 2),
                    'sample_size' => $recentData->count(),
                ],
            ];
        }

        return null;
    }

    /**
     * Check if data point is outside campaign survey zones
     */
    protected function isOutsideCampaignZone(DataPoint $dataPoint): bool
    {
        if (! $dataPoint->location || ! $dataPoint->campaign_id) {
            return false;
        }

        // Check if campaign has any zones defined
        $totalZones = DB::selectOne(
            'SELECT COUNT(*) as zone_count FROM survey_zones WHERE campaign_id = ?',
            [$dataPoint->campaign_id]
        );

        // If no zones defined, don't flag (campaign might not use zones)
        if (! $totalZones || $totalZones->zone_count === 0) {
            return false;
        }

        // Get latitude and longitude from the data point
        $lat = $dataPoint->latitude;
        $lon = $dataPoint->longitude;

        if (! $lat || ! $lon) {
            return false;
        }

        // Check if point is within any zone
        $zonesContainingPoint = DB::selectOne(
            'SELECT COUNT(*) as zone_count
            FROM survey_zones
            WHERE campaign_id = ?
            AND ST_Contains(area::geometry, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geometry)',
            [$dataPoint->campaign_id, $lon, $lat]
        );

        // Flag if campaign has zones but point is in none of them
        return $zonesContainingPoint && $zonesContainingPoint->zone_count === 0;
    }

    /**
     * Check if value is outside expected range for metric type
     */
    protected function isOutsideExpectedRange(DataPoint $dataPoint): ?array
    {
        $metric = EnvironmentalMetric::find($dataPoint->environmental_metric_id);

        if (! $metric || ! $metric->expected_min || ! $metric->expected_max) {
            return null;
        }

        if ($dataPoint->value < $metric->expected_min || $dataPoint->value > $metric->expected_max) {
            return [
                'reason' => sprintf(
                    'Value %.2f outside expected range [%.2f - %.2f] for %s',
                    $dataPoint->value,
                    $metric->expected_min,
                    $metric->expected_max,
                    $metric->name
                ),
            ];
        }

        return null;
    }

    /**
     * Get quality statistics for a campaign
     */
    public function getCampaignQualityStats(Campaign $campaign): array
    {
        $total = $campaign->dataPoints()->count();
        $approved = $campaign->dataPoints()->where('status', 'approved')->count();
        $rejected = $campaign->dataPoints()->where('status', 'rejected')->count();
        $flagged = $campaign->dataPoints()->whereNotNull('qa_flags')->count();
        $highAccuracy = $campaign->dataPoints()->where('accuracy', '<=', 10)->count();

        return [
            'total' => $total,
            'approved' => $approved,
            'rejected' => $rejected,
            'pending' => $total - $approved - $rejected,
            'flagged' => $flagged,
            'high_accuracy' => $highAccuracy,
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0,
            'high_accuracy_rate' => $total > 0 ? round(($highAccuracy / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Get user contribution statistics
     */
    public function getUserContributionStats(int $days = 30): Collection
    {
        return DB::table('data_points')
            ->join('users', 'data_points.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COUNT(*) as total_submissions'),
                DB::raw("COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count"),
                DB::raw("COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count"),
                DB::raw('AVG(accuracy) as avg_accuracy'),
                DB::raw('MIN(data_points.created_at) as first_submission'),
                DB::raw('MAX(data_points.created_at) as last_submission')
            )
            ->where('data_points.created_at', '>=', now()->subDays($days))
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_submissions')
            ->get()
            ->map(function ($user) {
                $user->approval_rate = $user->total_submissions > 0
                    ? round(($user->approved_count / $user->total_submissions) * 100, 1)
                    : 0;
                $user->avg_accuracy = round($user->avg_accuracy ?? 0, 2);

                return $user;
            });
    }

    /**
     * Auto-approve data points that meet quality criteria
     */
    public function autoApproveQualified(): int
    {
        $qualified = DataPoint::where('status', 'pending')
            ->where('accuracy', '<=', 10) // Excellent GPS accuracy
            ->whereNull('qa_flags') // No flags
            ->get();

        $count = 0;
        foreach ($qualified as $dataPoint) {
            $flags = $this->runQualityChecks($dataPoint);

            if (empty($flags)) {
                $dataPoint->update([
                    'status' => 'approved',
                    'reviewed_at' => now(),
                    'review_notes' => 'Auto-approved: High GPS accuracy, no quality issues',
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Flag suspicious readings for review
     */
    public function flagSuspiciousReadings(): int
    {
        $pending = DataPoint::where('status', 'pending')
            ->whereNull('qa_flags')
            ->get();

        $count = 0;
        foreach ($pending as $dataPoint) {
            $flags = $this->runQualityChecks($dataPoint);

            if (! empty($flags)) {
                $dataPoint->update(['qa_flags' => $flags]);
                $count++;
            }
        }

        return $count;
    }
}
