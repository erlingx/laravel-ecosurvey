<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\User;
use App\Services\UsageTrackingService;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->campaign = Campaign::factory()->create(['user_id' => $this->owner->id]);

    // Mock UsageTrackingService to allow exports
    $this->mock(\App\Services\UsageTrackingService::class, function ($mock) {
        $mock->shouldReceive('canPerformAction')->andReturn(true);
        $mock->shouldReceive('recordReportExport')->andReturn(true);
    });

    // Mock DataExportService
    $this->mock(\App\Services\DataExportService::class, function ($mock) {
        $mock->shouldReceive('exportForPublication')
            ->with(\Mockery::type(Campaign::class))
            ->andReturn(['data' => 'test']);
        $mock->shouldReceive('exportAsCSV')
            ->with(\Mockery::type(Campaign::class))
            ->andReturn('csv,data,test');
    });

    // Mock ReportGeneratorService
    $this->mock(\App\Services\ReportGeneratorService::class, function ($mock) {
        $mock->shouldReceive('generatePDF')
            ->with(\Mockery::type(Campaign::class))
            ->andReturn(response('pdf content', 200, ['Content-Type' => 'application/pdf']));
    });
});

test('campaign owner can export their campaign as JSON', function () {
    // Verify ownership
    expect($this->campaign->user_id)->toBe($this->owner->id);

    // Check if policy would allow
    $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);
    $canView = $gate->forUser($this->owner)->allows('view', $this->campaign);

    expect($canView)->toBeTrue('Policy should allow owner to view their campaign');

    $response = $this->actingAs($this->owner)
        ->withoutMiddleware(\App\Http\Middleware\SubscriptionRateLimiter::class)
        ->get(route('campaigns.export.json', $this->campaign));

    $response->assertSuccessful();
});

test('other users cannot export campaigns they do not own as JSON', function () {
    $response = $this->actingAs($this->otherUser)
        ->withoutMiddleware(\App\Http\Middleware\SubscriptionRateLimiter::class)
        ->get(route('campaigns.export.json', $this->campaign));

    $response->assertForbidden();
});

test('campaign owner can export their campaign as CSV', function () {
    $response = $this->actingAs($this->owner)
        ->withoutMiddleware(\App\Http\Middleware\SubscriptionRateLimiter::class)
        ->get(route('campaigns.export.csv', $this->campaign));

    $response->assertSuccessful();
});

test('other users cannot export campaigns they do not own as CSV', function () {
    $response = $this->actingAs($this->otherUser)
        ->withoutMiddleware(\App\Http\Middleware\SubscriptionRateLimiter::class)
        ->get(route('campaigns.export.csv', $this->campaign));

    $response->assertForbidden();
});

test('campaign owner can export their campaign as PDF', function () {
    $response = $this->actingAs($this->owner)
        ->withoutMiddleware(\App\Http\Middleware\SubscriptionRateLimiter::class)
        ->get(route('campaigns.export.pdf', $this->campaign));

    $response->assertSuccessful();
});

test('other users cannot export campaigns they do not own as PDF', function () {
    $response = $this->actingAs($this->otherUser)
        ->withoutMiddleware(\App\Http\Middleware\SubscriptionRateLimiter::class)
        ->get(route('campaigns.export.pdf', $this->campaign));

    $response->assertForbidden();
});

test('guests cannot export campaigns', function () {
    $response = $this->get(route('campaigns.export.json', $this->campaign));

    $response->assertRedirect(route('login'));
});
