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
        'expected_min',
        'expected_max',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'expected_min' => 'decimal:2',
            'expected_max' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function dataPoints(): HasMany
    {
        return $this->hasMany(DataPoint::class);
    }
}
