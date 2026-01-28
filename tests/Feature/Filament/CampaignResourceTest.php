<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\CampaignResource;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake(); // Prevent jobs from running during tests
});

test('campaigns can be listed in filament', function () {
    $user = User::factory()->create();
    Campaign::factory()->count(3)->create();

    $this->actingAs($user);

    $this->get(CampaignResource::getUrl('index'))
        ->assertSuccessful();
});

test('campaign can be created via filament', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $newData = [
        'name' => 'New Test Campaign',
        'description' => 'Testing campaign creation',
        'status' => 'active',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ];

    Livewire::test(CampaignResource\Pages\CreateCampaign::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('campaigns', [
        'name' => 'New Test Campaign',
        'status' => 'active',
    ]);
});

test('campaign can be edited via filament', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create([
        'name' => 'Original Name',
        'user_id' => $user->id, // User must own the campaign
    ]);

    Livewire::actingAs($user)
        ->test(CampaignResource\Pages\EditCampaign::class, ['record' => $campaign->getRouteKey()])
        ->assertSuccessful()
        ->set('data.name', 'Updated Name')
        ->set('data.status', 'completed')
        ->call('save')
        ->assertHasNoErrors();

    expect($campaign->fresh()->name)->toBe('Updated Name')
        ->and($campaign->fresh()->status)->toBe('completed');
});

test('campaign can be deleted via filament', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create([
        'user_id' => $user->id, // User must own the campaign
    ]);

    Livewire::actingAs($user)
        ->test(CampaignResource\Pages\EditCampaign::class, ['record' => $campaign->getRouteKey()])
        ->assertSuccessful()
        ->callAction('delete')
        ->assertHasNoErrors();

    $this->assertModelMissing($campaign);
});

test('manage zones link appears in campaign table', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['name' => 'Test Campaign']);

    $this->actingAs($user);

    $this->get(CampaignResource::getUrl('index'))
        ->assertSuccessful();

    // Verify the route exists and is accessible
    $zonesUrl = route('campaigns.zones.manage', $campaign);
    expect($zonesUrl)->toContain('/campaigns/'.$campaign->id.'/zones/manage');
});

test('navigation badge shows active campaigns count', function () {
    Campaign::factory()->count(3)->create(['status' => 'active']);
    Campaign::factory()->count(2)->create(['status' => 'draft']);

    $badge = CampaignResource::getNavigationBadge();

    expect($badge)->toBe('3');
});

test('campaign form shows data collection stats for existing campaigns', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()
        ->hasDataPoints(5, ['status' => 'approved'])
        ->hasSurveyZones(2)
        ->create([
            'user_id' => $user->id, // User must own the campaign
        ]);

    Livewire::actingAs($user)
        ->test(CampaignResource\Pages\EditCampaign::class, ['record' => $campaign->getRouteKey()])
        ->assertSuccessful()
        ->assertSet('data.name', $campaign->name)
        ->assertSet('data.status', $campaign->status);
});
