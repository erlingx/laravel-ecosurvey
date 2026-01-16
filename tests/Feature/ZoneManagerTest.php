<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\SurveyZone;
use App\Models\User;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

test('zone name and description can be updated', function () {
    $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);
    $zone = SurveyZone::factory()->create([
        'campaign_id' => $campaign->id,
        'name' => 'Original Zone Name',
        'description' => 'Original description',
    ]);

    Volt::test('campaigns.zone-manager', ['campaignId' => $campaign->id])
        ->call('startEditing', $zone->id)
        ->set('zoneName', 'Updated Zone Name')
        ->set('zoneDescription', 'Updated description')
        ->call('updateZone', $zone->id, 'Updated Zone Name', 'Updated description')
        ->assertHasNoErrors();

    $zone->refresh();

    expect($zone->name)->toBe('Updated Zone Name')
        ->and($zone->description)->toBe('Updated description');
});

test('zone name can be updated without description', function () {
    $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);
    $zone = SurveyZone::factory()->create([
        'campaign_id' => $campaign->id,
        'name' => 'Original Zone Name',
        'description' => null,
    ]);

    Volt::test('campaigns.zone-manager', ['campaignId' => $campaign->id])
        ->call('startEditing', $zone->id)
        ->set('zoneName', 'New Zone Name')
        ->set('zoneDescription', '')
        ->call('updateZone', $zone->id, 'New Zone Name', '')
        ->assertHasNoErrors();

    $zone->refresh();

    expect($zone->name)->toBe('New Zone Name')
        ->and($zone->description)->toBeEmpty();
});

test('editing mode is cancelled after successful update', function () {
    $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);
    $zone = SurveyZone::factory()->create([
        'campaign_id' => $campaign->id,
        'name' => 'Test Zone',
    ]);

    $component = Volt::test('campaigns.zone-manager', ['campaignId' => $campaign->id])
        ->call('startEditing', $zone->id)
        ->assertSet('editingZoneId', $zone->id)
        ->set('zoneName', 'Updated Name')
        ->set('zoneDescription', 'Updated description');

    // Call updateZone using the state variables
    $component->call('updateZone', $zone->id, $component->get('zoneName'), $component->get('zoneDescription'))
        ->assertSet('editingZoneId', null);
});

test('zonesUpdated event is dispatched after updating zone', function () {
    $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);
    $zone = SurveyZone::factory()->create([
        'campaign_id' => $campaign->id,
        'name' => 'Test Zone',
    ]);

    Volt::test('campaigns.zone-manager', ['campaignId' => $campaign->id])
        ->call('updateZone', $zone->id, 'Updated Name', 'Updated description')
        ->assertDispatched('zonesUpdated');
});
