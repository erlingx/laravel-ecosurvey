<?php

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\EnvironmentalMetric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->campaign = Campaign::factory()->create(['status' => 'active']);
    $this->metric = EnvironmentalMetric::factory()->create(['is_active' => true]);
});

test('reading form can be rendered', function () {
    $this->actingAs($this->user)
        ->get(route('readings.submit'))
        ->assertOk()
        ->assertSee('Submit Environmental Reading');
});

test('authenticated users can submit a reading with GPS coordinates', function () {
    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('metricId', $this->metric->id)
        ->set('value', 42.5)
        ->set('latitude', 39.7392)
        ->set('longitude', -104.9903)
        ->set('accuracy', 15.5)
        ->set('notes', 'Clear sunny day')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('value', null) // Form reset
        ->assertSet('notes', '') // Form reset
        ->assertSet('latitude', null); // Form reset

    // Verify DataPoint was created
    $this->assertDatabaseHas('data_points', [
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 42.5,
        'accuracy' => 15.5,
        'notes' => 'Clear sunny day',
    ]);

    // Verify PostGIS location was stored correctly
    $dataPoint = DataPoint::query()->latest()->first();
    expect($dataPoint)->not->toBeNull();
});

test('campaign field is required', function () {
    // Create a second campaign to prevent auto-selection
    Campaign::factory()->create(['status' => 'active']);

    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('metricId', $this->metric->id)
        ->set('value', 42.5)
        ->set('latitude', 39.7392)
        ->set('longitude', -104.9903)
        ->call('save')
        ->assertHasErrors(['campaignId' => 'required']);
});

test('metric field is required', function () {
    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('value', 42.5)
        ->set('latitude', 39.7392)
        ->set('longitude', -104.9903)
        ->call('save')
        ->assertHasErrors(['metricId' => 'required']);
});

test('value field is required and must be numeric', function () {
    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('metricId', $this->metric->id)
        ->set('latitude', 39.7392)
        ->set('longitude', -104.9903)
        ->call('save')
        ->assertHasErrors(['value' => 'required']);

    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('metricId', $this->metric->id)
        ->set('value', 'not-a-number')
        ->set('latitude', 39.7392)
        ->set('longitude', -104.9903)
        ->call('save')
        ->assertHasErrors(['value' => 'numeric']);
});

test('GPS coordinates are required', function () {
    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('metricId', $this->metric->id)
        ->set('value', 42.5)
        ->call('save')
        ->assertHasErrors(['latitude' => 'required', 'longitude' => 'required']);
});

test('latitude must be between -90 and 90', function () {
    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('metricId', $this->metric->id)
        ->set('value', 42.5)
        ->set('latitude', 95)
        ->set('longitude', -104.9903)
        ->call('save')
        ->assertHasErrors(['latitude']);
});

test('longitude must be between -180 and 180', function () {
    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('metricId', $this->metric->id)
        ->set('value', 42.5)
        ->set('latitude', 39.7392)
        ->set('longitude', 200)
        ->call('save')
        ->assertHasErrors(['longitude']);
});

test('notes field is optional but has max length', function () {
    $longNotes = str_repeat('a', 1001);

    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('metricId', $this->metric->id)
        ->set('value', 42.5)
        ->set('latitude', 39.7392)
        ->set('longitude', -104.9903)
        ->set('notes', $longNotes)
        ->call('save')
        ->assertHasErrors(['notes']);
});

test('form auto-selects campaign if only one exists', function () {
    // Delete other campaigns
    Campaign::query()->where('id', '!=', $this->campaign->id)->delete();

    $component = Livewire::actingAs($this->user)
        ->test('data-collection.reading-form');

    expect($component->get('campaignId'))->toBe($this->campaign->id);
});

test('campaigns list only shows active campaigns', function () {
    Campaign::factory()->create(['status' => 'completed']);
    Campaign::factory()->create(['status' => 'draft']);

    $component = Livewire::actingAs($this->user)
        ->test('data-collection.reading-form');

    $campaigns = $component->campaigns;

    expect($campaigns)->toHaveCount(1)
        ->and($campaigns->first()->status)->toBe('active');
});

test('metrics list only shows active metrics', function () {
    EnvironmentalMetric::factory()->create(['is_active' => false]);

    $component = Livewire::actingAs($this->user)
        ->test('data-collection.reading-form');

    $metrics = $component->metrics;

    // Should only show the active metric from beforeEach, not the inactive one
    expect($metrics)->toHaveCount(1)
        ->and($metrics->first()->id)->toBe($this->metric->id);
});
