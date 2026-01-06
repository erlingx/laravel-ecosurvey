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
    'campaignId' => null,
    'metricId' => null,
    'value' => null,
    'notes' => '',
    'latitude' => null,
    'longitude' => null,
    'accuracy' => null,
    'gpsStatus' => 'idle', // idle, requesting, success, error
    'gpsError' => null,
    'photo' => null,
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

$save = function () {
    $validated = $this->validate([
        'campaignId' => 'required|exists:campaigns,id',
        'metricId' => 'required|exists:environmental_metrics,id',
        'value' => 'required|numeric',
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
        'notes' => 'nullable|string|max:1000',
        'photo' => 'nullable|image|max:5120', // 5MB max
    ]);

    // Store photo if uploaded
    $photoPath = null;
    if ($this->photo) {
        $photoPath = $this->photo->store('data-points', 'public');
    }

    // Create DataPoint with PostGIS location
    DataPoint::query()->create([
        'campaign_id' => $validated['campaignId'],
        'environmental_metric_id' => $validated['metricId'],
        'user_id' => auth()->id(),
        'value' => $validated['value'],
        'location' => \DB::raw("ST_SetSRID(ST_MakePoint({$validated['longitude']}, {$validated['latitude']}), 4326)"),
        'accuracy' => $this->accuracy,
        'notes' => $validated['notes'],
        'photo_path' => $photoPath,
        'collected_at' => now(),
    ]);

    session()->flash('success', 'Reading submitted successfully!');

    // Reset form
    $this->reset(['value', 'notes', 'latitude', 'longitude', 'accuracy', 'gpsStatus', 'photo']);
};

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
}">
    <x-card>
        <flux:heading size="lg">Submit Environmental Reading</flux:heading>
        <flux:subheading>Capture GPS-tagged environmental data</flux:subheading>

        <form wire:submit="save" class="mt-6 space-y-6">
            {{-- Campaign Selection --}}
            <flux:field>
                <flux:label>Campaign</flux:label>
                <select
                    wire:model.live="campaignId"
                    class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400"
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
                    class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400"
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
                    <div class="mt-2">
                        <flux:text class="text-sm text-green-600 dark:text-green-400">
                            ✓ Photo selected: {{ $photo->getClientOriginalName() }}
                        </flux:text>
                    </div>
                @endif
            </flux:field>

            {{-- Submit Button --}}
            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">
                    <span wire:loading.remove wire:target="save">Submit Reading</span>
                    <span wire:loading wire:target="save">Submitting...</span>
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

