<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

class DataExportService
{
    /**
     * Export campaign data for scientific publication
     * Includes full provenance, metadata, and satellite context
     */
    public function exportForPublication(Campaign $campaign): array
    {
        // Get approved data points with satellite analyses and coordinates
        $dataPoints = DB::select("
            SELECT
                dp.id,
                dp.collected_at,
                ST_Y(dp.location::geometry) as latitude,
                ST_X(dp.location::geometry) as longitude,
                dp.accuracy as accuracy_meters,
                dp.notes,
                dp.value as measurement_value,
                em.name as metric_name,
                em.unit as metric_unit,
                dp.status,
                dp.device_model,
                dp.sensor_type,
                dp.calibration_at,
                dp.protocol_version,
                sa.ndvi_value,
                sa.moisture_index as ndmi_value,
                sa.ndre_value,
                sa.evi_value,
                sa.msi_value,
                sa.savi_value,
                sa.gndvi_value,
                sa.temperature_kelvin,
                sa.acquisition_date as satellite_date,
                sa.cloud_coverage_percent,
                sa.satellite_source,
                ABS(EXTRACT(EPOCH FROM (dp.collected_at - sa.acquisition_date)) / 86400) as temporal_offset_days
            FROM data_points dp
            LEFT JOIN satellite_analyses sa ON dp.id = sa.data_point_id
            LEFT JOIN environmental_metrics em ON dp.environmental_metric_id = em.id
            WHERE dp.campaign_id = ?
              AND dp.status = 'approved'
            ORDER BY dp.collected_at ASC
        ", [$campaign->id]);

        // Calculate QA/QC statistics
        $qaStats = DB::selectOne("
            SELECT
                COUNT(CASE WHEN dp.status = 'approved' THEN 1 END) as approved_count,
                COUNT(CASE WHEN dp.status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN dp.status = 'draft' THEN 1 END) as draft_count,
                COUNT(CASE WHEN dp.status = 'rejected' THEN 1 END) as rejected_count,
                AVG(CASE WHEN dp.status = 'approved' THEN dp.accuracy END) as avg_accuracy_meters,
                COUNT(CASE WHEN sa.id IS NOT NULL THEN 1 END) as satellite_enriched_count
            FROM data_points dp
            LEFT JOIN satellite_analyses sa ON dp.id = sa.data_point_id
            WHERE dp.campaign_id = ?
        ", [$campaign->id]);

        return [
            'metadata' => [
                'export_date' => now()->toIso8601String(),
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'campaign_start' => $campaign->start_date?->toDateString(),
                'campaign_end' => $campaign->end_date?->toDateString(),
                'coordinate_system' => 'WGS84 (EPSG:4326)',
                'qa_statistics' => $qaStats,
                'data_point_count' => count($dataPoints),
                'satellite_indices' => [
                    'NDVI' => 'Normalized Difference Vegetation Index',
                    'NDMI' => 'Normalized Difference Moisture Index',
                    'NDRE' => 'Normalized Difference Red Edge (Chlorophyll)',
                    'EVI' => 'Enhanced Vegetation Index',
                    'MSI' => 'Moisture Stress Index',
                    'SAVI' => 'Soil-Adjusted Vegetation Index',
                    'GNDVI' => 'Green Normalized Difference Vegetation Index',
                ],
                'temporal_correlation_note' => 'temporal_offset_days indicates days between field measurement and satellite observation',
            ],
            'data_points' => collect($dataPoints)->map(function ($dp) {
                return [
                    'id' => $dp->id,
                    'collected_at' => $dp->collected_at,
                    'location' => [
                        'latitude' => (float) $dp->latitude,
                        'longitude' => (float) $dp->longitude,
                        'accuracy_meters' => (float) $dp->accuracy_meters,
                    ],
                    'measurement' => [
                        'value' => (float) $dp->measurement_value,
                        'metric' => $dp->metric_name,
                        'unit' => $dp->metric_unit,
                    ],
                    'notes' => $dp->notes,
                    'quality_control' => [
                        'status' => $dp->status,
                        'device_model' => $dp->device_model,
                        'sensor_type' => $dp->sensor_type,
                        'calibration_date' => $dp->calibration_at,
                        'protocol_version' => $dp->protocol_version,
                    ],
                    'satellite_context' => $dp->ndvi_value ? [
                        'ndvi_value' => (float) $dp->ndvi_value,
                        'ndmi_value' => (float) $dp->ndmi_value,
                        'ndre_value' => $dp->ndre_value ? (float) $dp->ndre_value : null,
                        'evi_value' => $dp->evi_value ? (float) $dp->evi_value : null,
                        'msi_value' => $dp->msi_value ? (float) $dp->msi_value : null,
                        'savi_value' => $dp->savi_value ? (float) $dp->savi_value : null,
                        'gndvi_value' => $dp->gndvi_value ? (float) $dp->gndvi_value : null,
                        'temperature_kelvin' => $dp->temperature_kelvin ? (float) $dp->temperature_kelvin : null,
                        'satellite_date' => $dp->satellite_date,
                        'satellite_source' => $dp->satellite_source,
                        'cloud_coverage_percent' => $dp->cloud_coverage_percent ? (float) $dp->cloud_coverage_percent : null,
                        'temporal_offset_days' => $dp->temporal_offset_days ? (float) $dp->temporal_offset_days : null,
                        'temporal_quality' => $this->getTemporalQuality($dp->temporal_offset_days ? (float) $dp->temporal_offset_days : null),
                    ] : null,
                ];
            })->toArray(),
        ];
    }

    /**
     * Export campaign data as CSV for R/Python analysis
     */
    public function exportAsCSV(Campaign $campaign): string
    {
        $data = $this->exportForPublication($campaign);

        // Build CSV header
        $csv = 'id,collected_at,latitude,longitude,accuracy_meters,metric_name,metric_unit,measurement_value,status,';
        $csv .= 'ndvi,ndmi,ndre,evi,msi,savi,gndvi,temperature_k,';
        $csv .= 'satellite_date,satellite_source,cloud_coverage,temporal_offset_days,temporal_quality,';
        $csv .= "device_model,sensor_type,notes\n";

        // Add data rows
        foreach ($data['data_points'] as $dp) {
            $csv .= implode(',', [
                $dp['id'],
                $dp['collected_at'],
                $dp['location']['latitude'],
                $dp['location']['longitude'],
                $dp['location']['accuracy_meters'],
                '"'.str_replace('"', '""', $dp['measurement']['metric'] ?? '').'"',
                '"'.str_replace('"', '""', $dp['measurement']['unit'] ?? '').'"',
                $dp['measurement']['value'] ?? '',
                $dp['quality_control']['status'],
                $dp['satellite_context']['ndvi_value'] ?? '',
                $dp['satellite_context']['ndmi_value'] ?? '',
                $dp['satellite_context']['ndre_value'] ?? '',
                $dp['satellite_context']['evi_value'] ?? '',
                $dp['satellite_context']['msi_value'] ?? '',
                $dp['satellite_context']['savi_value'] ?? '',
                $dp['satellite_context']['gndvi_value'] ?? '',
                $dp['satellite_context']['temperature_kelvin'] ?? '',
                $dp['satellite_context']['satellite_date'] ?? '',
                '"'.str_replace('"', '""', $dp['satellite_context']['satellite_source'] ?? '').'"',
                $dp['satellite_context']['cloud_coverage_percent'] ?? '',
                $dp['satellite_context']['temporal_offset_days'] ?? '',
                '"'.($dp['satellite_context']['temporal_quality'] ?? '').'"',
                '"'.str_replace('"', '""', $dp['quality_control']['device_model'] ?? '').'"',
                '"'.str_replace('"', '""', $dp['quality_control']['sensor_type'] ?? '').'"',
                '"'.str_replace('"', '""', $dp['notes'] ?? '').'"',
            ])."\n";
        }

        return $csv;
    }

    /**
     * Determine temporal correlation quality based on days offset
     */
    private function getTemporalQuality(?float $offsetDays): string
    {
        if ($offsetDays === null) {
            return 'no_satellite_data';
        }

        if ($offsetDays <= 3) {
            return 'excellent';
        } elseif ($offsetDays <= 7) {
            return 'good';
        } elseif ($offsetDays <= 14) {
            return 'acceptable';
        } else {
            return 'poor';
        }
    }
}
