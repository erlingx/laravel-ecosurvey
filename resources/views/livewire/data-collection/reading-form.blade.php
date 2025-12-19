<?php

use App\Models\Campaign;
use App\Models\EnvironmentalMetric;

use function Livewire\Volt\computed;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;

state([
    'campaignId' => null,
    'metricId' => null,
    'value' => null,
    'notes' => '',
    'latitude' => null,
    'longitude' => null,
    'accuracy' => null,
    'gpsStatus' => 'idle', // idle, requesting, success, error
    'gpsError' => null,
]);

mount(function () {
    // Auto-select first campaign if only one exists
    $campaigns = Campaign::query()->get();
    if ($campaigns->count() === 1) {
        $this->campaignId = $campaigns->first()->id;
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

$captureGPS = function () {
    $this->gpsStatus = 'requesting';
    $this->gpsError = null;
    // GPS will be captured via JavaScript
};

$save = function () {
    $validated = $this->validate([
        'campaignId' => 'required|exists:campaigns,id',
        'metricId' => 'required|exists:environmental_metrics,id',
        'value' => 'required|numeric',
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'notes' => 'nullable|string|max:1000',
    ]);

    // TODO: Create DataPoint record
    // For now, just show success message

    session()->flash('success', 'Reading submitted successfully!');

    // Reset form
    $this->reset(['value', 'notes', 'latitude', 'longitude', 'accuracy', 'gpsStatus']);
};

?>

<div class="max-w-2xl mx-auto">
    <flux:card>
        <flux:heading size="lg">Submit Environmental Reading</flux:heading>
        <flux:subheading>Capture GPS-tagged environmental data</flux:subheading>

        <form wire:submit="save" class="mt-6 space-y-6">
            {{-- Campaign Selection --}}
            <flux:field>
                <flux:label>Campaign</flux:label>
                <flux:select wire:model="campaignId" placeholder="Select campaign...">
                    @foreach($this->campaigns as $campaign)
                        <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="campaignId" />
            </flux:field>

            {{-- Metric Type Selection --}}
            <flux:field>
                <flux:label>Metric Type</flux:label>
                <flux:select wire:model="metricId" placeholder="Select metric...">
                    @foreach($this->metrics as $metric)
                        <option value="{{ $metric->id }}">
                            {{ $metric->name }} ({{ $metric->unit }})
                        </option>
                    @endforeach
                </flux:select>
                <flux:error name="metricId" />
            </flux:field>

            {{-- GPS Location Capture --}}
            <flux:field>
                <flux:label>GPS Location</flux:label>

                <div class="flex gap-2">
                    <flux:button
                        type="button"
                        wire:click="captureGPS"
                        x-on:click="captureLocation()"
                        variant="secondary"
                        {{ $gpsStatus === 'requesting' ? 'disabled' : '' }}
                    >
                        <span wire:loading.remove wire:target="captureGPS">
                            📍 Capture GPS
                        </span>
                        <span wire:loading wire:target="captureGPS">
                            🔄 Getting location...
                        </span>
                    </flux:button>

                    @if($gpsStatus === 'success')
                        <flux:badge color="green">
                            ✓ GPS Captured
                        </flux:badge>
                    @endif
                </div>

                @if($latitude && $longitude)
                    <flux:text class="mt-2 text-sm">
                        📍 {{ number_format($latitude, 6) }}, {{ number_format($longitude, 6) }}
                        @if($accuracy)
                            (±{{ round($accuracy) }}m accuracy)
                        @endif
                    </flux:text>
                @endif

                @if($gpsError)
                    <flux:text class="mt-2 text-sm text-red-600 dark:text-red-400">
                        ⚠️ {{ $gpsError }}
                    </flux:text>
                @endif

                <flux:error name="latitude" />
                <flux:error name="longitude" />
            </flux:field>

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

            {{-- Submit Button --}}
            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">
                    Submit Reading
                </flux:button>
            </div>
        </form>
    </flux:card>

    {{-- Success Message --}}
    @if (session('success'))
        <flux:card class="mt-4 bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800">
            <flux:text class="text-green-800 dark:text-green-200">
                ✓ {{ session('success') }}
            </flux:text>
        </flux:card>
    @endif

    {{-- JavaScript for GPS Capture --}}
    <script>
        function captureLocation() {
            if (!navigator.geolocation) {
                @this.set('gpsError', 'Geolocation is not supported by your browser');
                @this.set('gpsStatus', 'error');
                return;
            }

            @this.set('gpsStatus', 'requesting');
            @this.set('gpsError', null);

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    @this.set('latitude', position.coords.latitude);
                    @this.set('longitude', position.coords.longitude);
                    @this.set('accuracy', position.coords.accuracy);
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
        }
    </script>
</div>

