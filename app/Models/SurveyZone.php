<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class SurveyZone extends Model
{
    /** @use HasFactory<\Database\Factories\SurveyZoneFactory> */
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'name',
        'description',
        'area',
        'area_km2',
    ];

    protected function casts(): array
    {
        return [
            'area_km2' => 'decimal:2',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function dataPoints(): HasMany
    {
        return $this->hasMany(DataPoint::class);
    }

    /**
     * Get data points that are spatially contained within this zone
     */
    public function getContainedDataPoints()
    {
        return DB::select(
            'SELECT dp.* FROM data_points dp, survey_zones sz
             WHERE sz.id = ? AND ST_Contains(sz.area::geometry, dp.location::geometry)',
            [$this->id]
        );
    }

    /**
     * Calculate area in square kilometers using PostGIS
     */
    public function calculateArea(): float
    {
        $result = DB::selectOne(
            'SELECT ST_Area(area::geography) / 1000000 as area_km2 FROM survey_zones WHERE id = ?',
            [$this->id]
        );

        return round($result->area_km2, 2);
    }

    /**
     * Get centroid coordinates [longitude, latitude]
     */
    public function getCentroid(): array
    {
        $result = DB::selectOne(
            'SELECT ST_Y(ST_Centroid(area::geometry)) as lat, ST_X(ST_Centroid(area::geometry)) as lon
             FROM survey_zones WHERE id = ?',
            [$this->id]
        );

        return [$result->lon, $result->lat];
    }

    /**
     * Get bounding box [minLon, minLat, maxLon, maxLat]
     */
    public function getBoundingBox(): array
    {
        $result = DB::selectOne(
            'SELECT
                ST_XMin(ST_Envelope(area::geometry)) as min_lon,
                ST_YMin(ST_Envelope(area::geometry)) as min_lat,
                ST_XMax(ST_Envelope(area::geometry)) as max_lon,
                ST_YMax(ST_Envelope(area::geometry)) as max_lat
             FROM survey_zones WHERE id = ?',
            [$this->id]
        );

        return [
            $result->min_lon,
            $result->min_lat,
            $result->max_lon,
            $result->max_lat,
        ];
    }

    /**
     * Export zone as GeoJSON
     */
    public function toGeoJSON(): array
    {
        $result = DB::selectOne(
            'SELECT ST_AsGeoJSON(area::geometry) as geojson FROM survey_zones WHERE id = ?',
            [$this->id]
        );

        return [
            'type' => 'Feature',
            'properties' => [
                'id' => $this->id,
                'name' => $this->name,
                'description' => $this->description,
                'campaign_id' => $this->campaign_id,
                'area_km2' => $this->area_km2,
            ],
            'geometry' => json_decode($result->geojson, true),
        ];
    }
}
