<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\SurveyZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('zone manager loads for authenticated user', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    $this->actingAs($user);

    Volt::test('campaigns.zone-manager', ['campaignId' => $campaign->id])
        ->assertOk()
        ->assertSee('Manage Survey Zones')
        ->assertSee($campaign->name);
});

test('zone manager displays existing zones', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    $zone = SurveyZone::factory()->for($campaign)->create([
        'name' => 'Test Zone Alpha',
        'description' => 'Primary study area',
    ]);

    $this->actingAs($user);

    Volt::test('campaigns.zone-manager', ['campaignId' => $campaign->id])
        ->assertSee('Test Zone Alpha')
        ->assertSee('Primary study area');
});

test('zone manager can save new zone', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    $this->actingAs($user);

    $geoJson = [
        'type' => 'Feature',
        'geometry' => [
            'type' => 'Polygon',
            'coordinates' => [
                [
                    [12.0, 55.0],
                    [12.1, 55.0],
                    [12.1, 55.1],
                    [12.0, 55.1],
                    [12.0, 55.0],
                ],
            ],
        ],
    ];

    Volt::test('campaigns.zone-manager', ['campaignId' => $campaign->id])
        ->call('saveZone', $geoJson, 'New Test Zone', 'Created via test')
        ->assertHasNoErrors()
        ->assertDispatched('zonesUpdated');

    expect(SurveyZone::where('campaign_id', $campaign->id)->count())->toBe(1);

    $zone = SurveyZone::where('campaign_id', $campaign->id)->first();
    expect($zone->name)->toBe('New Test Zone')
        ->and($zone->description)->toBe('Created via test')
        ->and($zone->area_km2)->toBeGreaterThan(0);
});

test('zone manager can update zone details', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $zone = SurveyZone::factory()->for($campaign)->create([
        'name' => 'Original Name',
        'description' => 'Original Description',
    ]);

    $this->actingAs($user);

    Volt::test('campaigns.zone-manager', ['campaignId' => $campaign->id])
        ->call('startEditing', $zone->id)
        ->set('zoneName', 'Updated Name')
        ->set('zoneDescription', 'Updated Description')
        ->call('updateZone', $zone->id, 'Updated Name', 'Updated Description');

    $zone->refresh();
    expect($zone->name)->toBe('Updated Name')
        ->and($zone->description)->toBe('Updated Description');
});

test('zone manager can delete zone', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $zone = SurveyZone::factory()->for($campaign)->create(['name' => 'Zone To Delete']);

    $this->actingAs($user);

    Volt::test('campaigns.zone-manager', ['campaignId' => $campaign->id])
        ->call('deleteZone', $zone->id);

    expect(SurveyZone::find($zone->id))->toBeNull();
});

test('zone manager shows empty state when no zones exist', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    $this->actingAs($user);

    Volt::test('campaigns.zone-manager', ['campaignId' => $campaign->id])
        ->assertSee('No survey zones yet')
        ->assertSee('Draw a polygon on the map to create one');
});
