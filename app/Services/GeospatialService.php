<?php

namespace App\Services;

use App\Models\DataPoint;
use Illuminate\Support\Facades\DB;

class GeospatialService
{
    /**
     * Get all data points with their coordinates as GeoJSON
     */
    public function getDataPointsAsGeoJSON(?int $campaignId = null, ?int $metricId = null): array
    {
        $query = DataPoint::query()
            ->with(['campaign', 'environmentalMetric', 'user'])
            ->select([
                'data_points.*',
                DB::raw('ST_X(location::geometry) as longitude'),
                DB::raw('ST_Y(location::geometry) as latitude'),
            ]);

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        if ($metricId) {
            $query->where('environmental_metric_id', $metricId);
        }

        $dataPoints = $query->get();

        return [
            'type' => 'FeatureCollection',
            'features' => $dataPoints->map(function ($point) {
                return [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [(float) $point->longitude, (float) $point->latitude],
                    ],
                    'properties' => [
                        'id' => $point->id,
                        'value' => $point->value,
                        'metric' => $point->environmentalMetric->name,
                        'unit' => $point->environmentalMetric->unit,
                        'campaign' => $point->campaign->name,
                        'user' => $point->user->name,
                        'accuracy' => $point->accuracy,
                        'notes' => $point->notes,
                        'latitude' => (float) $point->latitude,
                        'longitude' => (float) $point->longitude,
                        'photo_path' => $point->photo_url,
                        'collected_at' => $point->collected_at->format('Y-m-d H:i'),
                        'qa_flags' => $point->qa_flags,
                        'status' => $point->status,
                    ],
                ];
            })->toArray(),
        ];
    }

    /**
     * Find data points within a polygon
     */
    public function findPointsInPolygon(array $polygonCoordinates): array
    {
        // Convert coordinates array to WKT format
        $wkt = $this->coordinatesToWKT($polygonCoordinates);

        return DataPoint::query()
            ->whereRaw('ST_Within(location, ST_GeomFromText(?, 4326))', [$wkt])
            ->with(['campaign', 'environmentalMetric', 'user'])
            ->get()
            ->toArray();
    }

    /**
     * Find data points within a radius (in meters) of a point
     */
    public function findPointsInRadius(float $latitude, float $longitude, int $radiusMeters): array
    {
        return DataPoint::query()
            ->select([
                'data_points.*',
                DB::raw('ST_Distance(location::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) as distance'),
            ])
            ->whereRaw(
                'ST_DWithin(location::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                [$longitude, $latitude, $longitude, $latitude, $radiusMeters]
            )
            ->with(['campaign', 'environmentalMetric', 'user'])
            ->orderBy('distance')
            ->get()
            ->toArray();
    }

    /**
     * Calculate distance between two points in meters
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $result = DB::selectOne(
            'SELECT ST_Distance(
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
            ) as distance',
            [$lon1, $lat1, $lon2, $lat2]
        );

        return (float) $result->distance;
    }

    /**
     * Create a buffer zone around a point
     */
    public function createBufferZone(float $latitude, float $longitude, int $radiusMeters): string
    {
        $result = DB::selectOne(
            'SELECT ST_AsGeoJSON(
                ST_Buffer(
                    ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                    ?
                )::geometry
            ) as geojson',
            [$longitude, $latitude, $radiusMeters]
        );

        return $result->geojson;
    }

    /**
     * Convert array of coordinates to WKT polygon format
     */
    private function coordinatesToWKT(array $coordinates): string
    {
        $points = array_map(function ($coord) {
            return "{$coord[0]} {$coord[1]}";
        }, $coordinates);

        // Close the polygon by repeating first point
        $points[] = "{$coordinates[0][0]} {$coordinates[0][1]}";

        return 'POLYGON(('.implode(', ', $points).'))';
    }

    /**
     * Get bounding box for all data points
     */
    public function getBoundingBox(?int $campaignId = null): ?array
    {
        $query = DataPoint::query();

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        $result = $query->selectRaw(
            'ST_XMin(ST_Extent(location::geometry)) as min_lon,
             ST_YMin(ST_Extent(location::geometry)) as min_lat,
             ST_XMax(ST_Extent(location::geometry)) as max_lon,
             ST_YMax(ST_Extent(location::geometry)) as max_lat'
        )->first();

        if (! $result || ! $result->min_lon) {
            return null;
        }

        return [
            'southwest' => [(float) $result->min_lat, (float) $result->min_lon],
            'northeast' => [(float) $result->max_lat, (float) $result->max_lon],
        ];
    }

    /**
     * Get zone-based aggregation statistics using spatial join
     */
    public function getZoneStatistics(int $campaignId): array
    {
        $results = DB::select("
            SELECT
                sz.id as zone_id,
                sz.name as zone_name,
                em.id as metric_id,
                em.name as metric_name,
                em.unit as metric_unit,
                COUNT(dp.id) as point_count,
                AVG(dp.value) as avg_value,
                MIN(dp.value) as min_value,
                MAX(dp.value) as max_value,
                STDDEV(dp.value) as stddev_value
            FROM survey_zones sz
            LEFT JOIN data_points dp ON ST_Contains(sz.area::geometry, dp.location::geometry)
                AND dp.campaign_id = sz.campaign_id
                AND dp.status = 'approved'
            LEFT JOIN environmental_metrics em ON dp.environmental_metric_id = em.id
            WHERE sz.campaign_id = ?
            GROUP BY sz.id, sz.name, em.id, em.name, em.unit
            HAVING COUNT(dp.id) > 0
            ORDER BY sz.name, em.name
        ", [$campaignId]);

        return collect($results)->map(function ($row) {
            return [
                'zone_id' => $row->zone_id,
                'zone_name' => $row->zone_name,
                'metric_id' => $row->metric_id,
                'metric_name' => $row->metric_name,
                'metric_unit' => $row->metric_unit,
                'point_count' => (int) $row->point_count,
                'avg_value' => $row->avg_value ? (float) $row->avg_value : null,
                'min_value' => $row->min_value ? (float) $row->min_value : null,
                'max_value' => $row->max_value ? (float) $row->max_value : null,
                'stddev_value' => $row->stddev_value ? (float) $row->stddev_value : null,
            ];
        })->toArray();
    }

    /**
     * Find K nearest data points using KNN operator
     */
    public function findNearestDataPoints(float $lat, float $lon, int $limit = 5): array
    {
        $results = DB::select("
            SELECT
                dp.id,
                dp.value,
                em.name as metric_name,
                em.unit as metric_unit,
                ST_X(dp.location::geometry) as longitude,
                ST_Y(dp.location::geometry) as latitude,
                ST_Distance(
                    dp.location::geography,
                    ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
                ) as distance_meters
            FROM data_points dp
            LEFT JOIN environmental_metrics em ON dp.environmental_metric_id = em.id
            WHERE dp.status = 'approved'
            ORDER BY dp.location <-> ST_SetSRID(ST_MakePoint(?, ?), 4326)::geometry
            LIMIT ?
        ", [$lon, $lat, $lon, $lat, $limit]);

        return collect($results)->map(function ($row) {
            return [
                'id' => $row->id,
                'value' => $row->value ? (float) $row->value : null,
                'metric_name' => $row->metric_name,
                'metric_unit' => $row->metric_unit,
                'latitude' => (float) $row->latitude,
                'longitude' => (float) $row->longitude,
                'distance_meters' => (float) $row->distance_meters,
            ];
        })->toArray();
    }

    /**
     * Generate grid-based heatmap aggregation
     */
    public function generateGridHeatmap(int $campaignId, int $metricId, float $cellSizeDegrees = 0.001): array
    {
        $results = DB::select("
            SELECT
                ST_X(ST_SnapToGrid(dp.location::geometry, ?, ?)) as grid_x,
                ST_Y(ST_SnapToGrid(dp.location::geometry, ?, ?)) as grid_y,
                COUNT(*) as point_count,
                AVG(dp.value) as avg_value,
                STDDEV(dp.value) as stddev_value
            FROM data_points dp
            WHERE dp.campaign_id = ?
              AND dp.environmental_metric_id = ?
              AND dp.status = 'approved'
            GROUP BY grid_x, grid_y
            HAVING COUNT(*) >= 3
            ORDER BY grid_x, grid_y
        ", [$cellSizeDegrees, $cellSizeDegrees, $cellSizeDegrees, $cellSizeDegrees, $campaignId, $metricId]);

        return collect($results)->map(function ($row) {
            return [
                'longitude' => (float) $row->grid_x,
                'latitude' => (float) $row->grid_y,
                'point_count' => (int) $row->point_count,
                'avg_value' => (float) $row->avg_value,
                'stddev_value' => $row->stddev_value ? (float) $row->stddev_value : null,
            ];
        })->toArray();
    }

    /**
     * Detect spatial clusters using DBSCAN algorithm
     */
    public function detectClusters(int $campaignId, int $metricId, float $epsilonDegrees = 0.01, int $minPoints = 5): array
    {
        $results = DB::select("
            SELECT
                ST_ClusterDBSCAN(dp.location::geometry, eps := ?, minpoints := ?) OVER () as cluster_id,
                dp.id,
                dp.value,
                ST_X(dp.location::geometry) as longitude,
                ST_Y(dp.location::geometry) as latitude
            FROM data_points dp
            WHERE dp.campaign_id = ?
              AND dp.environmental_metric_id = ?
              AND dp.status = 'approved'
        ", [$epsilonDegrees, $minPoints, $campaignId, $metricId]);

        // Group by cluster_id and calculate statistics
        $clusters = collect($results)
            ->filter(fn ($row) => $row->cluster_id !== null)
            ->groupBy('cluster_id')
            ->map(function ($points, $clusterId) {
                return [
                    'cluster_id' => (int) $clusterId,
                    'point_count' => $points->count(),
                    'avg_value' => $points->avg('value'),
                    'center_latitude' => $points->avg('latitude'),
                    'center_longitude' => $points->avg('longitude'),
                    'points' => $points->map(fn ($p) => [
                        'id' => $p->id,
                        'value' => (float) $p->value,
                        'latitude' => (float) $p->latitude,
                        'longitude' => (float) $p->longitude,
                    ])->toArray(),
                ];
            })
            ->values()
            ->toArray();

        return $clusters;
    }

    /**
     * Generate Voronoi diagram for campaign data points
     */
    public function generateVoronoiDiagram(int $campaignId): array
    {
        $result = DB::selectOne("
            SELECT ST_AsGeoJSON(
                ST_VoronoiPolygons(
                    ST_Collect(dp.location::geometry)
                )
            ) as geojson
            FROM data_points dp
            WHERE dp.campaign_id = ?
              AND dp.status = 'approved'
        ", [$campaignId]);

        if (! $result || ! $result->geojson) {
            return [
                'type' => 'FeatureCollection',
                'features' => [],
            ];
        }

        $geometry = json_decode($result->geojson, true);

        return [
            'type' => 'Feature',
            'geometry' => $geometry,
            'properties' => [
                'campaign_id' => $campaignId,
                'type' => 'voronoi_diagram',
            ],
        ];
    }

    /**
     * Calculate convex hull for campaign data points
     */
    public function getCampaignConvexHull(int $campaignId): ?array
    {
        $result = DB::selectOne("
            SELECT
                ST_AsGeoJSON(ST_ConvexHull(ST_Collect(dp.location::geometry))) as geojson,
                ST_Area(ST_ConvexHull(ST_Collect(dp.location::geometry))::geography) as area_square_meters
            FROM data_points dp
            WHERE dp.campaign_id = ?
              AND dp.status = 'approved'
        ", [$campaignId]);

        if (! $result || ! $result->geojson) {
            return null;
        }

        $geometry = json_decode($result->geojson, true);

        return [
            'type' => 'Feature',
            'geometry' => $geometry,
            'properties' => [
                'campaign_id' => $campaignId,
                'area_square_meters' => (float) $result->area_square_meters,
                'area_hectares' => (float) $result->area_square_meters / 10000,
            ],
        ];
    }
}
