<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SatelliteApiCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_point_id',
        'campaign_id',
        'user_id',
        'call_type',
        'index_type',
        'latitude',
        'longitude',
        'acquisition_date',
        'cached',
        'response_time_ms',
        'cost_credits',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'acquisition_date' => 'date',
            'cached' => 'boolean',
            'cost_credits' => 'decimal:4',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
