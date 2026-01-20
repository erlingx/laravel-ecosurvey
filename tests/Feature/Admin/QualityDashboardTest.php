<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

test('quality dashboard page is accessible by authenticated admin', function () {
    actingAs($this->admin);

    $response = $this->get('/admin/quality-dashboard');

    $response->assertSuccessful();
});

test('quality dashboard page displays heading and subheading', function () {
    actingAs($this->admin);

    $response = $this->get('/admin/quality-dashboard');

    $response->assertSee('Quality Assurance Dashboard');
    $response->assertSee('Monitor data quality metrics, user contributions, and API usage in real-time.');
});

test('quality dashboard page displays command reference section', function () {
    actingAs($this->admin);

    $response = $this->get('/admin/quality-dashboard');

    $response->assertSee('Quality Guidelines');
    $response->assertSee('GPS Accuracy');
    $response->assertSee('Reviewing Data');
    $response->assertSee('Data Points');
    $response->assertSee('QA Flags');
    $response->assertSee('Bulk Actions');
});

test('quality dashboard page requires authentication', function () {
    $response = $this->get('/admin/quality-dashboard');

    $response->assertRedirect('/admin/login');
});
