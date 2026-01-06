<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataPoint extends Model
{
    /** @use HasFactory<\Database\Factories\DataPointFactory> */
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'environmental_metric_id',
        'user_id',
        'value',
        'location',
        'accuracy',
        'notes',
        'photo_path',
        'collected_at',
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
}
