<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Campaign;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class ReportGeneratorService
{
    public function __construct(
        private DataExportService $exportService,
        private AnalyticsService $analyticsService
    ) {}

    /**
     * Generate comprehensive PDF report for campaign
     */
    public function generatePDF(Campaign $campaign): \Illuminate\Http\Response
    {
        // Get comprehensive data
        $exportData = $this->exportService->exportForPublication($campaign);
        $metrics = $this->getAvailableMetrics($campaign);
        $statistics = [];
        $mapSnapshots = [];

        // Calculate statistics for each metric
        foreach ($metrics as $metric) {
            $stats = $this->analyticsService->calculateStatistics(
                $campaign->id,
                $metric->id
            );

            if ($stats['count'] > 0) {
                $statistics[$metric->name] = [
                    'count' => $stats['count'],
                    'min' => $stats['min'],
                    'max' => $stats['max'],
                    'average' => $stats['average'],
                    'median' => $stats['median'],
                    'std_dev' => $stats['std_dev'],
                    'unit' => $metric->unit,
                ];
            }
        }

        // Get survey zones
        $zones = $campaign->surveyZones()->select([
            'id',
            'name',
            'description',
            DB::raw('ST_Area(area::geography) / 1000000 as area_km2'),
        ])->get();

        $data = [
            'campaign' => $campaign,
            'metadata' => $exportData['metadata'],
            'statistics' => $statistics,
            'zones' => $zones,
            'qa_stats' => $exportData['metadata']['qa_statistics'] ?? null,
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('reports.campaign-pdf', $data);

        $filename = sprintf(
            'ecosurvey-report-%s-%s.pdf',
            str_replace(' ', '-', strtolower($campaign->name)),
            now()->format('Y-m-d')
        );

        return $pdf->download($filename);
    }

    /**
     * Get available metrics for campaign
     */
    private function getAvailableMetrics(Campaign $campaign): \Illuminate\Support\Collection
    {
        return DB::table('environmental_metrics as em')
            ->join('data_points as dp', 'dp.environmental_metric_id', '=', 'em.id')
            ->where('dp.campaign_id', $campaign->id)
            ->select('em.id', 'em.name', 'em.unit')
            ->distinct()
            ->get();
    }
}
