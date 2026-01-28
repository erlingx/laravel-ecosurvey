<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\DataPoint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Queue;

test('lazy loading prevention is enabled in non-production environments', function () {
    // Verify that Model::preventLazyLoading() was called during app bootstrap
    expect(Model::preventsLazyLoading())->toBeTrue();
});

test('AppServiceProvider boots lazy loading prevention correctly', function () {
    // Verify the code exists in AppServiceProvider
    $appServiceProvider = file_get_contents(app_path('Providers/AppServiceProvider.php'));

    expect($appServiceProvider)->toContain('Model::preventLazyLoading');
    expect($appServiceProvider)->toContain('! app()->isProduction()');
});

test('eager loaded relationships work correctly', function () {
    Queue::fake();

    $campaign = Campaign::factory()->create();
    DataPoint::factory()->create(['campaign_id' => $campaign->id]);

    // Get campaign WITH eager loading
    $campaignFromDb = Campaign::with('dataPoints')->find($campaign->id);

    // This should NOT throw exception
    expect($campaignFromDb->dataPoints)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($campaignFromDb->dataPoints)->toHaveCount(1);
});
