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
}
