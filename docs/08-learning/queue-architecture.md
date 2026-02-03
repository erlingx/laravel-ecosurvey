# Queue Architecture

## Laravel Queue System

### Configuration
```php
// config/queue.php
'default' => 'database',

'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
    ],
],
```

### Worker Command
```bash
ddev artisan queue:work --sleep=3 --tries=3 --timeout=60 --max-time=3600
```

**Flags:**
- `--sleep=3` → Wait 3s when queue empty
- `--tries=3` → Retry failed jobs 3 times
- `--timeout=60` → Kill job after 60s
- `--max-time=3600` → Restart worker after 1hr (picks up code changes)

---

## Job Structure

### Basic Job
```php
class EnrichDataPointWithSatelliteData implements ShouldQueue
{
    use Queueable;
    
    public function __construct(
        public DataPoint $dataPoint
    ) {}
    
    public function handle(
        CopernicusService $copernicus,
        UsageService $usage
    ): void {
        // Job logic
    }
}
```

### Dispatching
```php
// Sync (immediate)
EnrichDataPointWithSatelliteData::dispatchSync($dataPoint);

// Async (queued)
EnrichDataPointWithSatelliteData::dispatch($dataPoint);

// Delayed
EnrichDataPointWithSatelliteData::dispatch($dataPoint)
    ->delay(now()->addMinutes(5));

// Specific queue
EnrichDataPointWithSatelliteData::dispatch($dataPoint)
    ->onQueue('satellite');
```

---

## Transaction Safety

### Atomic Operations
```php
public function handle(Service $service): void
{
    DB::transaction(function () use ($service) {
        // Create analysis record
        SatelliteAnalysis::create([...]);
        
        // Record usage (billing)
        $service->recordUsage($this->user);
        
        // Both succeed or both fail
    });
}
```

**Why:** Prevents billing without data or data without billing

---

## Rate Limiting in Jobs

### Redis Throttle
```php
use Illuminate\Support\Facades\Redis;

public function handle(): void
{
    Redis::throttle('copernicus-api')
        ->allow(30)           // 30 calls
        ->every(60)           // per 60 seconds
        ->then(function () {
            // Execute job
        }, function () {
            // Release back to queue with 10s delay
            return $this->release(10);
        });
}
```

### Usage Limits
```php
public function handle(UsageService $usage): void
{
    // Check BEFORE processing
    if (!$usage->canPerformAction($this->user, 'satellite_analyses')) {
        Log::warning("User {$this->user->id} exceeded limit");
        return; // Don't retry
    }
    
    // Proceed with API calls
}
```

---

## Error Handling

### Failed Job Handling
```php
class EnrichJob implements ShouldQueue
{
    public $tries = 3;
    public $backoff = [10, 30, 60]; // Exponential backoff
    
    public function failed(\Throwable $exception): void
    {
        Log::error("Job failed permanently", [
            'data_point_id' => $this->dataPoint->id,
            'error' => $exception->getMessage(),
        ]);
        
        // Notify user, cleanup, etc.
    }
}
```

### Retry Logic
```php
use Illuminate\Queue\Middleware\ThrottlesExceptions;

public function middleware(): array
{
    return [
        new ThrottlesExceptions(10, 5), // 10 attempts per 5 min
    ];
}

public function retryUntil(): DateTime
{
    return now()->addHours(24);
}
```

### Conditional Retry
```php
public function handle(): void
{
    try {
        $result = $this->apiCall();
    } catch (RateLimitException $e) {
        // Retry after delay
        $this->release(60);
        return;
    } catch (ValidationException $e) {
        // Don't retry - bad data
        $this->fail($e);
        return;
    } catch (\Exception $e) {
        // Let default retry logic handle
        throw $e;
    }
}
```

---

## Monitoring

### Queue Status
```bash
# Monitor queue size
ddev artisan queue:monitor database

# List failed jobs
ddev artisan queue:failed

# Retry specific job
ddev artisan queue:retry {id}

# Retry all failed
ddev artisan queue:retry all

# Clear failed jobs
ddev artisan queue:flush
```

### Logging
```php
public function handle(): void
{
    Log::info('Job started', [
        'job_id' => $this->job->getJobId(),
        'data_point_id' => $this->dataPoint->id,
    ]);
    
    // Work...
    
    Log::info('Job completed', [
        'duration_ms' => $this->getDuration(),
    ]);
}
```

### Metrics
```php
// Track job timing
SatelliteApiCall::create([
    'response_time_ms' => $this->startTime->diffInMilliseconds(now()),
    'cached' => $wasCached,
    'cost_credits' => $cost,
]);
```

---

## Workflow Patterns

### Chaining Jobs
```php
EnrichDataPoint::dispatch($dataPoint)
    ->chain([
        new AnalyzeQuality($dataPoint),
        new NotifyUser($dataPoint->user),
    ]);
```

### Batching
```php
use Illuminate\Bus\Batch;

$batch = Bus::batch([
    new EnrichDataPoint($point1),
    new EnrichDataPoint($point2),
    new EnrichDataPoint($point3),
])->then(function (Batch $batch) {
    // All jobs completed
})->catch(function (Batch $batch, Throwable $e) {
    // First failure
})->finally(function (Batch $batch) {
    // Batch finished (success or failure)
})->dispatch();
```

### Progress Tracking
```php
// In job
$batch = $this->batch();
if ($batch) {
    $batch->progress = ($batch->processedJobs() / $batch->totalJobs) * 100;
}

// Check progress
$batch = Bus::findBatch($batchId);
echo $batch->progress; // 0-100
```

---

## Horizon (Alternative)

### Setup
```bash
composer require laravel/horizon
php artisan horizon:install
```

### Dashboard
```
http://localhost/horizon
```

**Features:**
- Real-time monitoring
- Job metrics
- Failed job management
- Queue prioritization
- Auto-scaling

---

## Pitfalls

### Code Changes
```bash
# Queue worker caches code
# MUST restart after changes to:
# - Jobs
# - Services
# - Config

# Fast restart (don't use ddev restart)
ddev artisan queue:restart
```

### Memory Leaks
```php
// Worker runs for hours
// Prevent memory bloat:

public function handle(): void
{
    // Clear resolved instances
    $this->dataPoint->refresh();
    
    // Unset large variables
    unset($imageData);
    
    // Explicit GC (rarely needed)
    gc_collect_cycles();
}
```

### Database Connections
```php
// Long-running workers can lose DB connection
// Laravel auto-reconnects, but be aware

// Force reconnect if needed
DB::reconnect();
```

### Serialization
```php
// Jobs are serialized to database
// Don't pass large objects or closures

// Bad
public function __construct(
    public Collection $largeDataset // Serialized!
) {}

// Good
public function __construct(
    public int $dataPointId // Fetch in handle()
) {}
```

### Failed Job Loop
```php
// Prevent infinite retries on permanent failures

public function handle(): void
{
    if ($this->attempts() > 3) {
        $this->fail(new \Exception('Max attempts exceeded'));
        return;
    }
    
    // Work...
}
```

### Transaction Deadlocks
```php
// Multiple jobs updating same records
// Use row-level locking

DataPoint::lockForUpdate()->find($id);

// Or optimistic locking
if ($dataPoint->updated_at != $originalTimestamp) {
    throw new ConcurrentModificationException();
}
```

---

## Best Practices

1. **Idempotent jobs** - Safe to run multiple times
2. **Small payloads** - Pass IDs, not models
3. **Atomic operations** - Use transactions
4. **Limit retries** - Prevent infinite loops
5. **Monitor failed jobs** - Alert on spikes
6. **Log key metrics** - Track performance
7. **Check limits first** - Before expensive operations
8. **Backoff strategy** - Don't hammer failing services
9. **Restart after code changes** - `queue:restart`
10. **Use Horizon** - For complex queue setups
