# Subscription & Billing

## Stripe Integration

### Tier Structure
```
Free:       $0/mo    - 50 data points, basic features
Pro:        $49/mo   - Unlimited points, satellite analysis
Enterprise: $199/mo  - Teams, API access, white-label
```

### Usage Metering
```php
// Check before action
if (!$usageService->canPerformAction($user, 'satellite_analyses')) {
    return redirect()->route('billing')->with('error', 'Upgrade required');
}

// Record after success
DB::transaction(function() {
    SatelliteAnalysis::create([...]);
    $usageService->recordSatelliteAnalysis($user, 'all_indices');
});
```

### Features by Tier

| Feature | Free | Pro | Enterprise |
|---------|------|-----|------------|
| Data points/month | 50 | Unlimited | Unlimited |
| Satellite analyses | 0 | 100/mo | Unlimited |
| Export reports | 5/mo | Unlimited | Unlimited |
| Team members | 1 | 1 | 5-50 |
| API access | ❌ | ❌ | ✅ |
| Support | Community | Email | Priority |

---

## Subscription Management

### User Actions

**Subscribe**
```php
return $user->newSubscription('default', 'price_pro_monthly')
    ->checkout([
        'success_url' => route('billing.success'),
        'cancel_url' => route('billing'),
    ]);
```

**Cancel (Immediate)**
```php
$subscription = $user->subscription('default');
$subscription->cancelNow();
```

**Cancel (End of Period)**
```php
$subscription = $user->subscription('default');
$subscription->cancel(); // Continues until period_ends_at
```

**Resume**
```php
if ($subscription->onGracePeriod()) {
    $subscription->resume();
}
```

**Update Payment Method**
```php
return $user->redirectToBillingPortal(route('billing'));
```

---

## Rate Limiting Middleware

### Implementation
```php
class SubscriptionRateLimiter
{
    public function handle(Request $request, Closure $next)
    {
        $tier = $request->user()->subscription_tier ?? 'free';
        
        $limits = [
            'free' => 30,
            'pro' => 60,
            'premium' => 300,
            'enterprise' => 1000,
        ];
        
        $limit = $limits[$tier] ?? 30;
        
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return response()->json([
                'message' => 'Rate limit exceeded',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }
        
        RateLimiter::hit($key, 3600); // 1 hour window
        
        return $next($request);
    }
}
```

### Registration
```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'subscription.rate' => \App\Http\Middleware\SubscriptionRateLimiter::class,
    ]);
})
```

### Usage
```php
Route::middleware(['auth', 'subscription.rate'])->group(function() {
    Route::post('/api/data-points', [DataPointController::class, 'store']);
    Route::post('/api/satellite-analysis', [SatelliteController::class, 'analyze']);
});
```

---

## Webhook Handling

### Signature Verification
```php
public function handleWebhook(Request $request)
{
    $payload = $request->getContent();
    $signature = $request->header('Stripe-Signature');
    
    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload,
            $signature,
            config('services.stripe.webhook_secret')
        );
    } catch (\Exception $e) {
        return response('Invalid signature', 400);
    }
    
    return $this->processEvent($event);
}
```

### Event Handling
```php
private function processEvent($event)
{
    return match($event->type) {
        'customer.subscription.created' => $this->handleSubscriptionCreated($event->data->object),
        'customer.subscription.updated' => $this->handleSubscriptionUpdated($event->data->object),
        'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->data->object),
        'invoice.payment_succeeded' => $this->handlePaymentSucceeded($event->data->object),
        'invoice.payment_failed' => $this->handlePaymentFailed($event->data->object),
        default => response('Unhandled event type', 200)
    };
}
```

### Idempotency
```php
// Prevent duplicate processing
if (ProcessedWebhook::where('event_id', $event->id)->exists()) {
    return response('Already processed', 200);
}

DB::transaction(function() use ($event) {
    // Process event
    ProcessedWebhook::create(['event_id' => $event->id]);
});
```

---

## Usage Dashboard

### Display Current Usage
```php
$user->load([
    'dataPoints' => fn($q) => $q->whereMonth('created_at', now()->month),
    'satelliteAnalyses' => fn($q) => $q->whereMonth('created_at', now()->month),
    'exports' => fn($q) => $q->whereMonth('created_at', now()->month),
]);

$usage = [
    'data_points' => $user->dataPoints->count(),
    'satellite_analyses' => $user->satelliteAnalyses->count(),
    'exports' => $user->exports->count(),
];

$limits = config('subscriptions.limits')[$user->subscription_tier];
```

### Progress Bars
```blade
@foreach(['data_points', 'satellite_analyses', 'exports'] as $metric)
    <div>
        <span>{{ ucfirst(str_replace('_', ' ', $metric)) }}</span>
        <div class="progress-bar">
            <div style="width: {{ ($usage[$metric] / $limits[$metric]) * 100 }}%"></div>
        </div>
        <span>{{ $usage[$metric] }} / {{ $limits[$metric] }}</span>
    </div>
@endforeach
```

---

## Invoice Management

### List Invoices
```php
$invoices = $user->invoices();

foreach ($invoices as $invoice) {
    echo $invoice->date()->toFormattedDateString();
    echo $invoice->total();
    echo $invoice->downloadUrl();
}
```

### Download Invoice
```blade
<a href="{{ $invoice->downloadUrl() }}" target="_blank">
    Download Invoice
</a>
```

---

## Testing

### Subscription Tests
```php
test('free users cannot exceed 50 data points', function() {
    $user = User::factory()->create(['subscription_tier' => 'free']);
    
    DataPoint::factory()->count(50)->create(['user_id' => $user->id]);
    
    expect($usageService->canPerformAction($user, 'data_points'))->toBeFalse();
});

test('pro users have unlimited data points', function() {
    $user = User::factory()->create(['subscription_tier' => 'pro']);
    
    DataPoint::factory()->count(1000)->create(['user_id' => $user->id]);
    
    expect($usageService->canPerformAction($user, 'data_points'))->toBeTrue();
});
```

### Rate Limiting Tests
```php
test('free tier gets 30 requests per hour', function() {
    $user = User::factory()->create(['subscription_tier' => 'free']);
    
    for ($i = 0; $i < 30; $i++) {
        $this->actingAs($user)->post('/api/data-points', $data)
            ->assertSuccessful();
    }
    
    $this->actingAs($user)->post('/api/data-points', $data)
        ->assertStatus(429);
});
```

---

## Pitfalls

### Payment Method Required
- Stripe requires valid payment method even for free trials
- Use `allowPromotionCodes()` for discounts
- Test mode cards: `4242 4242 4242 4242`

### Webhook Timing
- Webhooks can arrive out of order
- Always check current state in database
- Use `created` timestamp to order events

### Grace Period Confusion
- `onGracePeriod()` → cancelled but still active
- `cancelled()` → will not renew
- `active()` → currently has access

### Metered Billing
- Reset counts monthly (cron job)
- Track usage in separate table (not user model)
- Consider pro-rating on plan changes

### Security
- Never expose Stripe secret key
- Verify webhook signatures
- Use HTTPS in production
- Rotate webhook secret after breaches
