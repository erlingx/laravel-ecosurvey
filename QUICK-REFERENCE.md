# EcoSurvey Quick Reference

**Essential commands and workflows for developers**

---

## 🚀 Quick Start

```bash
# Clone & setup
git clone https://github.com/yourusername/laravel-ecosurvey.git
cd laravel-ecosurvey
ddev start
ddev composer install
ddev npm install
ddev artisan migrate:fresh --seed

# Access
https://ecosurvey.ddev.site
```

---

## 📋 Common Commands

### Development

```bash
# Start environment
ddev start

# Stop environment
ddev stop

# Restart services
ddev restart

# View logs
ddev logs

# SSH into container
ddev ssh
```

### Database

```bash
# Run migrations
ddev artisan migrate

# Fresh migrations with seed
ddev artisan migrate:fresh --seed

# Rollback
ddev artisan migrate:rollback

# Create migration
ddev artisan make:migration create_table_name

# Tinker (REPL)
ddev artisan tinker
```

### Testing

```bash
# All tests
ddev artisan test

# Specific file
ddev artisan test tests/Feature/SubscriptionTest.php

# Filter by name
ddev artisan test --filter=Subscription

# With coverage
ddev artisan test --coverage --min=80

# Parallel testing
ddev artisan test --parallel
```

### Code Quality

```bash
# Format code (auto-fix)
ddev pint --dirty

# Check style (no changes)
ddev pint --test

# Static analysis
ddev composer analyse
```

### Assets

```bash
# Install dependencies
ddev npm install

# Development build (watch mode auto-runs with ddev start)
ddev npm run dev

# Production build
ddev npm run build
```

### Cache Management

```bash
# Clear all caches
ddev artisan optimize:clear

# Cache config
ddev artisan config:cache

# Cache routes
ddev artisan route:cache

# Cache views
ddev artisan view:cache

# Clear view cache
ddev artisan view:clear
```

### Queue & Jobs

```bash
# Work queue (auto-starts with ddev start)
ddev artisan queue:work

# Monitor queue
ddev artisan queue:monitor database

# Restart queue worker
ddev artisan queue:restart

# Failed jobs
ddev artisan queue:failed
ddev artisan queue:retry all
```

### Code Generation

```bash
# Model with migration, factory, seeder
ddev artisan make:model Campaign -mfs

# Controller
ddev artisan make:controller CampaignController --resource

# Livewire component
ddev artisan make:livewire CampaignForm

# Volt component
ddev artisan make:volt pages/campaigns/create

# Form Request
ddev artisan make:request StoreCampaignRequest

# Test (Pest)
ddev artisan make:test Feature/CampaignTest --pest

# Service class
ddev artisan make:class Services/CampaignService

# Job
ddev artisan make:job ProcessSatelliteImages

# Observer
ddev artisan make:observer CampaignObserver --model=Campaign
```

---

## 🗄️ Database Queries (PostGIS)

### Spatial Queries

```php
// Find surveys within 10km of a point
Survey::whereRaw(
    'ST_DWithin(location, ST_MakePoint(?, ?), ?)',
    [-118.2437, 34.0522, 10000]
)->get();

// Find surveys within a zone
Survey::whereRaw(
    'ST_Contains(?, location)',
    [$zone->boundary]
)->get();

// Distance calculation
Survey::selectRaw(
    '*, ST_Distance(location, ST_MakePoint(?, ?)) as distance',
    [-118.2437, 34.0522]
)->orderBy('distance')->get();

// Area of polygon
SurveyZone::selectRaw(
    'id, name, ST_Area(boundary::geography) as area_sqm'
)->get();
```

### Common Eloquent Patterns

```php
// Eager loading (prevent N+1)
Campaign::with(['surveys', 'satelliteImages'])->get();

// Conditional loading
Campaign::when($userId, fn($q) => $q->where('user_id', $userId))->get();

// Exists check
Campaign::whereHas('surveys', fn($q) => $q->where('temperature', '>', 30))->get();

// Count relationship
Campaign::withCount('surveys')->get();

// Latest records
Survey::latest()->limit(10)->get();
```

---

## 🧪 Testing Patterns

### Basic Test

```php
test('user can create campaign', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->post('/campaigns', [
        'name' => 'Test Campaign',
        'description' => 'Test Description',
    ]);
    
    $response->assertRedirect();
    expect(Campaign::count())->toBe(1);
});
```

### Livewire Test

```php
use Livewire\Volt\Volt;

test('campaign form validates name', function () {
    $user = User::factory()->create();
    
    Volt::test('pages.campaigns.create')
        ->actingAs($user)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});
```

### Database Test

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('survey belongs to campaign', function () {
    $campaign = Campaign::factory()->create();
    $survey = Survey::factory()->for($campaign)->create();
    
    expect($survey->campaign->id)->toBe($campaign->id);
});
```

---

## 🔐 Authentication & Authorization

### Login as User

```php
// In tests
$user = User::factory()->create();
$this->actingAs($user);

// In tinker
Auth::login(User::first());
```

### Check Permissions

```php
// In controller
$this->authorize('update', $campaign);

// In Blade
@can('update', $campaign)
    <button>Edit</button>
@endcan

// In code
if ($user->can('update', $campaign)) {
    // ...
}
```

### Gates

```php
// Define in AuthServiceProvider
Gate::define('admin', fn($user) => $user->is_admin);

// Check
if (Gate::allows('admin')) {
    // ...
}
```

---

## 📊 Subscription System

### Check Limits

```php
// Check if user can perform action
if ($user->canCreateDataPoint()) {
    // Create data point
    $user->trackUsage('data_point', 1);
}

// Get usage for current cycle
$usage = $user->getUsageForCurrentCycle('data_point');

// Get tier
$tier = $user->subscriptionTier(); // 'free', 'pro', 'enterprise'
```

### Create Subscription

```php
$user->newSubscription('default', $priceId)->create($paymentMethod);
```

### Cancel Subscription

```php
$user->subscription('default')->cancel();
```

---

## 🛰️ Satellite Service

### Fetch Satellite Data

```php
use App\Services\SatelliteService;

$service = app(SatelliteService::class);
$service->fetchAndProcessForCampaign($campaign);
```

### Calculate Index

```php
$ndvi = $service->calculateNDVI($redBand, $nirBand);
$evi = $service->calculateEVI($redBand, $nirBand, $blueBand);
```

---

## 🔍 Debugging

### Debug Bar

```bash
# Already installed in dev
# Access: Bottom of page (dev only)
```

### Tinker Examples

```php
ddev artisan tinker

>>> User::count()
=> 15

>>> Campaign::with('surveys')->first()
=> App\Models\Campaign {#1234 ...}

>>> Survey::whereRaw('ST_DWithin(location, ST_MakePoint(-118, 34), 10000)')->count()
=> 42

>>> DB::enableQueryLog()
>>> User::first()
>>> DB::getQueryLog()
```

### Logging

```php
// Log debug info
Log::debug('Processing survey', ['id' => $survey->id]);

// View logs
ddev artisan pail

// Or tail file
ddev exec tail -f storage/logs/laravel.log
```

---

## 🎨 Frontend (Livewire + Alpine)

### Livewire Wire Directives

```blade
{{-- Real-time binding --}}
<input wire:model.live="search">

{{-- Debounced --}}
<input wire:model.live.debounce.300ms="search">

{{-- Loading state --}}
<button wire:click="save" wire:loading.attr="disabled">
    <span wire:loading.remove>Save</span>
    <span wire:loading>Saving...</span>
</button>

{{-- Polling --}}
<div wire:poll.5s>
    Current time: {{ now() }}
</div>
```

### Alpine.js Patterns

```blade
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open" x-transition>
        Content
    </div>
</div>
```

---

## 📦 Deployment

### Railway (Quick)

```bash
# Connect to Railway
railway login
railway link

# Deploy
git push origin main

# Run migrations
railway run php artisan migrate --force
```

### Manual Production

```bash
# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev

# Build assets
npm run build
```

---

## 🆘 Troubleshooting

### Clear Everything

```bash
ddev artisan optimize:clear
ddev artisan config:clear
ddev artisan route:clear
ddev artisan view:clear
ddev composer dump-autoload
```

### Database Issues

```bash
# Check PostGIS
ddev exec psql -c "SELECT PostGIS_Version();"

# Reset database
ddev artisan migrate:fresh --seed
```

### Queue Not Working

```bash
# Restart queue worker
ddev artisan queue:restart

# Check failed jobs
ddev artisan queue:failed

# Retry all failed
ddev artisan queue:retry all
```

### Vite Not Working

```bash
# Rebuild assets
ddev npm run build

# Check Vite is running
ddev exec bash -c "ps aux | grep vite"
```

---

## 📚 Documentation Links

- [Architecture](docs/02-architecture/ARCHITECTURE.md)
- [API Reference](docs/03-integrations/API-REFERENCE.md)
- [Deployment](docs/04-guides/DEPLOYMENT.md)
- [Contributing](CONTRIBUTING.md)

---

**Last Updated**: January 26, 2026
