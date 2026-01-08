<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
        'user_id',
        'survey_zone',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dataPoints(): HasMany
    {
        return $this->hasMany(DataPoint::class);
    }

    public function surveyZones(): HasMany
    {
        return $this->hasMany(SurveyZone::class);
    }

    public function satelliteAnalyses(): HasMany
    {
        return $this->hasMany(SatelliteAnalysis::class);
    }

    /**
     * Get intelligent map center for this campaign
     * Priority: 1) Survey zone centroid, 2) Data points bounding box center, 3) Default Copenhagen
     *
     * @return array [longitude, latitude]
     */
    public function getMapCenter(): array
    {
        // Priority 1: Use survey zone centroid if exists
        $surveyZone = $this->surveyZones()->first();
        if ($surveyZone) {
            return $surveyZone->getCentroid();
        }

        // Priority 2: Use data points bounding box center
        $dataPointsCount = $this->dataPoints()->count();
        if ($dataPointsCount > 0) {
            $result = \DB::selectOne(
                'SELECT
                    AVG(ST_X(location::geometry)) as center_lon,
                    AVG(ST_Y(location::geometry)) as center_lat
                 FROM data_points WHERE campaign_id = ?',
                [$this->id]
            );

            if ($result) {
                return [(float) $result->center_lon, (float) $result->center_lat];
            }
        }

        // Priority 3: Default to Copenhagen
        return [12.5683, 55.6761];
    }
}
