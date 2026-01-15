<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SatelliteAnalysis extends Model
{
    /** @use HasFactory<\Database\Factories\SatelliteAnalysisFactory> */
    use HasFactory;

    protected $fillable = [
        'data_point_id',
        'campaign_id',
        'location',
        'image_url',
        'ndvi_value',
        'ndvi_interpretation',
        'moisture_index',
        'temperature_kelvin',
        'acquisition_date',
        'satellite_source',
        'processing_level',
        'cloud_coverage_percent',
        'metadata',
        'evi_value',
        'savi_value',
        'ndre_value',
        'msi_value',
        'gndvi_value',
    ];

    protected function casts(): array
    {
        return [
            'ndvi_value' => 'decimal:4',
            'moisture_index' => 'decimal:4',
            'temperature_kelvin' => 'decimal:2',
            'cloud_coverage_percent' => 'decimal:2',
            'acquisition_date' => 'date',
            'metadata' => 'array',
            'evi_value' => 'decimal:3',
            'savi_value' => 'decimal:3',
            'ndre_value' => 'decimal:3',
            'msi_value' => 'decimal:3',
            'gndvi_value' => 'decimal:3',
        ];
    }

    public function dataPoint(): BelongsTo
    {
        return $this->belongsTo(DataPoint::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
