<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataPoint extends Model
{
    /** @use HasFactory<\Database\Factories\DataPointFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'campaign_id',
        'environmental_metric_id',
        'survey_zone_id',
        'user_id',
        'value',
        'location',
        'accuracy',
        'notes',
        'photo_path',
        'collected_at',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'qa_flags',
        'device_model',
        'sensor_type',
        'calibration_at',
        'protocol_version',
        'official_value',
        'official_station_name',
        'official_station_distance',
        'variance_percentage',
        'satellite_image_url',
        'ndvi_value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'accuracy' => 'decimal:2',
            'official_value' => 'decimal:2',
            'official_station_distance' => 'decimal:2',
            'variance_percentage' => 'decimal:2',
            'ndvi_value' => 'decimal:4',
            'collected_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'qa_flags' => 'array',
            'calibration_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function environmentalMetric(): BelongsTo
    {
        return $this->belongsTo(EnvironmentalMetric::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function surveyZone(): BelongsTo
    {
        return $this->belongsTo(SurveyZone::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function satelliteAnalyses(): HasMany
    {
        return $this->hasMany(SatelliteAnalysis::class);
    }

    public function scopeHighQuality($query)
    {
        return $query->where('status', 'approved')
            ->where('accuracy', '<=', 50);
    }

    public function flagAsOutlier(string $reason): void
    {
        $flags = $this->qa_flags ?? [];
        $flags[] = [
            'type' => 'outlier',
            'reason' => $reason,
            'flagged_at' => now()->toISOString(),
        ];
        $this->qa_flags = $flags;
        $this->save();
    }
}
