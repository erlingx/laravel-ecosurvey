<?php

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\EnvironmentalMetric;
use Livewire\WithFileUploads;

use function Livewire\Volt\computed;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;
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
]);

mount(function ($dataPoint = null) {
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

$save = function () {

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
    ]);

    // Validate coordinates: either GPS OR manual entry required (skip if editing existing point)
    if (! $this->dataPointId) {
        // For new submissions, require coordinates
        if (! $this->latitude && ! $this->manualLatitude) {
            $this->addError('location', 'Either capture GPS or enter coordinates manually');

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

        return;
    }
    if (! is_numeric($finalLongitude) || $finalLongitude < -180 || $finalLongitude > 180) {
        $this->addError('longitude', 'Longitude must be between -180 and 180');

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

    session()->flash('success', $message);

    // For new submissions, redirect to map. For edits, stay on page to see success message
    if (! $this->dataPointId) {
        return redirect()->route('maps.survey');
    } else {
        $dataPoint->refresh();

        $this->existingPhotoPath = $dataPoint->photo_path;
        $this->existingPhotoVersion = now()->timestamp;
        $this->photo = null;
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

?>

<div class="max-w-2xl mx-auto" x-data="{
    captureLocation() {
        if (!navigator.geolocation) {
            @this.set('gpsError', 'Geolocation is not supported by your browser');
            @this.set('gpsStatus', 'error');
            return;
        }

        @this.set('gpsStatus', 'requesting');
        @this.set('gpsError', null);

        navigator.geolocation.getCurrentPosition(
            (position) => {
                // Round to 6 decimal places (~0.11m precision, prevents validation issues)
                const lat = parseFloat(position.coords.latitude.toFixed(6));
                const lng = parseFloat(position.coords.longitude.toFixed(6));
                const acc = Math.round(position.coords.accuracy);

                // Clear manual entry when GPS is captured
                @this.set('manualLatitude', null);
                @this.set('manualLongitude', null);

                // Set GPS coordinates
                @this.set('latitude', lat);
                @this.set('longitude', lng);
                @this.set('accuracy', acc);
                @this.set('gpsStatus', 'success');
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
                @this.set('gpsError', errorMessage);
                @this.set('gpsStatus', 'error');
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
        @this.set('latitude', null);
        @this.set('longitude', null);
        @this.set('accuracy', null);
        @this.set('gpsStatus', 'idle');
    }
}">
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

        <form wire:submit="save" class="mt-6 space-y-6">
            {{-- Campaign Selection --}}
            <flux:field>
                <flux:label>Campaign</flux:label>
                <select
                    wire:model.live="campaignId"
                    class="w-full h-10 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 px-3 py-2 text-sm"
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
                <flux:label>Metric Type</flux:label>
                <select
                    wire:model.live="metricId"
                    class="w-full h-10 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 px-3 py-2 text-sm"
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

            {{-- GPS Location Capture --}}
            <flux:field>
                <flux:label>GPS Location</flux:label>

                <div class="flex gap-2">
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
                        Decimal degrees (-90 to 90)
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
                        Decimal degrees (-180 to 180)
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

            {{-- Value Input --}}
            <flux:field>
                <flux:label>Reading Value</flux:label>
                <flux:input
                    type="number"
                    step="0.01"
                    wire:model="value"
                    placeholder="Enter measurement value..."
                />
                <flux:error name="value" />
            </flux:field>

            {{-- Notes (Optional) --}}
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

            {{-- Photo Upload (Optional) --}}
            <flux:field>
                <flux:label>Photo (Optional)</flux:label>
                <flux:input
                    type="file"
                    wire:model="photo"
                    accept="image/*"
                />
                <flux:text class="text-sm">
                    Maximum file size: 5MB. Accepted formats: JPG, PNG, WebP
                </flux:text>
                <flux:error name="photo" />

                @if ($photo)
                    <div class="mt-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <flux:text class="text-sm text-green-800 dark:text-green-200 mb-2">
                            ✓ New photo selected: {{ $photo->getClientOriginalName() }}
                        </flux:text>
                        <div class="mt-2">
                            @if (method_exists($photo, 'isPreviewable') && $photo->isPreviewable())
                                <img
                                    src="{{ $photo->temporaryUrl() }}"
                                    alt="Photo preview"
                                    class="w-32 h-32 object-cover rounded-lg border-2 border-green-300 dark:border-green-600 shadow-sm"
                                >
                            @else
                                <flux:text class="text-sm text-green-800 dark:text-green-200">
                                    Preview not available.
                                </flux:text>
                            @endif
                        </div>
                    </div>
                @elseif ($this->existingPhotoUrl)
                     <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800" wire:key="existing-photo-{{ $existingPhotoPath }}-{{ $existingPhotoVersion ?? '0' }}">
                         <flux:text class="text-sm text-blue-800 dark:text-blue-200 mb-2">
                             Current photo:
                         </flux:text>
                         <div class="mt-2">
                             <img
                                src="{{ $this->existingPhotoUrl }}"
                                 alt="Current photo"
                                 class="w-32 h-32 object-cover rounded-lg border-2 border-blue-300 dark:border-blue-600 shadow-sm"
                             >
                         </div>
                     </div>
                @endif
            </flux:field>

            {{-- Device & Sensor Information (Optional) --}}
            <flux:separator text="Device Information (Optional)" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Device Model --}}
                <flux:field>
                    <flux:label>Device/Sensor Model</flux:label>
                    <flux:input
                        type="text"
                        wire:model="deviceModel"
                        placeholder="e.g., iPhone 14, AirQuality Pro 2000"
                    />
                    <flux:text class="text-sm">
                        What device or sensor are you using?
                    </flux:text>
                    <flux:error name="deviceModel" />
                </flux:field>

                {{-- Sensor Type --}}
                <flux:field>
                    <flux:label>Sensor Type</flux:label>
                    <select
                        wire:model="sensorType"
                        class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400"
                    >
                        <option value="">Select type...</option>
                        <option value="GPS">GPS</option>
                        <option value="Mobile Device">Mobile Device</option>
                        <option value="Professional Equipment">Professional Equipment</option>
                        <option value="Survey Equipment">Survey Equipment</option>
                        <option value="Manual Entry">Manual Entry</option>
                    </select>
                    <flux:text class="text-sm">
                        How was this measurement taken?
                    </flux:text>
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
                    When was your device/sensor last calibrated? (Leave empty if not applicable)
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

            {{-- Submit Button --}}
            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">
                    <span wire:loading.remove wire:target="save">{{ $dataPointId ? 'Update Reading' : 'Submit Reading' }}</span>
                    <span wire:loading wire:target="save">{{ $dataPointId ? 'Updating...' : 'Submitting...' }}</span>
                </flux:button>
            </div>
        </form>
    </x-card>

    {{-- Success Message --}}
    @if (session('success'))
        <x-card variant="success" class="mt-4">
            <flux:text class="text-green-800 dark:text-green-200">
                ✓ {{ session('success') }}
            </flux:text>
        </x-card>
    @endif
</div>

