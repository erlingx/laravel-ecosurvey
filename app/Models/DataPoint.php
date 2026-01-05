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
        'collected_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'accuracy' => 'decimal:2',
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
