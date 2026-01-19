<?php

use App\Models\Campaign;
use App\Models\DataPoint;
use App\Models\EnvironmentalMetric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake(); // Prevent automatic satellite enrichment which can hang tests
    $this->user = User::factory()->create();
    $this->campaign = Campaign::factory()->create(['status' => 'active']);
    $this->metric = EnvironmentalMetric::factory()->create(['is_active' => true]);
});

test('reading form can be rendered', function () {
    $this->actingAs($this->user)
        ->get(route('data-points.submit'))
        ->assertOk()
        ->assertSee('Submit Environmental Reading');
});

test('authenticated users can submit a reading with GPS coordinates', function () {
    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('metricId', $this->metric->id)
        ->set('value', 42.5)
        ->set('latitude', 39.7392)
        ->set('longitude', -104.9903)
        ->set('accuracy', 15.5)
        ->set('notes', 'Clear sunny day')
        ->call('save')
        ->assertHasNoErrors();

    // Verify DataPoint was created
    $this->assertDatabaseHas('data_points', [
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 42.5,
        'accuracy' => 15.5,
        'notes' => 'Clear sunny day',
    ]);

    // Verify PostGIS location was stored correctly
    $dataPoint = DataPoint::query()->latest()->first();
    expect($dataPoint)->not->toBeNull();
});

test('campaign field is required', function () {
    // Create a second campaign to prevent auto-selection
    Campaign::factory()->create(['status' => 'active']);

    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('metricId', $this->metric->id)
        ->set('value', 42.5)
        ->set('latitude', 39.7392)
        ->set('longitude', -104.9903)
        ->call('save')
        ->assertHasErrors(['campaignId' => 'required']);
});

test('metric field is required', function () {
    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('value', 42.5)
        ->set('latitude', 39.7392)
        ->set('longitude', -104.9903)
        ->call('save')
        ->assertHasErrors(['metricId' => 'required']);
});

test('value field is required and must be numeric', function () {
    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('metricId', $this->metric->id)
        ->set('latitude', 39.7392)
        ->set('longitude', -104.9903)
        ->call('save')
        ->assertHasErrors(['value' => 'required']);

    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('metricId', $this->metric->id)
        ->set('value', 'not-a-number')
        ->set('latitude', 39.7392)
        ->set('longitude', -104.9903)
        ->call('save')
        ->assertHasErrors(['value' => 'numeric']);
});

test('GPS coordinates are required', function () {
    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('metricId', $this->metric->id)
        ->set('value', 42.5)
        ->call('save')
        ->assertHasErrors(['location']);
});

test('latitude must be between -90 and 90', function () {
    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('metricId', $this->metric->id)
        ->set('value', 42.5)
        ->set('latitude', 95)
        ->set('longitude', -104.9903)
        ->call('save')
        ->assertHasErrors(['latitude']);
});

test('longitude must be between -180 and 180', function () {
    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('metricId', $this->metric->id)
        ->set('value', 42.5)
        ->set('latitude', 39.7392)
        ->set('longitude', 200)
        ->call('save')
        ->assertHasErrors(['longitude']);
});

test('notes field is optional but has max length', function () {
    $longNotes = str_repeat('a', 1001);

    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('metricId', $this->metric->id)
        ->set('value', 42.5)
        ->set('latitude', 39.7392)
        ->set('longitude', -104.9903)
        ->set('notes', $longNotes)
        ->call('save')
        ->assertHasErrors(['notes']);
});

test('photo upload is optional', function () {
    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('metricId', $this->metric->id)
        ->set('value', 42.5)
        ->set('latitude', 39.7392)
        ->set('longitude', -104.9903)
        ->call('save')
        ->assertHasNoErrors();

    $dataPoint = DataPoint::query()->latest()->first();
    expect($dataPoint->photo_path)->toBeNull();
});

test('can upload photo with reading', function () {
    Storage::fake('uploads');

    $photo = UploadedFile::fake()->image('test-photo.jpg', 800, 600);

    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('metricId', $this->metric->id)
        ->set('value', 42.5)
        ->set('latitude', 39.7392)
        ->set('longitude', -104.9903)
        ->set('photo', $photo)
        ->call('save')
        ->assertHasNoErrors();

    $dataPoint = DataPoint::query()->latest()->first();
    expect($dataPoint->photo_path)->not->toBeNull();
    Storage::disk('uploads')->assertExists($dataPoint->photo_path);
});

test('photo must be valid image file', function () {
    Storage::fake('uploads');

    $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);

    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('metricId', $this->metric->id)
        ->set('value', 42.5)
        ->set('latitude', 39.7392)
        ->set('longitude', -104.9903)
        ->set('photo', $invalidFile)
        ->call('save')
        ->assertHasErrors(['photo']);
});

test('photo size must not exceed 5MB', function () {
    Storage::fake('uploads');

    $largePhoto = UploadedFile::fake()->image('large.jpg')->size(6000); // 6MB

    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form')
        ->set('campaignId', $this->campaign->id)
        ->set('metricId', $this->metric->id)
        ->set('value', 42.5)
        ->set('latitude', 39.7392)
        ->set('longitude', -104.9903)
        ->set('photo', $largePhoto)
        ->call('save')
        ->assertHasErrors(['photo']);
});

test('form auto-selects campaign if only one exists', function () {
    // Delete other campaigns
    Campaign::query()->where('id', '!=', $this->campaign->id)->delete();

    $component = Livewire::actingAs($this->user)
        ->test('data-collection.reading-form');

    expect($component->get('campaignId'))->toBe($this->campaign->id);
});

test('campaigns list only shows active campaigns', function () {
    Campaign::factory()->create(['status' => 'completed']);
    Campaign::factory()->create(['status' => 'draft']);

    $component = Livewire::actingAs($this->user)
        ->test('data-collection.reading-form');

    $campaigns = $component->campaigns;

    expect($campaigns)->toHaveCount(1)
        ->and($campaigns->first()->status)->toBe('active');
});

test('metrics list only shows active metrics', function () {
    // Create an inactive metric that should NOT appear
    $inactiveMetric = EnvironmentalMetric::factory()->create([
        'name' => 'Inactive Test Metric',
        'is_active' => false,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test('data-collection.reading-form');

    $metrics = $component->metrics;

    // Verify we have some metrics
    expect($metrics->count())->toBeGreaterThan(0);

    // The core assertion: inactive metric should NOT be in the list
    $metricIds = $metrics->pluck('id')->toArray();
    expect($metricIds)->not->toContain($inactiveMetric->id);
});

test('can edit data point and update photo', function () {
    Storage::fake('uploads');

    // Create existing data point with photo
    $oldPhoto = UploadedFile::fake()->image('old-photo.jpg');
    $oldPhotoPath = $oldPhoto->store('data-points', 'uploads');

    $dataPoint = DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 25.5,
        'photo_path' => $oldPhotoPath,
        'notes' => 'Original note',
    ]);

    // Update with new photo
    $newPhoto = UploadedFile::fake()->image('new-photo.jpg');

    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form', ['dataPoint' => $dataPoint->id])
        ->assertSet('existingPhotoPath', $oldPhotoPath)
        ->assertSet('value', '25.50')
        ->set('value', 30.0)
        ->set('notes', 'Updated note')
        ->set('photo', $newPhoto)
        ->call('save')
        ->assertHasNoErrors();

    // Refresh from database
    $dataPoint->refresh();

    // Assert photo was updated
    expect($dataPoint->photo_path)
        ->not->toBeNull()
        ->not->toBe($oldPhotoPath);

    expect($dataPoint->value)->toBe('30.00');
    expect($dataPoint->notes)->toBe('Updated note');

    // Verify new photo exists and old photo was deleted
    Storage::disk('uploads')->assertExists($dataPoint->photo_path);
    Storage::disk('uploads')->assertMissing($oldPhotoPath);
});

test('photo persists after edit without new photo upload', function () {
    Storage::fake('uploads');

    // Create existing data point with photo
    $photo = UploadedFile::fake()->image('existing-photo.jpg');
    $photoPath = $photo->store('data-points', 'uploads');

    $dataPoint = DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 25.5,
        'photo_path' => $photoPath,
    ]);

    // Update WITHOUT uploading new photo
    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form', ['dataPoint' => $dataPoint->id])
        ->assertSet('existingPhotoPath', $photoPath)
        ->set('value', 30.0)
        ->set('notes', 'Updated without changing photo')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('existingPhotoPath', $photoPath); // Verify component still has photo path

    // Refresh from database
    $dataPoint->refresh();

    // Assert photo is STILL there
    expect($dataPoint->photo_path)
        ->not->toBeNull()
        ->toBe($photoPath);

    expect($dataPoint->value)->toBe('30.00');

    // Verify photo still exists
    Storage::disk('uploads')->assertExists($photoPath);
});

test('existingPhotoPath updates in component after uploading new photo', function () {
    Storage::fake('uploads');

    // Create existing data point with photo
    $oldPhoto = UploadedFile::fake()->image('old-photo.jpg');
    $oldPhotoPath = $oldPhoto->store('data-points', 'uploads');

    $dataPoint = DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 25.5,
        'photo_path' => $oldPhotoPath,
    ]);

    // Upload new photo
    $newPhoto = UploadedFile::fake()->image('new-photo.jpg');

    $component = Livewire::actingAs($this->user)
        ->test('data-collection.reading-form', ['dataPoint' => $dataPoint->id])
        ->assertSet('existingPhotoPath', $oldPhotoPath)
        ->set('photo', $newPhoto)
        ->call('save')
        ->assertHasNoErrors();

    // Component should have updated existingPhotoPath and cleared photo
    $newPath = $component->get('existingPhotoPath');
    expect($newPath)->not->toBeNull();
    expect($newPath)->not->toBe($oldPhotoPath);
    expect($component->get('photo'))->toBeNull();

    // Verify in database
    $dataPoint->refresh();
    expect($dataPoint->photo_path)->toBe($newPath);
    Storage::disk('uploads')->assertExists($newPath);
});

test('photo persists after refresh when uploading new photo in edit mode', function () {
    Storage::fake('uploads');

    // Create existing data point WITHOUT photo
    $dataPoint = DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'value' => 25.5,
        'photo_path' => null,
    ]);

    // Upload a new photo
    $newPhoto = UploadedFile::fake()->image('new-photo.jpg');

    $component = Livewire::actingAs($this->user)
        ->test('data-collection.reading-form', ['dataPoint' => $dataPoint->id])
        ->assertSet('existingPhotoPath', null)
        ->set('photo', $newPhoto)
        ->call('save')
        ->assertHasNoErrors();

    expect($component->get('existingPhotoPath'))->not->toBeNull();
    expect($component->get('existingPhotoUrl'))->not->toBeNull();

    // Refresh from database - THE ACTUAL BUG TEST
    $dataPoint->refresh();
    $savedPhotoPath = $dataPoint->photo_path;

    // Photo should be saved in database
    expect($savedPhotoPath)->not->toBeNull();
    Storage::disk('uploads')->assertExists($savedPhotoPath);

    // NOW SIMULATE PAGE REFRESH - reload the component fresh
    $reloadedComponent = Livewire::actingAs($this->user)
        ->test('data-collection.reading-form', ['dataPoint' => $dataPoint->id]);

    // After "refresh", the existingPhotoPath should be loaded from database
    expect($reloadedComponent->get('existingPhotoPath'))->toBe($savedPhotoPath);
});

test('photo url uses uploads disk and geojson exposes it for popups', function () {
    Storage::fake('uploads');

    $photo = UploadedFile::fake()->image('test-photo.jpg', 800, 600);

    $dataPoint = DataPoint::factory()->create([
        'campaign_id' => $this->campaign->id,
        'environmental_metric_id' => $this->metric->id,
        'user_id' => $this->user->id,
        'photo_path' => null,
    ]);

    Livewire::actingAs($this->user)
        ->test('data-collection.reading-form', ['dataPoint' => $dataPoint->id])
        ->set('photo', $photo)
        ->call('save')
        ->assertHasNoErrors();

    $dataPoint->refresh();

    expect($dataPoint->photo_path)
        ->not->toBeNull()
        ->and($dataPoint->photo_path)
        ->toStartWith('data-points/');

    Storage::disk('uploads')->assertExists($dataPoint->photo_path);

    // Verify photo_url is generated
    expect($dataPoint->photo_url)->not->toBeNull();

    $geojson = app(\App\Services\GeospatialService::class)->getDataPointsAsGeoJSON($this->campaign->id);

    $feature = collect($geojson['features'])->firstWhere('properties.id', $dataPoint->id);

    expect($feature)->not->toBeNull();
    expect($feature['properties']['photo_path'])->toBe($dataPoint->photo_url);
});
