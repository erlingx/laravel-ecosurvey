<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('campaigns index page displays user campaigns', function () {
    $user = User::factory()->create();
    Campaign::factory()->count(3)->create(['user_id' => $user->id]);
    Campaign::factory()->count(2)->create(); // Other user's campaigns

    $this->actingAs($user);

    $this->get(route('campaigns.index'))
        ->assertSuccessful()
        ->assertSee('My Campaigns')
        ->assertSeeLivewire('campaigns.my-campaigns');
});

test('dashboard shows campaign statistics', function () {
    $user = User::factory()->create();
    Campaign::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'active']);
    Campaign::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Total Campaigns')
        ->assertSee('3') // Total campaigns
        ->assertSee('2 active'); // Active campaigns
});

test('sidebar contains campaign navigation links', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('My Campaigns');
});

test('empty state shows when user has no campaigns', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('campaigns.index'))
        ->assertSuccessful()
        ->assertSee('No campaigns yet')
        ->assertSee('Create your first campaign');
});

test('dashboard shows quick action links', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Submit Reading')
        ->assertSee('View Campaigns')
        ->assertSee('View Satellite Data');
});

test('sidebar shows administration section', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Overview')
        ->assertSee('Dashboard')
        ->assertSee('Campaigns')
        ->assertSee('My Campaigns')
        ->assertSee('Manage Campaigns')
        ->assertSee('Data Collection')
        ->assertSee('Satellite & Analysis')
        ->assertSee('Administration')
        ->assertSee('Manage Users');
});
