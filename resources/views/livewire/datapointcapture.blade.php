<?php

use App\Models\Campaign;
use App\Models\DataPoint;
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
    'gpsStatus' => 'idle',
    'gpsError' => null,
]);

mount(function () {
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


$save = function () {
    $validated = $this->validate([
        'campaignId' => 'required|exists:campaigns,id',
        'metricId' => 'required|exists:environmental_metrics,id',
        'value' => 'required|numeric',
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'notes' => 'nullable|string|max:1000',
    ]);

    DataPoint::query()->create([
        'campaign_id' => $validated['campaignId'],
        'environmental_metric_id' => $validated['metricId'],
        'user_id' => auth()->id(),
        'value' => $validated['value'],
        'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$validated['longitude']}, {$validated['latitude']}), 4326)"),
        'accuracy' => $this->accuracy,
        'notes' => $validated['notes'],
        'collected_at' => now(),
    ]);

    session()->flash('success', 'Data point submitted successfully!');
    $this->reset(['value', 'notes', 'latitude', 'longitude', 'accuracy', 'gpsStatus']);
};

?>

<div class="max-w-2xl mx-auto" x-data="{
    captureLocation() {
        if (!navigator.geolocation) {
            @this.set('gpsError', 'Geolocation not supported');
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
                        errorMessage = 'Location permission denied';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = 'Location unavailable';
                        break;
                    case error.TIMEOUT:
                        errorMessage = 'Location request timed out';
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
}">
    <x-card>
        <flux:heading size="lg">Capture Data Point</flux:heading>
        <flux:subheading>Record GPS-tagged environmental measurement</flux:subheading>

        <form wire:submit="save" class="mt-6 space-y-6">
            <flux:field>
                <flux:label>Campaign</flux:label>
                <flux:select wire:model.live="campaignId" placeholder="Select campaign...">
                    @foreach($this->campaigns as $campaign)
                        <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="campaignId" />
            </flux:field>

            <flux:field>
                <flux:label>Environmental Metric</flux:label>
                <flux:select wire:model.live="metricId" placeholder="Select metric...">
                    @foreach($this->metrics as $metric)
                        <option value="{{ $metric->id }}">
                            {{ $metric->name }} ({{ $metric->unit }})
                        </option>
                    @endforeach
                </flux:select>
                <flux:error name="metricId" />
            </flux:field>

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

                @if($latitude && $longitude)
                    <flux:text class="mt-2 text-sm">
                        📍 {{ number_format($latitude, 6) }}, {{ number_format($longitude, 6) }}
                        @if($accuracy)
                            (±{{ round($accuracy) }}m)
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

            <flux:field>
                <flux:label>Measurement Value</flux:label>
                <flux:input
                    type="number"
                    step="0.01"
                    wire:model="value"
                    placeholder="Enter value..."
                />
                <flux:error name="value" />
            </flux:field>

            <flux:field>
                <flux:label>Notes (Optional)</flux:label>
                <flux:textarea
                    wire:model="notes"
                    rows="3"
                    placeholder="Additional observations..."
                />
                <flux:text class="text-sm">
                    {{ strlen($notes ?? '') }}/1000 characters
                </flux:text>
                <flux:error name="notes" />
            </flux:field>

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">
                    <span wire:loading.remove wire:target="save">Submit Data Point</span>
                    <span wire:loading wire:target="save">Submitting...</span>
                </flux:button>
            </div>
        </form>
    </x-card>

    @if (session('success'))
        <x-card variant="success" class="mt-4">
            <flux:text class="text-green-800 dark:text-green-200">
                ✓ {{ session('success') }}
            </flux:text>
        </x-card>
    @endif
</div>

