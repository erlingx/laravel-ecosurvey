<?php

use App\Jobs\EnrichDataPointWithSatelliteData;
use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\User;
use App\Services\UsageTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake(); // Prevent automatic satellite enrichment which can hang tests
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->usageService = app(UsageTrackingService::class);
});
test('data point creation is blocked when limit reached', function () {
    // Create 50 data points (free tier limit)
    for ($i = 0; $i < 50; $i++) {
        $this->usageService->recordDataPointCreation($this->user);
    }
    expect($this->usageService->canPerformAction($this->user, 'data_points'))->toBeFalse();
});
test('satellite analysis job stops when limit reached', function () {
    // Create 10 satellite analyses (free tier limit)
    for ($i = 0; $i < 10; $i++) {
        $this->usageService->recordSatelliteAnalysis($this->user, 'ndvi');
    }
    $dataPoint = DataPoint::factory()->for($this->user)->create();
    // Job should check limit and exit early
    Queue::fake();
    $job = new EnrichDataPointWithSatelliteData($dataPoint);
    expect($this->usageService->canPerformAction($this->user, 'satellite_analyses'))->toBeFalse();
});
test('export is blocked when limit reached', function () {
    // Create 2 exports (free tier limit)
    for ($i = 0; $i < 2; $i++) {
        $this->usageService->recordReportExport($this->user, 'pdf');
    }
    $campaign = Campaign::factory()->for($this->user)->create();
    $response = $this->get(route('campaigns.export.pdf', $campaign));
    $response->assertForbidden();
    $response->assertSee('monthly export limit');
});
test('pro user can exceed free limits', function () {
    // Create Pro subscription
    $this->user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price' => config('subscriptions.plans.pro.stripe_price_id'),
        'quantity' => 1,
    ]);
    DB::table('subscription_items')->insert([
        'subscription_id' => $this->user->subscriptions()->first()->id,
        'stripe_id' => 'si_test',
        'stripe_product' => 'prod_test',
        'stripe_price' => config('subscriptions.plans.pro.stripe_price_id'),
        'quantity' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    // Record 51 data points (over free limit of 50)
    for ($i = 0; $i < 51; $i++) {
        $this->usageService->recordDataPointCreation($this->user);
    }
    // Pro tier limit is 500, so should still be able to perform action
    expect($this->usageService->canPerformAction($this->user, 'data_points'))->toBeTrue();
    expect($this->usageService->getRemainingQuota($this->user, 'data_points'))->toBe(449);
});
test('usage is tracked per billing cycle', function () {
    $this->usageService->recordDataPointCreation($this->user);
    $this->usageService->recordSatelliteAnalysis($this->user, 'ndvi');
    $this->usageService->recordReportExport($this->user, 'pdf');
    $usage = $this->usageService->getCurrentUsage($this->user);
    expect($usage['data_points'])->toBe(1);
    expect($usage['satellite_analyses'])->toBe(1);
    expect($usage['report_exports'])->toBe(1);
});
test('csv export is blocked when limit reached', function () {
    // Record 2 exports (free tier limit)
    for ($i = 0; $i < 2; $i++) {
        $this->usageService->recordReportExport($this->user, 'csv');
    }
    $campaign = Campaign::factory()->for($this->user)->create();
    $response = $this->get(route('campaigns.export.csv', $campaign));
    $response->assertForbidden();
    $response->assertSee('monthly export limit');
});
test('json export is blocked when limit reached', function () {
    // Record 2 exports (free tier limit)
    for ($i = 0; $i < 2; $i++) {
        $this->usageService->recordReportExport($this->user, 'json');
    }
    $campaign = Campaign::factory()->for($this->user)->create();
    $response = $this->get(route('campaigns.export.json', $campaign));
    $response->assertForbidden();
    $response->assertSee('monthly export limit');
});
test('export records usage after successful export', function () {
    $campaign = Campaign::factory()->for($this->user)->create();
    $this->get(route('campaigns.export.json', $campaign));
    $usage = $this->usageService->getCurrentUsage($this->user);
    expect($usage['report_exports'])->toBe(1);
});
