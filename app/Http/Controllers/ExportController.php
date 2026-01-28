<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\DataExportService;
use App\Services\ReportGeneratorService;
use App\Services\UsageTrackingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private DataExportService $exportService,
        private ReportGeneratorService $reportService,
        private UsageTrackingService $usageService
    ) {}

    /**
     * Check if user has reached export limit
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function checkExportLimit(): void
    {
        if (! $this->usageService->canPerformAction(auth()->user(), 'report_exports')) {
            abort(403, 'You have reached your monthly export limit. Upgrade to Pro for more exports!');
        }
    }

    /**
     * Export campaign data as JSON for publication
     */
    public function exportJSON(Campaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);
        $this->checkExportLimit();

        $data = $this->exportService->exportForPublication($campaign);

        // Record usage
        $this->usageService->recordReportExport(auth()->user(), 'json');

        $filename = sprintf(
            'ecosurvey-%s-%s.json',
            str_replace(' ', '-', strtolower($campaign->name)),
            now()->format('Y-m-d')
        );

        return response()->json($data)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Content-Type', 'application/json');
    }

    /**
     * Export campaign data as CSV for R/Python analysis
     */
    public function exportCSV(Campaign $campaign): Response
    {
        $this->authorize('view', $campaign);
        $this->checkExportLimit();

        $csv = $this->exportService->exportAsCSV($campaign);

        // Record usage
        $this->usageService->recordReportExport(auth()->user(), 'csv');

        $filename = sprintf(
            'ecosurvey-%s-%s.csv',
            str_replace(' ', '-', strtolower($campaign->name)),
            now()->format('Y-m-d')
        );

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Generate comprehensive PDF report
     */
    public function exportPDF(Campaign $campaign): Response
    {
        $this->authorize('view', $campaign);
        $this->checkExportLimit();

        $response = $this->reportService->generatePDF($campaign);

        // Record usage
        $this->usageService->recordReportExport(auth()->user(), 'pdf');

        return $response;
    }
}
