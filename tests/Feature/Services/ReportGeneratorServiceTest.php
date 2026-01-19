<?php

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\EnvironmentalMetric;
use App\Models\User;
use App\Services\ReportGeneratorService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake(); // Prevent automatic satellite enrichment which can hang tests
});

test('generates PDF report for campaign', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    $service = app(ReportGeneratorService::class);
    $response = $service->generatePDF($campaign);

    expect($response)->toBeInstanceOf(\Illuminate\Http\Response::class)
        ->and($response->headers->get('content-type'))->toContain('pdf');
});

test('PDF includes campaign metadata', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test Campaign',
        'description' => 'Test Description',
    ]);

    $service = app(ReportGeneratorService::class);
    $response = $service->generatePDF($campaign);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('content-disposition'))->toContain('test-campaign');
});

test('PDF generation works with data points', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);
    $metric = EnvironmentalMetric::factory()->create();

    DataPoint::factory()->count(5)->create([
        'campaign_id' => $campaign->id,
        'environmental_metric_id' => $metric->id,
        'status' => 'approved',
    ]);

    $service = app(ReportGeneratorService::class);
    $response = $service->generatePDF($campaign);

    expect($response->getStatusCode())->toBe(200);
});

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
