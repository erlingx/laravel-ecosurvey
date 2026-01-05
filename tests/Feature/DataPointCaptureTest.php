<?php

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\EnvironmentalMetric;
use App\Models\User;
use Livewire\Livewire;

test('data point capture page is accessible for authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/data-points/capture');

    $response->assertSuccessful();
    $response->assertSeeLivewire('datapointcapture');
});

test('data point capture page requires authentication', function () {
    $response = $this->get('/data-points/capture');

    $response->assertRedirect('/login');
});

test('can submit data point with valid data', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['status' => 'active']);
    $metric = EnvironmentalMetric::factory()->create(['is_active' => true]);

    Livewire::actingAs($user)
        ->test('datapointcapture')
        ->set('campaignId', $campaign->id)
        ->set('metricId', $metric->id)
        ->set('value', 23.5)
        ->set('latitude', 40.7128)
        ->set('longitude', -74.0060)
        ->set('accuracy', 10.5)
        ->set('notes', 'Test observation')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('data_points', [
        'campaign_id' => $campaign->id,
        'environmental_metric_id' => $metric->id,
        'user_id' => $user->id,
        'value' => 23.5,
        'accuracy' => 10.5,
        'notes' => 'Test observation',
    ]);
});

test('validates required fields', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('datapointcapture')
        ->call('save')
        ->assertHasErrors(['campaignId', 'metricId', 'value', 'latitude', 'longitude']);
});

test('validates latitude range', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['status' => 'active']);
    $metric = EnvironmentalMetric::factory()->create(['is_active' => true]);

    Livewire::actingAs($user)
        ->test('datapointcapture')
        ->set('campaignId', $campaign->id)
        ->set('metricId', $metric->id)
        ->set('value', 23.5)
        ->set('latitude', 95.0)
        ->set('longitude', -74.0060)
        ->call('save')
        ->assertHasErrors(['latitude']);
});

test('validates longitude range', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['status' => 'active']);
    $metric = EnvironmentalMetric::factory()->create(['is_active' => true]);

    Livewire::actingAs($user)
        ->test('datapointcapture')
        ->set('campaignId', $campaign->id)
        ->set('metricId', $metric->id)
        ->set('value', 23.5)
        ->set('latitude', 40.7128)
        ->set('longitude', 200.0)
        ->call('save')
        ->assertHasErrors(['longitude']);
});

test('validates notes length', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['status' => 'active']);
    $metric = EnvironmentalMetric::factory()->create(['is_active' => true]);

    Livewire::actingAs($user)
        ->test('datapointcapture')
        ->set('campaignId', $campaign->id)
        ->set('metricId', $metric->id)
        ->set('value', 23.5)
        ->set('latitude', 40.7128)
        ->set('longitude', -74.0060)
        ->set('notes', str_repeat('a', 1001))
        ->call('save')
        ->assertHasErrors(['notes']);
});

test('auto-selects campaign when only one exists', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['status' => 'active']);

    $component = Livewire::actingAs($user)
        ->test('datapointcapture');

    expect($component->get('campaignId'))->toBe($campaign->id);
});

test('resets form after successful submission', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['status' => 'active']);
    $metric = EnvironmentalMetric::factory()->create(['is_active' => true]);

    Livewire::actingAs($user)
        ->test('datapointcapture')
        ->set('campaignId', $campaign->id)
        ->set('metricId', $metric->id)
        ->set('value', 23.5)
        ->set('latitude', 40.7128)
        ->set('longitude', -74.0060)
        ->set('notes', 'Test note')
        ->call('save')
        ->assertSet('value', null)
        ->assertSet('notes', '')
        ->assertSet('latitude', null)
        ->assertSet('longitude', null);
});

