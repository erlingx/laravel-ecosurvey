<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnvironmentalMetric extends Model
{
    /** @use HasFactory<\Database\Factories\EnvironmentalMetricFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function dataPoints(): HasMany
    {
        return $this->hasMany(DataPoint::class);
    }
}
