<?php

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Queue::fake();
    $this->admin = User::factory()->create();
});

test('quality dashboard page displays with user-friendly guidelines', function () {
    actingAs($this->admin);

    $page = visit('/admin/quality-dashboard');

    $page->assertSee('Quality Assurance Dashboard')
        ->assertSee('Monitor data quality metrics, user contributions, and API usage in real-time.')
        ->assertSee('Quality Guidelines')
        ->assertSee('GPS Accuracy')
        ->assertSee('Reviewing Data')
        ->assertSee('Data Points')
        ->assertSee('QA Flags')
        ->assertSee('Bulk Actions')
        ->assertNoJavascriptErrors();
});

test('quality dashboard displays qa statistics widget', function () {
    actingAs($this->admin);

    Campaign::factory()->count(3)->create(['status' => 'active']);
    DataPoint::factory()->count(5)->create(['status' => 'pending']);
    DataPoint::factory()->count(8)->create(['status' => 'approved']);
    DataPoint::factory()->count(2)->create(['status' => 'rejected']);

    $page = visit('/admin/quality-dashboard');

    $page->assertSee('Pending Review')
        ->assertSee('5')
        ->assertSee('Approved')
        ->assertSee('8')
        ->assertSee('Rejected')
        ->assertSee('2')
        ->assertSee('Active Campaigns')
        ->assertSee('3')
        ->assertNoJavascriptErrors();
});

test('quality dashboard displays user contribution leaderboard widget', function () {
    actingAs($this->admin);

    $user1 = User::factory()->create(['name' => 'Top Contributor']);
    $user2 = User::factory()->create(['name' => 'Second Place']);
    $user3 = User::factory()->create(['name' => 'Third Place']);

    DataPoint::factory()->count(10)->create(['user_id' => $user1->id, 'status' => 'approved']);
    DataPoint::factory()->count(7)->create(['user_id' => $user2->id, 'status' => 'approved']);
    DataPoint::factory()->count(5)->create(['user_id' => $user3->id, 'status' => 'approved']);

    $page = visit('/admin/quality-dashboard');

    $page->assertSee('Top Contributor')
        ->assertSee('10 submissions')
        ->assertSee('Second Place')
        ->assertSee('7 submissions')
        ->assertSee('Third Place')
        ->assertSee('5 submissions')
        ->assertNoJavascriptErrors();
});

test('quality dashboard displays api usage tracker widget', function () {
    actingAs($this->admin);

    $page = visit('/admin/quality-dashboard');

    $page->assertSee('Satellite API Calls')
        ->assertSee('Cache Hit Rate')
        ->assertSee('Avg Indices per Analysis')
        ->assertNoJavascriptErrors();
});

test('quality dashboard navigation is accessible from admin panel', function () {
    actingAs($this->admin);

    $page = visit('/admin');

    $page->assertSee('Quality Dashboard')
        ->click('Quality Dashboard')
        ->assertUrlIs('/admin/quality-dashboard')
        ->assertSee('Quality Assurance Dashboard')
        ->assertNoJavascriptErrors();
});
