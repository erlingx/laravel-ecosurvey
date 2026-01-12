<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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
            'flagged_at' => now(),
        ];
        $this->qa_flags = $flags;
        $this->save();
    }

    public function approve(User $reviewer, ?string $notes = null): void
    {
        $this->status = 'approved';
        $this->reviewed_by = $reviewer->id;
        $this->reviewed_at = now();
        $this->review_notes = $notes;
        $this->save();
    }

    public function reject(User $reviewer, string $reason): void
    {
        $this->status = 'rejected';
        $this->reviewed_by = $reviewer->id;
        $this->reviewed_at = now();
        $this->review_notes = $reason;
        $this->save();
    }

    public function resetToReview(): void
    {
        $this->status = 'pending';
        $this->reviewed_by = null;
        $this->reviewed_at = null;
        $this->review_notes = null;
        $this->save();
    }

    public function clearFlags(): void
    {
        $this->qa_flags = null;
        $this->save();
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isFlagged(): bool
    {
        return ! empty($this->qa_flags);
    }

    /**
     * Get latitude from PostGIS location field
     */
    public function getLatitudeAttribute(): ?float
    {
        if (! $this->location) {
            return null;
        }

        $result = \DB::selectOne(
            'SELECT ST_Y(location::geometry) as latitude FROM data_points WHERE id = ?',
            [$this->id]
        );

        return $result ? (float) $result->latitude : null;
    }

    /**
     * Get longitude from PostGIS location field
     */
    public function getLongitudeAttribute(): ?float
    {
        if (! $this->location) {
            return null;
        }

        $result = \DB::selectOne(
            'SELECT ST_X(location::geometry) as longitude FROM data_points WHERE id = ?',
            [$this->id]
        );

        return $result ? (float) $result->longitude : null;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        $path = $this->photo_path;

        if (! $path) {
            return null;
        }

        $path = trim($path);

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Check if it's already a /files/ path (new upload location)
        if (str_starts_with($path, '/files/')) {
            return url($path);
        }

        if (str_starts_with($path, 'files/')) {
            return url('/'.$path);
        }

        // New uploads stored in data-points/ go to uploads disk (public/files)
        if (str_starts_with($path, 'data-points/')) {
            // Check if it exists in uploads disk first
            if (Storage::disk('uploads')->exists($path)) {
                return Storage::disk('uploads')->url($path);
            }
        }

        // Legacy paths in storage/app/public (for seeded data)
        if (str_starts_with($path, '/storage/')) {
            $path = ltrim(substr($path, strlen('/storage/')), '/');
        }

        if (str_starts_with($path, 'storage/')) {
            $path = ltrim(substr($path, strlen('storage/')), '/');
        }

        return Storage::disk('public')->url($path);
    }
}
