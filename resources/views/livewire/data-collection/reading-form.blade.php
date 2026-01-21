<?php

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\EnvironmentalMetric;
use App\Services\UsageTrackingService;
use Livewire\WithFileUploads;

use function Livewire\Volt\computed;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;
use function Livewire\Volt\updated;
use function Livewire\Volt\uses;

uses([WithFileUploads::class]);

state([
    'dataPointId' => null,
    'campaignId' => null,
    'metricId' => null,
    'value' => null,
    'notes' => '',
    // GPS Capture (auto from device)
    'latitude' => null,
    'longitude' => null,
    'accuracy' => null,
    'gpsStatus' => 'idle', // idle, requesting, success, error
    'gpsError' => null,
    // Manual Entry (user types)
    'manualLatitude' => null,
    'manualLongitude' => null,
    // Other fields
    'photo' => null,
    'existingPhotoPath' => null,
    'existingPhotoVersion' => null,
    'deviceModel' => null,
    'sensorType' => null,
    'calibrationDate' => null,
    // QA/Review fields
    'status' => 'pending',
    'reviewNotes' => '',
    'inModal' => false, // Track if form is in modal context
]);

state([
    'qaFlags' => [],
    'showFlagModal' => false,
    'flagType' => '',
    'flagReason' => '',
]);

mount(function ($dataPoint = null, $inModal = false) {
    $this->inModal = $inModal;

    // If editing existing data point, fetch it if we got an ID
    if ($dataPoint) {
        // If $dataPoint is a string/int (ID), fetch the model
        if (is_string($dataPoint) || is_int($dataPoint)) {
            $dataPoint = DataPoint::query()->findOrFail($dataPoint);
        }

        $this->dataPointId = $dataPoint->id;
        $this->campaignId = $dataPoint->campaign_id;
        $this->metricId = $dataPoint->environmental_metric_id;
        $this->value = $dataPoint->value;
        $this->notes = $dataPoint->notes;
        $this->latitude = $dataPoint->latitude;
        $this->longitude = $dataPoint->longitude;
        $this->accuracy = $dataPoint->accuracy;
        $this->existingPhotoPath = $dataPoint->photo_path;
        $this->deviceModel = $dataPoint->device_model;
        $this->sensorType = $dataPoint->sensor_type;
        $this->calibrationDate = $dataPoint->calibration_at?->format('Y-m-d');
        $this->status = $dataPoint->status ?? 'pending';
        $this->reviewNotes = $dataPoint->review_notes ?? '';
        $this->qaFlags = $dataPoint->qa_flags ?? [];

        // Set GPS status to success if we have coordinates
        if ($this->latitude && $this->longitude) {
            $this->gpsStatus = 'success';
        }
    } else {
        // Auto-select first campaign if only one exists (create mode)
        $campaigns = Campaign::query()->get();
        if ($campaigns->count() === 1) {
            $this->campaignId = $campaigns->first()->id;
        }
    }
});

$campaigns = computed(fn () => Campaign::query()
    ->select('id', 'name', 'status')
    ->where('status', 'active')
    ->orderBy('name')
    ->get()
);

$metrics = computed(fn () => EnvironmentalMetric::query()
    ->select('id', 'name', 'unit', 'description')
    ->where('is_active', true)
    ->orderBy('name')
    ->get()
);

// Clear validation errors when fields are updated
updated([
    'campaignId' => fn () => $this->resetErrorBag('campaignId'),
    'metricId' => fn () => $this->resetErrorBag('metricId'),
    'value' => fn () => $this->resetErrorBag('value'),
    'latitude' => fn () => $this->resetErrorBag(['latitude', 'location']),
    'longitude' => fn () => $this->resetErrorBag(['longitude', 'location']),
    'manualLatitude' => fn () => $this->resetErrorBag(['manualLatitude', 'location']),
    'manualLongitude' => fn () => $this->resetErrorBag(['manualLongitude', 'location']),
]);

$save = function () {
    $usageService = app(UsageTrackingService::class);

    // Check usage limits for NEW data points only (not edits)
    if (! $this->dataPointId) {
        if (! $usageService->canPerformAction(auth()->user(), 'data_points')) {
            $remaining = $usageService->getRemainingQuota(auth()->user(), 'data_points');
            $tier = auth()->user()->subscriptionTier();

            $this->addError('usage_limit', "You've reached your monthly limit for data points. Upgrade to Pro for 10x more!");
            $this->dispatch('saving-failed');
            $this->dispatch('usage-limit-reached', resource: 'data_points', tier: $tier);

            return;
        }
    }

    // Custom validation: require either GPS OR manual coordinates
    $this->validate([
        'campaignId' => 'required|exists:campaigns,id',
        'metricId' => 'required|exists:environmental_metrics,id',
        'value' => 'required|numeric',
        'notes' => 'nullable|string|max:1000',
        'photo' => 'nullable|image|max:5120', // 5MB max
        'deviceModel' => 'nullable|string|max:100',
        'sensorType' => 'nullable|string|max:50',
        'calibrationDate' => 'nullable|date|before_or_equal:today',
        'status' => 'nullable|in:draft,pending,approved,rejected',
        'reviewNotes' => 'nullable|string|max:1000',
    ]);

    // Validate coordinates: either GPS OR manual entry required (skip if editing existing point)
    if (! $this->dataPointId) {
        // For new submissions, require coordinates
        if (! $this->latitude && ! $this->manualLatitude) {
            $this->addError('location', 'Either capture GPS or enter coordinates manually');
            $this->dispatch('saving-failed');

            return;
        }
    }

    // Get coordinates from form or existing data point
    if ($this->dataPointId) {
        // For edit mode, use form values if provided, otherwise keep existing
        $existingDataPoint = DataPoint::query()->find($this->dataPointId);

        // Get coordinates - either from form or from existing data point
        // Note: latitude/longitude are computed from PostGIS location field
        $finalLatitude = $this->latitude ?? $this->manualLatitude;
        $finalLongitude = $this->longitude ?? $this->manualLongitude;

        // If no coordinates in form, get from existing data point
        if (! $finalLatitude || ! $finalLongitude) {
            $finalLatitude = $existingDataPoint->latitude;
            $finalLongitude = $existingDataPoint->longitude;
        }

        $finalAccuracy = $this->accuracy ?? $existingDataPoint->accuracy ?? 0;
    } else {
        // For new submissions, use provided coordinates
        $finalLatitude = $this->latitude ?? $this->manualLatitude;
        $finalLongitude = $this->longitude ?? $this->manualLongitude;
        $finalAccuracy = $this->latitude ? $this->accuracy : 0; // 0m for manual entry
    }

    // Validate final coordinates
    if (! is_numeric($finalLatitude) || $finalLatitude < -90 || $finalLatitude > 90) {
        $this->addError('latitude', 'Latitude must be between -90 and 90');
        $this->dispatch('saving-failed');

        return;
    }
    if (! is_numeric($finalLongitude) || $finalLongitude < -180 || $finalLongitude > 180) {
        $this->addError('longitude', 'Longitude must be between -180 and 180');
        $this->dispatch('saving-failed');

        return;
    }

    // Store photo if uploaded
    $photoPath = $this->existingPhotoPath; // Keep existing if not changed
    \Log::info('PHOTO DEBUG 1: Before photo logic', [
        'existingPhotoPath' => $this->existingPhotoPath,
        'photoPath' => $photoPath,
        'hasNewPhoto' => (bool) $this->photo,
    ]);
    if ($this->photo) {
        // Delete old photo if exists
        if ($this->existingPhotoPath && ! str_starts_with($this->existingPhotoPath, 'http')) {
            // Try both disks for backward compatibility
            \Storage::disk('uploads')->delete($this->existingPhotoPath);
            \Storage::disk('public')->delete($this->existingPhotoPath);
        }
        $photoPath = $this->photo->store('data-points', 'uploads');
    }
    \Log::info('PHOTO DEBUG 2: After photo logic', ['photoPath' => $photoPath]);

    $data = [
        'campaign_id' => $this->campaignId,
        'environmental_metric_id' => $this->metricId,
        'user_id' => auth()->id(),
        'value' => $this->value,
        'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$finalLongitude}, {$finalLatitude}), 4326)"),
        'accuracy' => $finalAccuracy,
        'notes' => $this->notes,
        'photo_path' => $photoPath,
        'device_model' => $this->deviceModel,
        'sensor_type' => $this->sensorType,
        'calibration_at' => $this->calibrationDate ? \Carbon\Carbon::parse($this->calibrationDate) : null,
        'status' => $this->status ?? 'pending',
        'review_notes' => $this->reviewNotes,
    ];

    if ($this->dataPointId) {
        // Update existing data point
        $dataPoint = DataPoint::query()->findOrFail($this->dataPointId);

        // Update all fields except location
        $dataPoint->campaign_id = $data['campaign_id'];
        $dataPoint->environmental_metric_id = $data['environmental_metric_id'];
        $dataPoint->user_id = $data['user_id'];
        $dataPoint->value = $data['value'];
        $dataPoint->accuracy = $data['accuracy'];
        $dataPoint->notes = $data['notes'];
        $dataPoint->photo_path = $data['photo_path'];
        $dataPoint->device_model = $data['device_model'];
        $dataPoint->sensor_type = $data['sensor_type'];
        $dataPoint->calibration_at = $data['calibration_at'];
        $dataPoint->status = $data['status'];
        $dataPoint->review_notes = $data['review_notes'];

        // Save all non-geometry fields first
        $dataPoint->save();

        // Debug: Check what was actually saved
        $freshDataPoint = DataPoint::query()->find($dataPoint->id);
        \Log::info('PHOTO DEBUG 3: After save', [
            'model_photo_path' => $dataPoint->photo_path,
            'db_photo_path' => $freshDataPoint->photo_path,
        ]);

        // Now update location using raw SQL (after save to avoid conflicts)
        \DB::statement('UPDATE data_points SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
            [$finalLongitude, $finalLatitude, $this->dataPointId]);

        $message = 'Reading updated successfully!';
    } else {
        // Create new data point
        $data['collected_at'] = now();
        $data['status'] = 'pending';
        $dataPoint = DataPoint::query()->create($data);

        // Record usage for new data point
        $usageService->recordDataPointCreation(auth()->user());

        $message = 'Reading submitted successfully!';
    }

    // Auto-flag based on data quality
    $qaFlags = [];

    // Flag location uncertainty if GPS accuracy is poor (>80m)
    if ($finalAccuracy && $finalAccuracy > 80) {
        $qaFlags[] = 'location_uncertainty';
    }

    // Flag calibration overdue if last calibration was >90 days ago
    if ($this->calibrationDate) {
        $calibrationDate = \Carbon\Carbon::parse($this->calibrationDate);
        if ($calibrationDate->diffInDays(now()) > 90) {
            $qaFlags[] = 'calibration_overdue';
        }
    }

    // Save QA flags if any were triggered
    if (! empty($qaFlags)) {
        $dataPoint->qa_flags = $qaFlags;
        $dataPoint->save();
    }

    // Update component state with the saved photo path and clear temporary upload
    $this->existingPhotoPath = $photoPath;
    $this->photo = null;

    session()->flash('success', $message);

    // Handle differently based on context
    if ($this->inModal) {
        // Close modal and refresh map
        $this->dispatch('data-point-saved');
        $this->dispatch('close-edit-modal');
    } else {
        // Keep UI in a loading state through navigation.
        $this->redirectRoute('maps.survey', navigate: true);
    }
};

$clearFlags = function () {
    if (! $this->dataPointId) {
        return;
    }

    $dataPoint = DataPoint::query()->findOrFail($this->dataPointId);
    $dataPoint->qa_flags = null;
    $dataPoint->save();

    // Update local state
    $this->qaFlags = [];

    session()->flash('success', 'QA flags cleared! The marker will show as '.($dataPoint->status === 'approved' ? 'green (approved)' : 'blue (pending)').' on the map after saving.');
};

$openFlagModal = function () {
    $this->flagType = '';
    $this->flagReason = '';
    $this->showFlagModal = true;
};

$addFlag = function () {
    if (! $this->dataPointId) {
        return;
    }

    $this->validate([
        'flagType' => 'required|string',
        'flagReason' => 'required|string|min:5|max:500',
    ]);

    $dataPoint = DataPoint::query()->findOrFail($this->dataPointId);

    $flags = $dataPoint->qa_flags ?? [];
    $flags[] = [
        'type' => $this->flagType,
        'reason' => $this->flagReason,
        'flagged_at' => now(),
    ];

    $dataPoint->qa_flags = $flags;
    $dataPoint->save();

    $this->qaFlags = $flags;
    $this->showFlagModal = false;
    $this->flagType = '';
    $this->flagReason = '';

    session()->flash('success', 'QA flag added successfully! This data point will now show as red on the map.');
};

$removeFlag = function ($index) {
    if (! $this->dataPointId) {
        return;
    }

    $dataPoint = DataPoint::query()->findOrFail($this->dataPointId);

    $flags = $dataPoint->qa_flags ?? [];

    // Remove the flag at the specified index
    if (isset($flags[$index])) {
        array_splice($flags, $index, 1);

        $dataPoint->qa_flags = empty($flags) ? null : array_values($flags);
        $dataPoint->save();

        $this->qaFlags = $dataPoint->qa_flags ?? [];

        $message = empty($flags)
            ? 'Last QA flag removed! The marker will show as '.($dataPoint->status === 'approved' ? 'green (approved)' : 'blue (pending)').' on the map.'
            : 'QA flag removed successfully!';

        session()->flash('success', $message);
    }
};

$existingPhotoUrl = computed(function () {
    $path = $this->existingPhotoPath;

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

    // New uploads in public/files
    if (str_starts_with($path, '/files/')) {
        return url($path);
    }

    if (str_starts_with($path, 'files/')) {
        return url('/'.$path);
    }

    // Try uploads disk first for data-points
    if (str_starts_with($path, 'data-points/')) {
        if (\Storage::disk('uploads')->exists($path)) {
            $url = \Storage::disk('uploads')->url($path);
            if ($this->existingPhotoVersion) {
                $url .= (str_contains($url, '?') ? '&' : '?').'v='.$this->existingPhotoVersion;
            }

            return $url;
        }
    }

    // Legacy: storage/app/public paths
    if (str_starts_with($path, '/storage/')) {
        $path = ltrim(substr($path, strlen('/storage/')), '/');
    }

    if (str_starts_with($path, 'storage/')) {
        $path = ltrim(substr($path, strlen('storage/')), '/');
    }

    $url = \Storage::disk('public')->url($path);

    if ($this->existingPhotoVersion) {
        $url .= (str_contains($url, '?') ? '&' : '?').'v='.$this->existingPhotoVersion;
    }

    return $url;
});

$formatQaFlag = function (string|array $flag): array {
    $flagMap = [
        'outlier' => [
            'name' => 'Statistical Outlier',
            'description' => 'Value significantly deviates from expected range for this location/metric',
            'icon' => '📊',
        ],
        'location_uncertainty' => [
            'name' => 'Location Uncertainty',
            'description' => 'GPS accuracy is poor (>80m). Location may not be precise.',
            'icon' => '📍',
        ],
        'calibration_overdue' => [
            'name' => 'Calibration Overdue',
            'description' => 'Sensor calibration is more than 90 days old',
            'icon' => '⚙️',
        ],
        'suspicious_value' => [
            'name' => 'Suspicious Value',
            'description' => 'Value is outside realistic range for this metric',
            'icon' => '⚠️',
        ],
        'manual_review' => [
            'name' => 'Manual Review Required',
            'description' => 'This data point has been flagged for manual review',
            'icon' => '👁️',
        ],
        'data_quality' => [
            'name' => 'Data Quality Concern',
            'description' => 'General data quality issue requiring investigation',
            'icon' => '🔍',
        ],
    ];

    // If it's an object/array with type/reason
    if (is_array($flag)) {
        $type = $flag['type'] ?? $flag;
        $reason = $flag['reason'] ?? null;

        if (isset($flagMap[$type])) {
            $result = $flagMap[$type];
            if ($reason) {
                $result['description'] = $reason;
            }

            return $result;
        }

        return [
            'name' => ucwords(str_replace('_', ' ', $type)),
            'description' => $reason ?? 'Data quality issue detected',
            'icon' => '⚠️',
        ];
    }

    // Simple string flag
    if (isset($flagMap[$flag])) {
        return $flagMap[$flag];
    }

    return [
        'name' => ucwords(str_replace('_', ' ', $flag)),
        'description' => 'Data quality issue detected',
        'icon' => '⚠️',
    ];
};

?>

<div class="max-w-2xl mx-auto relative"
     x-data="{
    isSaving: false,
    captureLocation() {
        if (!navigator.geolocation) {
            this.$wire.set('gpsError', 'Geolocation is not supported by your browser');
            this.$wire.set('gpsStatus', 'error');
            return;
        }

        this.$wire.set('gpsStatus', 'requesting');
        this.$wire.set('gpsError', null);

        navigator.geolocation.getCurrentPosition(
            (position) => {
                // Round to 6 decimal places (~0.11m precision, prevents validation issues)
                const lat = parseFloat(position.coords.latitude.toFixed(6));
                const lng = parseFloat(position.coords.longitude.toFixed(6));
                const acc = Math.round(position.coords.accuracy);

                // Clear manual entry when GPS is captured
                this.$wire.set('manualLatitude', null);
                this.$wire.set('manualLongitude', null);

                // Set GPS coordinates
                this.$wire.set('latitude', lat);
                this.$wire.set('longitude', lng);
                this.$wire.set('accuracy', acc);
                this.$wire.set('gpsStatus', 'success');
            },
            (error) => {
                let errorMessage = 'Unable to retrieve location';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = 'Location permission denied. Please enable location access.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = 'Location information unavailable.';
                        break;
                    case error.TIMEOUT:
                        errorMessage = 'Location request timed out.';
                        break;
                }
                this.$wire.set('gpsError', errorMessage);
                this.$wire.set('gpsStatus', 'error');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    },
    onManualEntry() {
        // Clear GPS when user starts manual entry
        this.$wire.set('latitude', null);
        this.$wire.set('longitude', null);
        this.$wire.set('accuracy', null);
        this.$wire.set('gpsStatus', 'idle');
    },
    async submitForm() {
        if (this.isSaving) return;

        this.isSaving = true;

        try {
            await this.$wire.save();
            // After save completes, wait briefly then check for validation errors
            await new Promise(r => setTimeout(r, 100));
            // Check if there are any validation error messages visible
            const errorElements = document.querySelectorAll('[data-flux-error]');
            const hasErrors = Array.from(errorElements).some(el => el.textContent.trim() !== '');
            if (hasErrors) {
                this.isSaving = false;
            }
            // If no errors, keep spinner - redirect will navigate away
        } catch (e) {
            this.isSaving = false;
        }
    }
}">
    {{-- Full-screen loading overlay --}}
    <div x-show="isSaving" x-transition.opacity x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl p-8 flex flex-col items-center gap-4">
            <svg class="animate-spin h-12 w-12 text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-lg font-medium text-zinc-900 dark:text-zinc-100">Processing...</span>
            <span class="text-sm text-zinc-500 dark:text-zinc-400">Please wait while we save your data</span>
        </div>
    </div>

    <x-card>
        <flux:heading size="lg">{{ $dataPointId ? 'Edit Environmental Reading' : 'Submit Environmental Reading' }}</flux:heading>
        <flux:subheading>{{ $dataPointId ? 'Update GPS-tagged environmental data' : 'Capture GPS-tagged environmental data' }}</flux:subheading>

        @if($dataPointId)
            <div class="mt-2">
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    <strong>Data Point ID:</strong> #{{ $dataPointId }}
                </flux:text>
            </div>
        @endif

        {{-- QA Flags Warning Section --}}
        @if($dataPointId && !empty($qaFlags))
            <div class="mt-4 rounded-lg border-2 border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20 p-4">
                <div class="flex items-start gap-3">
                    <div class="text-2xl">🚩</div>
                    <div class="flex-1">
                        <div class="font-semibold text-red-900 dark:text-red-100 mb-2">
                            Quality Assurance Flags ({{ count($qaFlags) }})
                        </div>
                        <div class="text-sm text-red-800 dark:text-red-200 mb-3">
                            This data point has been flagged for quality issues. Even if approved, it will show as red on the map until flags are cleared.
                        </div>
                        <div class="space-y-2">
                            @foreach($qaFlags as $flag)
                                @php
                                    $flagInfo = $this->formatQaFlag($flag);
                                @endphp
                                <div class="flex items-start gap-2 text-sm bg-white dark:bg-zinc-800 rounded p-2 border border-red-200 dark:border-red-800">
                                    <div class="text-lg">{{ $flagInfo['icon'] }}</div>
                                    <div class="flex-1">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $flagInfo['name'] }}</div>
                                        <div class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">{{ $flagInfo['description'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($status === 'approved')
                            <div class="mt-3 text-xs text-red-700 dark:text-red-300 flex items-center gap-2">
                                <span class="text-base">ℹ️</span>
                                <span>Note: This data point is <strong>approved</strong> but will remain flagged (red) until the quality issues are resolved and flags are cleared.</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Low Accuracy Warning Section --}}
        @if($dataPointId && $accuracy && $accuracy > 50 && empty($qaFlags))
            <div class="mt-4 rounded-lg border-2 border-yellow-300 dark:border-yellow-700 bg-yellow-50 dark:bg-yellow-900/20 p-4">
                <div class="flex items-start gap-3">
                    <div class="text-2xl">🟡</div>
                    <div class="flex-1">
                        <div class="font-semibold text-yellow-900 dark:text-yellow-100 mb-2">
                            Low GPS Accuracy Warning
                        </div>
                        <div class="text-sm text-yellow-800 dark:text-yellow-200 mb-2">
                            This data point has low GPS accuracy (±{{ round($accuracy) }}m). The quality threshold is 50m.
                        </div>
                        <div class="text-xs text-yellow-700 dark:text-yellow-300 flex items-center gap-2">
                            <span class="text-base">ℹ️</span>
                            <span>Yellow markers on the map indicate poor GPS precision. The exact location may be uncertain.</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <form x-on:submit.prevent="submitForm" class="mt-6 space-y-6">

            {{-- Data Point Information Section --}}
            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                <div class="bg-zinc-50 dark:bg-zinc-800 px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Data Point Information</h3>
                </div>
                <div class="p-4 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Campaign Selection --}}
                        <flux:field>
                            <flux:label>Campaign <span x-data :class="$wire.campaignId ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">*</span></flux:label>
                            <select
                                wire:model.live="campaignId"
                                class="w-full h-10 rounded-lg border @error('campaignId') border-red-500 dark:border-red-400 @else border-zinc-300 dark:border-zinc-600 @enderror bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 px-3 py-2 text-sm"
                            >
                                <option value="">Select campaign...</option>
                                @foreach($this->campaigns as $campaign)
                                    <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                                @endforeach
                            </select>
                            <flux:error name="campaignId" />
                        </flux:field>

                        {{-- Metric Type Selection --}}
                        <flux:field>
                            <flux:label>Environmental Metric <span x-data :class="$wire.metricId ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">*</span></flux:label>
                            <select
                                wire:model.live="metricId"
                                class="w-full h-10 rounded-lg border @error('metricId') border-red-500 dark:border-red-400 @else border-zinc-300 dark:border-zinc-600 @enderror bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 px-3 py-2 text-sm"
                            >
                                <option value="">Select metric...</option>
                                @foreach($this->metrics as $metric)
                                    <option value="{{ $metric->id }}">
                                        {{ $metric->name }} ({{ $metric->unit }})
                                    </option>
                                @endforeach
                            </select>
                            <flux:error name="metricId" />
                        </flux:field>
                    </div>

                    {{-- Value Input --}}
                    <flux:field>
                        <flux:label>Measurement Value <span x-data :class="$wire.value ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">*</span></flux:label>
                        <flux:input
                            type="number"
                            step="0.01"
                            wire:model.live="value"
                            placeholder="Enter measurement value..."
                        />
                        <flux:error name="value" />
                    </flux:field>

                    @if($dataPointId)
                        <flux:field>
                            <flux:label>Status <span class="text-red-600 dark:text-red-400">*</span></flux:label>
                            <select
                                wire:model="status"
                                class="w-full h-10 rounded-lg border @error('status') border-red-500 dark:border-red-400 @else border-zinc-300 dark:border-zinc-600 @enderror bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 px-3 py-2 text-sm"
                            >
                                <option value="draft">Draft</option>
                                <option value="pending">Pending Review</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <flux:error name="status" />
                        </flux:field>
                    @endif
                </div>
            </div>

            {{-- Location Information Section --}}
            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                <div class="bg-zinc-50 dark:bg-zinc-800 px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Location Information</h3>
                </div>
                <div class="p-4 space-y-4">
                    {{-- GPS Location Capture --}}
                    <flux:field>
                        <flux:label>GPS Location <span x-data :class="($wire.latitude && $wire.longitude) || ($wire.manualLatitude && $wire.manualLongitude) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">*</span></flux:label>

                        <div class="flex gap-2 {{ $errors->has('location') ? 'p-2 -m-2 border-2 border-red-500 dark:border-red-400 rounded-lg' : '' }}">
                            <flux:button
                                type="button"
                                x-on:click="captureLocation()"
                                variant="outline"
                                x-bind:disabled="$wire.gpsStatus === 'requesting'"
                            >
                                <span x-show="$wire.gpsStatus !== 'requesting'">📍 Capture GPS</span>
                                <span x-show="$wire.gpsStatus === 'requesting'" x-cloak>🔄 Getting location...</span>
                            </flux:button>

                            @if($gpsStatus === 'success')
                                <flux:badge color="green">
                                    ✓ GPS Captured
                                </flux:badge>
                            @endif
                        </div>

                        {{-- Show GPS info under button --}}
                        @if($latitude && $longitude)
                            <div class="mt-2 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                <flux:text class="text-sm text-green-800 dark:text-green-200">
                                    📍 <strong>GPS Captured:</strong><br>
                                    Lat: {{ number_format($latitude, 6) }}, Long: {{ number_format($longitude, 6) }}<br>
                                    Accuracy: ±{{ round($accuracy) }}m
                                </flux:text>
                            </div>
                        @endif

                        @if($gpsError)
                    <flux:text class="mt-2 text-sm text-red-600 dark:text-red-400">
                        ⚠️ {{ $gpsError }}
                    </flux:text>
                @endif

                <flux:error name="location" />
            </flux:field>

            {{-- Manual Latitude/Longitude Input --}}
            <flux:separator text="OR Enter Coordinates Manually" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Latitude</flux:label>
                    <flux:input
                        type="number"
                        step="0.000001"
                        wire:model.live="manualLatitude"
                        placeholder="e.g., 55.676098"
                        min="-90"
                        max="90"
                        x-on:focus="onManualEntry()"
                    />
                    <flux:text class="text-sm">
                        WGS84 decimal degrees (-90 to +90)
                    </flux:text>
                    <flux:error name="manualLatitude" />
                </flux:field>

                <flux:field>
                    <flux:label>Longitude</flux:label>
                    <flux:input
                        type="number"
                        step="0.000001"
                        wire:model.live="manualLongitude"
                        placeholder="e.g., 12.568337"
                        min="-180"
                        max="180"
                        x-on:focus="onManualEntry()"
                    />
                    <flux:text class="text-sm">
                        WGS84 decimal degrees (-180 to +180)
                    </flux:text>
                    <flux:error name="manualLongitude" />
                </flux:field>
            </div>

            {{-- Show manual entry info --}}
            @if($manualLatitude && $manualLongitude)
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <flux:text class="text-sm text-blue-800 dark:text-blue-200">
                        📍 <strong>Manual Coordinates:</strong><br>
                        Lat: {{ number_format($manualLatitude, 6) }}, Long: {{ number_format($manualLongitude, 6) }}<br>
                        <span class="text-green-600 dark:text-green-400">Accuracy: Surveyed/exact location (0m)</span>
                    </flux:text>
                </div>
            @endif
        </div>
    </div>

    {{-- Collection Details Section --}}
    <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
        <div class="bg-zinc-50 dark:bg-zinc-800 px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Collection Details</h3>
        </div>
        <div class="p-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Device Model --}}
                <flux:field>
                    <flux:label>Device Model</flux:label>
                    <flux:input
                        type="text"
                        wire:model="deviceModel"
                        placeholder="e.g., iPhone 14, Samsung Galaxy S23"
                    />
                    <flux:error name="deviceModel" />
                </flux:field>

                {{-- Sensor Type --}}
                <flux:field>
                    <flux:label>Sensor Type</flux:label>
                    <select
                        wire:model="sensorType"
                        class="w-full h-10 rounded-lg border @error('sensorType') border-red-500 dark:border-red-400 @else border-zinc-300 dark:border-zinc-600 @enderror bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 px-3 py-2 text-sm"
                    >
                        <option value="">Select type...</option>
                        <option value="GPS">GPS</option>
                        <option value="Mobile Device">Mobile Device</option>
                        <option value="Professional Equipment">Professional Equipment</option>
                        <option value="Survey Equipment">Survey Equipment</option>
                        <option value="Manual Entry">Manual Entry</option>
                    </select>
                    <flux:error name="sensorType" />
                </flux:field>
            </div>

            {{-- Calibration Date --}}
            <flux:field>
                <flux:label>Last Calibration Date</flux:label>
                <flux:input
                    type="date"
                    wire:model="calibrationDate"
                    max="{{ date('Y-m-d') }}"
                />
                <flux:text class="text-sm">
                    When was your device/sensor last calibrated?
                    @if($calibrationDate)
                        @php
                            $daysSince = \Carbon\Carbon::parse($calibrationDate)->diffInDays(now());
                            $isOverdue = $daysSince > 90;
                            $colorClass = $isOverdue ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400';
                        @endphp
                        <span class="{{ $colorClass }}">
                            ({{ $daysSince }} days ago{{ $isOverdue ? ' ⚠️ Calibration overdue!' : '' }})
                        </span>
                    @endif
                </flux:text>
                <flux:error name="calibrationDate" />
            </flux:field>
        </div>
    </div>

    {{-- Additional Information Section --}}
    <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
        <div class="bg-zinc-50 dark:bg-zinc-800 px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Additional Information</h3>
        </div>
        <div class="p-4 space-y-4">
            {{-- Photo Upload --}}
            <flux:field>
                <flux:label>Photo (Optional)</flux:label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:input
                            type="file"
                            wire:model="photo"
                            accept="image/*"
                        />
                        <flux:text class="text-sm mt-2">
                            Max 5MB. JPG, PNG, WebP
                        </flux:text>
                        <flux:error name="photo" />
                    </div>
                    <div>
                        @if ($photo)
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                <flux:text class="text-sm text-green-800 dark:text-green-200 mb-2">
                                    ✓ New photo selected
                                </flux:text>
                                <div class="mt-2">
                                    @if (method_exists($photo, 'isPreviewable') && $photo->isPreviewable())
                                        <img
                                            src="{{ $photo->temporaryUrl() }}"
                                            alt="Photo preview"
                                            class="w-full h-32 object-cover rounded-lg border-2 border-green-300 dark:border-green-600 shadow-sm"
                                        >
                                    @else
                                        <flux:text class="text-sm text-green-800 dark:text-green-200">
                                            Preview not available.
                                        </flux:text>
                                    @endif
                                </div>
                            </div>
                        @elseif ($this->existingPhotoUrl)
                             <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800" wire:key="existing-photo-{{ $existingPhotoPath }}-{{ $existingPhotoVersion ?? '0' }}">
                                 <flux:text class="text-sm text-blue-800 dark:text-blue-200 mb-2">
                                     Current photo:
                                 </flux:text>
                                 <div class="mt-2">
                                     <img
                                        src="{{ $this->existingPhotoUrl }}"
                                         alt="Current photo"
                                         class="w-full h-32 object-cover rounded-lg border-2 border-blue-300 dark:border-blue-600 shadow-sm"
                                     >
                                 </div>
                             </div>
                        @endif
                    </div>
                </div>
            </flux:field>

            {{-- Notes --}}
            <flux:field>
                <flux:label>Notes (Optional)</flux:label>
                <flux:textarea
                    wire:model="notes"
                    rows="3"
                    placeholder="Add any observations or context..."
                />
                <flux:text class="text-sm">
                    {{ strlen($notes ?? '') }}/1000 characters
                </flux:text>
                <flux:error name="notes" />
            </flux:field>
        </div>
    </div>

    @if($dataPointId)
        {{-- Review Information Section --}}
        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
            <div class="bg-zinc-50 dark:bg-zinc-800 px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Review Information</h3>
            </div>
            <div class="p-4 space-y-4">
                <flux:field>
                    <flux:label>Review Notes</flux:label>
                    <flux:textarea
                        wire:model="reviewNotes"
                        rows="3"
                        placeholder="Add review notes..."
                    />
                    <flux:text class="text-sm">
                        Notes from reviewer (approve/reject decision)
                    </flux:text>
                    <flux:error name="reviewNotes" />
                </flux:field>
            </div>
        </div>

        {{-- Quality Assurance Section --}}
        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
            <div class="bg-zinc-50 dark:bg-zinc-800 px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Quality Assurance</h3>
                    <div class="flex gap-2">
                        @if(!empty($qaFlags))
                            <flux:button
                                wire:click="clearFlags"
                                variant="danger"
                                size="xs"
                                type="button"
                            >
                                🗑️ Clear All Flags
                            </flux:button>
                        @endif
                        <flux:button
                            wire:click="openFlagModal"
                            variant="outline"
                            size="xs"
                            type="button"
                        >
                            🚩 Add Flag
                        </flux:button>
                    </div>
                </div>
            </div>
            <div class="p-4 space-y-4">
                @if(!empty($qaFlags))
                    <div class="space-y-2">
                        <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Current QA Flags ({{ count($qaFlags) }}):
                        </flux:text>
                        @foreach($qaFlags as $index => $flag)
                            @php
                                $flagInfo = $this->formatQaFlag($flag);
                            @endphp
                            <div class="flex items-start gap-2 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                <div class="text-lg">{{ $flagInfo['icon'] }}</div>
                                <div class="flex-1">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100 text-sm">{{ $flagInfo['name'] }}</div>
                                    <div class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">{{ $flagInfo['description'] }}</div>
                                </div>
                                <flux:button
                                    wire:click="removeFlag({{ $index }})"
                                    variant="ghost"
                                    size="xs"
                                    type="button"
                                    class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
                                >
                                    ✕
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="text-4xl mb-2">✅</div>
                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                            No quality issues detected
                        </flux:text>
                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-500 mt-1">
                            Click "Add Flag" to manually flag this data point
                        </flux:text>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Remove duplicate fields below (now moved to sections above) --}}
    {{-- OLD STRUCTURE STARTS HERE - TO BE REMOVED --}}
    <div style="display:none;">
        {{-- These fields are now in proper sections above --}}
        {{-- Value Input - moved to Data Point Information section --}}
        {{-- Notes - moved to Additional Information section --}}
        {{-- Photo Upload - moved to Additional Information section --}}
        {{-- Device & Sensor Info - moved to Collection Details section --}}
        {{-- Calibration Date - moved to Collection Details section --}}
        {{-- Review Fields - moved to Review Information section --}}
    </div>
    {{-- OLD STRUCTURE ENDS HERE --}}

    {{-- Submit Button --}}
    <div class="flex gap-2">
        <flux:button
            type="submit"
            variant="primary"
            x-bind:disabled="isSaving"
        >
            <span x-show="!isSaving">
                {{ $dataPointId ? 'Update Reading' : 'Submit Reading' }}
            </span>
            <span x-show="isSaving" x-cloak class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
            </span>
        </flux:button>
        @if($inModal)
            <button
                type="button"
                @click="window.dispatchEvent(new CustomEvent('edit-modal-close'))"
                class="inline-flex items-center justify-center h-10 px-4 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors text-sm font-medium"
            >
                Cancel
            </button>
        @else
            <button
                type="button"
                onclick="window.history.back()"
                class="inline-flex items-center justify-center h-10 px-4 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors text-sm font-medium"
            >
                Cancel
            </button>
        @endif
    </div>
</form>

        {{-- Success Message --}}
        @if (session('success'))
            <x-card variant="success" class="mt-4">
                <flux:text class="text-green-800 dark:text-green-200">
                    ✓ {{ session('success') }}
                </flux:text>
            </x-card>
        @endif
    </x-card>

    {{-- Flag Modal --}}
    <flux:modal wire:model="showFlagModal" class="space-y-6">
        <div>
            <flux:heading size="lg">Flag Data Point for Review</flux:heading>
            <flux:subheading>Add a quality assurance flag to this data point</flux:subheading>
        </div>

        <flux:field>
            <flux:label>Flag Type</flux:label>
            <select
                wire:model="flagType"
                class="w-full h-10 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 px-3 py-2 text-sm"
            >
                <option value="">Select flag type...</option>
                <optgroup label="Automated QA Flags">
                    <option value="high_gps_error">📍 High GPS Error (>50m)</option>
                    <option value="statistical_outlier">📊 Statistical Outlier</option>
                    <option value="outside_zone">🗺️ Outside Survey Zone</option>
                    <option value="unexpected_range">⚠️ Unexpected Range</option>
                </optgroup>
                <optgroup label="Manual QA Flags">
                    <option value="outlier">📊 Statistical Outlier (Manual)</option>
                    <option value="suspicious_value">⚠️ Suspicious Value</option>
                    <option value="location_uncertainty">📍 Location Uncertainty</option>
                    <option value="calibration_overdue">⚙️ Calibration Issue</option>
                    <option value="manual_review">👁️ Manual Review Required</option>
                    <option value="data_quality">🔍 Data Quality Concern</option>
                </optgroup>
            </select>
            <flux:error name="flagType" />
        </flux:field>

        <flux:field>
            <flux:label>Reason</flux:label>
            <flux:textarea
                wire:model="flagReason"
                rows="4"
                placeholder="Describe why this data point needs review..."
            />
            <flux:text class="text-sm">
                {{ strlen($flagReason ?? '') }}/500 characters (minimum 5)
            </flux:text>
            <flux:error name="flagReason" />
        </flux:field>

        <div class="flex gap-2">
            <flux:button wire:click="addFlag" variant="primary">
                Add Flag
            </flux:button>
            <flux:button wire:click="$set('showFlagModal', false)" variant="outline">
                Cancel
            </flux:button>
        </div>
    </flux:modal>
</div>
