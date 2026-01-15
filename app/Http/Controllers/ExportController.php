<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\DataExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    public function __construct(
        private DataExportService $exportService
    ) {}

    /**
     * Export campaign data as JSON for publication
     */
    public function exportJSON(Campaign $campaign): JsonResponse
    {
        $data = $this->exportService->exportForPublication($campaign);

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
        $csv = $this->exportService->exportAsCSV($campaign);

        $filename = sprintf(
            'ecosurvey-%s-%s.csv',
            str_replace(' ', '-', strtolower($campaign->name)),
            now()->format('Y-m-d')
        );

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
