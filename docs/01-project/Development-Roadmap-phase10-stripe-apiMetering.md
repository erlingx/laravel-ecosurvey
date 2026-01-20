# EcoSurvey Development Roadmap - Phase 10: Stripe API Metering

**Based on:** Development Roadmap.md (Main Phases 1-9)  
**Start Date:** January 20, 2026  
**Target Completion:** January 27, 2026  
**Duration:** 1 week (7 days)  
**Status:** ⏸️ PENDING

---

## Overview

**Main Roadmap Phases 1-9:** ✅ COMPLETE (Foundation → Quality Assurance Dashboard)  
**This is Phase 10** in the main Development Roadmap (Premium Features & Monetization).

### Current State (January 20, 2026)

**What's Working:** ✅
- API usage tracking in database (`api_usages` table)
- User contribution leaderboard
- QA flags and quality metrics
- Satellite data enrichment (7 indices)
- Data export and reporting
- Admin dashboard with widgets
- **Tests:** 180+ passing

**What's Missing:** ❌
- Stripe subscription integration
- Usage-based billing / API metering
- Rate limiting per subscription tier
- Payment gateway and webhooks
- Cost calculation dashboard
- Usage alerts and notifications
- Subscription upgrade/downgrade flows

### Phase 10 Goals

**Current Gap:** Free unlimited access → Need subscription-based monetization

**Objectives:**
1. Integrate Stripe subscription billing (3 tiers: Free/Pro/Enterprise)
2. Implement API metering and usage tracking
3. Add rate limiting based on subscription tier
4. Build cost calculation dashboard for users
5. Set up webhook handling for subscription events
6. Create upgrade/downgrade flows with prorated billing

### Why This Matters

**Business Impact:**
- Enables sustainable revenue model
- Scales with user value (API usage-based pricing)
- Professional billing infrastructure
- Self-service subscription management
- Transparent usage monitoring

**Technical Impact:**
- Demonstrates Stripe integration expertise
- Shows understanding of SaaS billing patterns
- Implements rate limiting best practices
- Usage metering and cost attribution

**Portfolio Value:**
- Full-stack SaaS application
- Real payment processing integration
- Subscription lifecycle management
- Production-ready monetization

---

## Architecture Overview

### Subscription Tiers

| Tier | Price | Data Points/Month | Satellite Analyses/Month | Export Reports | Features |
|------|-------|-------------------|--------------------------|----------------|----------|
| **Free** | $0 | 50 | 10 | 2 | Basic maps, limited API |
| **Pro** | $29/mo | 500 | 100 | 20 | All features, priority support |
| **Enterprise** | $99/mo | Unlimited | Unlimited | Unlimited | White-label, API access, SLA |

### Usage Metering Strategy

**Metered Resources:**
1. **Data Points Created** - Each GPS-tagged environmental reading
2. **Satellite Analyses** - Copernicus API calls (NDVI, NDRE, EVI, MSI, etc.)
3. **Report Exports** - PDF/CSV downloads
4. **API Calls** - External API usage (future)

**Billing Approach:**
- Subscription with usage limits (not overage charges)
- Soft limits: Warning at 80% usage
- Hard limits: Block creation at 100% usage
- Monthly reset on billing cycle date

---

## Priority 1: Stripe Setup & Subscription Management (Days 1-2)

**Time:** 2 days  
**Goal:** Install Stripe, create subscription products, integrate checkout  
**Impact:** Users can subscribe to Pro/Enterprise tiers

### Task 1.1: Install Laravel Cashier (Stripe) ✅

**Why:** Official Laravel package for Stripe subscriptions

- ⏳ Install Laravel Cashier
  - `ddev composer require laravel/cashier`
  - Publish configuration: `ddev artisan vendor:publish --tag="cashier-config"`
  - Publish migrations: `ddev artisan vendor:publish --tag="cashier-migrations"`
- ⏳ Run migrations
  - `ddev artisan migrate`
  - Creates: `subscriptions`, `subscription_items`, `customers`, `payment_methods` tables
- ⏳ Add `Billable` trait to User model
  - `use Laravel\Cashier\Billable;` in `app/Models/User.php`
- ⏳ Configure Stripe API keys
  - Add to `.env`: `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
  - Get keys from Stripe Dashboard (Test mode initially)

**Deliverable:** Cashier installed, database ready, User model billable

**Testing:**
- ⏳ `test('user can be billable customer')`
- ⏳ `test('cashier migrations created required tables')`

**Documentation:**
- Search docs: `search-docs` queries: `["stripe cashier", "subscription billing", "cashier setup"]`

---

### Task 1.2: Create Stripe Products & Prices ✅

**Why:** Define subscription tiers in Stripe Dashboard

- ⏳ Create Stripe Products (via Stripe Dashboard)
  - **Product 1:** EcoSurvey Pro
    - Recurring price: $29/month
    - Price ID: `price_pro_monthly` (example)
  - **Product 2:** EcoSurvey Enterprise
    - Recurring price: $99/month
    - Price ID: `price_enterprise_monthly` (example)
- ⏳ Add price IDs to config
  - Create `config/subscriptions.php`
  - Store price IDs for each tier
  - Store usage limits per tier
- ⏳ Add helper method to User model
  - `subscriptionTier(): string` - Returns 'free', 'pro', or 'enterprise'
  - `hasActivePlan(string $tier): bool`
  - `canCreateDataPoint(): bool` - Checks usage limits
  - `canRunSatelliteAnalysis(): bool`

**Deliverable:** Stripe products configured, config file created, User model helpers

**Testing:**
- ⏳ `test('user subscription tier defaults to free')`
- ⏳ `test('user can check subscription tier')`
- ⏳ `test('free tier has usage limits')`

**Files:**
- `config/subscriptions.php` (new)
- `app/Models/User.php` (edit)

---

### Task 1.3: Build Subscription Checkout Flow (Volt) ✅

**Why:** Users need UI to subscribe

- ⏳ Create Volt component: `resources/views/livewire/billing/subscription-plans.blade.php`
  - Display 3 pricing cards (Free, Pro, Enterprise)
  - Show features per tier
  - "Subscribe" button for Pro/Enterprise
  - "Current Plan" badge for active subscription
  - Use Flux UI cards and buttons
- ⏳ Create Volt component: `resources/views/livewire/billing/checkout.blade.php`
  - Stripe Checkout redirect
  - Use `$user->newSubscription('default', $priceId)->checkout()`
  - Success/cancel URLs
  - Loading state with `wire:loading`
- ⏳ Add routes
  - `Route::get('/billing/plans', SubscriptionPlans::class)->name('billing.plans')`
  - `Route::get('/billing/checkout/{plan}', Checkout::class)->name('billing.checkout')`
  - `Route::get('/billing/success', ...)->name('billing.success')`
  - `Route::get('/billing/cancel', ...)->name('billing.cancel')`
- ⏳ Add navigation link
  - "Billing" in main navigation (resources/views/layouts/navigation.blade.php)

**Deliverable:** Working subscription checkout UI

**Testing:**
- ⏳ `test('displays subscription plans')`
- ⏳ `test('redirects to stripe checkout')`
- ⏳ `test('handles checkout success')`
- ⏳ `test('handles checkout cancel')`

**Browser Testing:**
- ⏳ Full checkout flow (test mode with Stripe test cards)

**Files:**
- `resources/views/livewire/billing/subscription-plans.blade.php` (new)
- `resources/views/livewire/billing/checkout.blade.php` (new)
- `routes/web.php` (edit)
- `resources/views/layouts/navigation.blade.php` (edit)

---

### Task 1.4: Stripe Webhook Integration ✅

**Why:** Handle subscription lifecycle events (created, updated, cancelled)

- ⏳ Register webhook endpoint
  - Route already provided by Cashier: `/stripe/webhook`
  - Register in Stripe Dashboard → Developers → Webhooks
  - Get webhook signing secret → Add to `.env` as `STRIPE_WEBHOOK_SECRET`
- ⏳ Select webhook events to listen for
  - `customer.subscription.created`
  - `customer.subscription.updated`
  - `customer.subscription.deleted`
  - `invoice.payment_succeeded`
  - `invoice.payment_failed`
- ⏳ Create event listeners (optional - Cashier handles most)
  - `app/Listeners/HandleSubscriptionCancelled.php`
  - `app/Listeners/HandlePaymentFailed.php`
  - Send email notifications on payment failure
- ⏳ Test webhook locally
  - Use Stripe CLI: `stripe listen --forward-to https://ecosurvey.ddev.site/stripe/webhook`
  - Trigger test events: `stripe trigger customer.subscription.created`

**Deliverable:** Webhooks registered, events handled

**Testing:**
- ⏳ `test('webhook handles subscription created')`
- ⏳ `test('webhook handles subscription cancelled')`
- ⏳ `test('webhook handles payment failed')`

**Files:**
- `app/Listeners/HandleSubscriptionCancelled.php` (new)
- `app/Listeners/HandlePaymentFailed.php` (new)
- `app/Providers/EventServiceProvider.php` (edit)

---

## Priority 2: API Metering & Usage Tracking (Days 3-4)

**Time:** 2 days  
**Goal:** Track usage per user per billing cycle, enforce limits  
**Impact:** Users can monitor usage, system enforces tier limits

### Task 2.1: Create Usage Tracking Service ✅

**Why:** Centralize usage tracking logic

- ⏳ Create `app/Services/UsageTrackingService.php`
  - `recordDataPointCreation(User $user): bool` - Increment counter
  - `recordSatelliteAnalysis(User $user, string $index): bool`
  - `recordReportExport(User $user, string $format): bool`
  - `getCurrentUsage(User $user): array` - Returns usage for current billing cycle
  - `getRemainingQuota(User $user, string $resource): int`
  - `canPerformAction(User $user, string $resource): bool` - Check if under limit
  - `getBillingCycleStart(User $user): Carbon` - First of month or subscription start date
  - `getBillingCycleEnd(User $user): Carbon`
- ⏳ Use `api_usages` table (already exists from Phase 9)
  - Add columns if needed: `billing_cycle_start`, `billing_cycle_end`
  - OR create new `usage_meters` table for cleaner separation
- ⏳ Cache usage counts (Redis/file cache)
  - Cache key: `usage:{user_id}:{resource}:{cycle_start}`
  - TTL: Until end of billing cycle

**Deliverable:** Usage tracking service with quota enforcement

**Testing:**
- ⏳ `test('records data point creation')`
- ⏳ `test('records satellite analysis')`
- ⏳ `test('calculates current usage correctly')`
- ⏳ `test('enforces free tier limits')`
- ⏳ `test('pro tier has higher limits')`
- ⏳ `test('enterprise has unlimited usage')`

**Files:**
- `app/Services/UsageTrackingService.php` (new)
- Migration for `usage_meters` table (new) OR update `api_usages`

---

### Task 2.2: Integrate Usage Tracking into Features ✅

**Why:** Automatically track when users create data points, run analyses, etc.

- ⏳ Data Point Creation
  - Edit `resources/views/livewire/data-collection/reading-form.blade.php`
  - Before `DataPoint::create()`, check: `UsageTrackingService::canPerformAction($user, 'data_points')`
  - If limit reached, show error: "You've reached your monthly limit. Upgrade to Pro."
  - After creation, call: `UsageTrackingService::recordDataPointCreation($user)`
- ⏳ Satellite Analysis
  - Edit `app/Jobs/EnrichWithSatelliteData.php`
  - Before API call, check: `UsageTrackingService::canPerformAction($user, 'satellite_analyses')`
  - Record each index fetch: `recordSatelliteAnalysis($user, 'ndvi')`
- ⏳ Report Export
  - Edit PDF export controller/action
  - Check: `UsageTrackingService::canPerformAction($user, 'report_exports')`
  - Record: `recordReportExport($user, 'pdf')`

**Deliverable:** Usage tracking integrated into all metered features

**Testing:**
- ⏳ `test('free user blocked at 50 data points')`
- ⏳ `test('pro user can create 500 data points')`
- ⏳ `test('satellite analysis blocked at limit')`
- ⏳ `test('usage resets on new billing cycle')`

**Files:**
- `resources/views/livewire/data-collection/reading-form.blade.php` (edit)
- `app/Jobs/EnrichWithSatelliteData.php` (edit)
- PDF export file (edit)

---

### Task 2.3: Build Usage Dashboard (Volt + Filament Widget) ✅

**Why:** Users need visibility into their usage and quota

- ⏳ Create Volt component: `resources/views/livewire/billing/usage-dashboard.blade.php`
  - Display current billing cycle dates
  - Show usage per resource with progress bars
    - Data Points: 45/50 (90%)
    - Satellite Analyses: 8/10 (80%)
    - Report Exports: 1/2 (50%)
  - Color-code: Green (<50%), Yellow (50-80%), Red (>80%), Gray (at limit)
  - "Upgrade to Pro" CTA if approaching limits
  - Chart showing usage over time (Chart.js line chart)
- ⏳ Create Filament widget for admin panel
  - `app/Filament/Widgets/UsageStatsWidget.php`
  - Show total usage across all users
  - Revenue dashboard (total MRR, subscriber count)
  - Top users by usage
- ⏳ Add route
  - `Route::get('/billing/usage', UsageDashboard::class)->name('billing.usage')`

**Deliverable:** User-facing usage dashboard and admin revenue widget

**Testing:**
- ⏳ `test('displays current usage correctly')`
- ⏳ `test('shows warning at 80% usage')`
- ⏳ `test('displays upgrade CTA when at limit')`

**Browser Testing:**
- ⏳ Usage dashboard UI (various usage levels)

**Files:**
- `resources/views/livewire/billing/usage-dashboard.blade.php` (new)
- `app/Filament/Widgets/UsageStatsWidget.php` (new)
- `routes/web.php` (edit)

---

## Priority 3: Subscription Management UI (Day 5)

**Time:** 1 day  
**Goal:** Users can manage subscriptions (upgrade, downgrade, cancel)  
**Impact:** Self-service subscription lifecycle

### Task 3.1: Build Subscription Management Page (Volt) ✅

**Why:** Users need to manage their subscriptions

- ⏳ Create Volt component: `resources/views/livewire/billing/manage-subscription.blade.php`
  - Display current subscription details
    - Plan name (Pro / Enterprise)
    - Billing cycle (Monthly)
    - Next billing date
    - Payment method (last 4 digits)
  - "Update Payment Method" button
    - Use `$user->redirectToBillingPortal()` (Cashier method)
  - "Upgrade to Enterprise" / "Downgrade to Pro" buttons
    - Use `$user->subscription('default')->swap($newPriceId)`
  - "Cancel Subscription" button
    - Use `$user->subscription('default')->cancel()`
    - Show confirmation modal
    - Option: Cancel immediately vs. cancel at period end
  - Display invoices
    - Use `$user->invoices()` (Cashier method)
    - Download invoice PDFs
- ⏳ Add route
  - `Route::get('/billing/manage', ManageSubscription::class)->name('billing.manage')`

**Deliverable:** Full subscription management UI

**Testing:**
- ⏳ `test('displays current subscription')`
- ⏳ `test('can upgrade subscription')`
- ⏳ `test('can downgrade subscription')`
- ⏳ `test('can cancel subscription')`
- ⏳ `test('can view invoices')`

**Browser Testing:**
- ⏳ Full subscription management flow

**Files:**
- `resources/views/livewire/billing/manage-subscription.blade.php` (new)
- `routes/web.php` (edit)

---

### Task 3.2: Handle Subscription Lifecycle Events ✅

**Why:** Different behavior for active, cancelled, expired subscriptions

- ⏳ Update User model helpers
  - `hasActiveSubscription(): bool` - Uses `$user->subscribed('default')`
  - `onGracePeriod(): bool` - Cancelled but still active until period end
  - `subscriptionEndsAt(): ?Carbon` - When subscription expires
- ⏳ Restrict features based on subscription status
  - Show "Reactivate Subscription" banner if cancelled
  - Block feature access if expired (treat as free tier)
- ⏳ Send email notifications
  - Subscription activated
  - Subscription cancelled
  - Subscription about to expire (3 days before)
  - Payment failed

**Deliverable:** Proper subscription lifecycle handling

**Testing:**
- ⏳ `test('cancelled subscription on grace period still has access')`
- ⏳ `test('expired subscription treated as free tier')`
- ⏳ `test('sends cancellation email')`

**Files:**
- `app/Models/User.php` (edit)
- `app/Notifications/SubscriptionCancelled.php` (new)
- `app/Notifications/SubscriptionExpiring.php` (new)

---

## Priority 4: Cost Calculation & Alerts (Day 6)

**Time:** 1 day  
**Goal:** Show cost attribution per resource, alert on high usage  
**Impact:** Transparency and proactive notifications

### Task 4.1: Build Cost Calculator Service ✅

**Why:** Show users what they're paying for

- ⏳ Create `app/Services/CostCalculatorService.php`
  - `calculateMonthlyCost(User $user): array` - Breakdown by resource
  - `getEstimatedCost(User $user): float` - Projected cost this cycle
  - `getCostPerResource(User $user, string $resource): float`
  - For free tier: Show "You saved $X by staying under limits"
  - For paid tier: Show "You used $X of your $29/mo plan"
- ⏳ Display cost breakdown
  - Add to `billing/usage-dashboard.blade.php`
  - Show: "Data Points: 45 ($0.00 - included in plan)"
  - Show: "Satellite Analyses: 8 ($0.00 - included in plan)"
  - For overage (future): "Extra Data Points: 5 ($2.50)"

**Deliverable:** Cost calculation service with UI

**Testing:**
- ⏳ `test('calculates monthly cost for free tier')`
- ⏳ `test('calculates monthly cost for pro tier')`
- ⏳ `test('shows cost savings for free tier')`

**Files:**
- `app/Services/CostCalculatorService.php` (new)
- `resources/views/livewire/billing/usage-dashboard.blade.php` (edit)

---

### Task 4.2: Implement Usage Alerts ✅

**Why:** Warn users before hitting limits

- ⏳ Create job: `app/Jobs/CheckUsageThresholds.php`
  - Run daily (scheduled command)
  - Check all users' usage levels
  - Send alert at 80%, 90%, 100% of quota
- ⏳ Create notification: `app/Notifications/UsageThresholdReached.php`
  - Email: "You've used 80% of your data points quota"
  - CTA: "Upgrade to Pro for 10x more data points"
  - Database notification (show in UI)
- ⏳ Schedule job
  - Add to `routes/console.php`: `Schedule::job(new CheckUsageThresholds)->daily()`
- ⏳ Display notifications in UI
  - Add notification badge to navigation
  - Show recent alerts on usage dashboard

**Deliverable:** Proactive usage alerts

**Testing:**
- ⏳ `test('sends alert at 80% usage')`
- ⏳ `test('sends alert at 100% usage')`
- ⏳ `test('does not send duplicate alerts')`

**Files:**
- `app/Jobs/CheckUsageThresholds.php` (new)
- `app/Notifications/UsageThresholdReached.php` (new)
- `routes/console.php` (edit)

---

## Priority 5: Rate Limiting & Security (Day 7)

**Time:** 1 day  
**Goal:** Prevent abuse, enforce tier-based rate limits  
**Impact:** System stability and fair usage

### Task 5.1: Implement Tier-Based Rate Limiting ✅

**Why:** Prevent API abuse, enforce fair usage

- ⏳ Create rate limiting middleware
  - `app/Http/Middleware/SubscriptionRateLimiter.php`
  - Check user's subscription tier
  - Apply different rate limits:
    - Free: 60 requests/hour
    - Pro: 300 requests/hour
    - Enterprise: 1000 requests/hour
  - Return 429 Too Many Requests if exceeded
- ⏳ Apply middleware to API routes
  - Edit `bootstrap/app.php` or route groups
  - Apply to data collection routes
  - Apply to satellite analysis routes
- ⏳ Use Laravel's built-in rate limiting
  - `RateLimiter::for('free-tier', fn () => Limit::perHour(60))`
  - Dynamic limits based on user tier

**Deliverable:** Tier-based rate limiting

**Testing:**
- ⏳ `test('free tier limited to 60 requests per hour')`
- ⏳ `test('pro tier limited to 300 requests per hour')`
- ⏳ `test('enterprise tier limited to 1000 requests per hour')`
- ⏳ `test('returns 429 when limit exceeded')`

**Files:**
- `app/Http/Middleware/SubscriptionRateLimiter.php` (new)
- `bootstrap/app.php` (edit)

---

### Task 5.2: Add Subscription Checks to Forms ✅

**Why:** Frontend validation before submission

- ⏳ Update Volt components
  - `data-collection/reading-form.blade.php`
  - Check `$user->canCreateDataPoint()` before showing form
  - Show upgrade prompt if at limit
  - Disable submit button with tooltip: "Upgrade to Pro to continue"
- ⏳ Display quota in form header
  - "Data Points: 45/50 remaining"
  - Progress bar
  - Link to usage dashboard
- ⏳ JavaScript validation
  - Check quota before allowing form submission
  - Show modal: "You've reached your limit. Upgrade to Pro?"

**Deliverable:** Form-level subscription checks

**Testing:**
- ⏳ `test('form shows upgrade prompt at limit')`
- ⏳ `test('submit button disabled at limit')`

**Browser Testing:**
- ⏳ Form behavior at different usage levels

**Files:**
- `resources/views/livewire/data-collection/reading-form.blade.php` (edit)

---

### Task 5.3: Admin Tools for Subscription Management ✅

**Why:** Support team needs tools to manage subscriptions

- ⏳ Create Filament resource: `app/Filament/Resources/SubscriptionResource.php`
  - List all subscriptions (active, cancelled, expired)
  - Filter by plan, status
  - Actions: Cancel, refund, extend
  - Show usage stats per user
- ⏳ Add Filament widget: `UsageByPlanWidget.php`
  - Show usage distribution by plan
  - Identify power users (high usage)
  - Show churned users (cancelled subscriptions)
- ⏳ Add manual override for quotas
  - Admin can grant extra quota to specific users
  - Add `quota_overrides` table
  - Store: user_id, resource, extra_amount, expires_at

**Deliverable:** Admin subscription management tools

**Testing:**
- ⏳ `test('admin can view all subscriptions')`
- ⏳ `test('admin can grant quota override')`

**Files:**
- `app/Filament/Resources/SubscriptionResource.php` (new)
- `app/Filament/Widgets/UsageByPlanWidget.php` (new)
- Migration for `quota_overrides` table (new)

---

## Testing Strategy

### Feature Tests

**Subscription Management:**
- ⏳ `tests/Feature/SubscriptionCheckoutTest.php` (15 tests)
  - Checkout flow
  - Success/cancel handling
  - Webhook processing
  - Plan upgrades/downgrades

**Usage Tracking:**
- ⏳ `tests/Feature/UsageTrackingTest.php` (12 tests)
  - Recording usage
  - Quota enforcement
  - Billing cycle calculations
  - Cache invalidation

**Rate Limiting:**
- ⏳ `tests/Feature/RateLimitingTest.php` (8 tests)
  - Tier-based limits
  - 429 responses
  - Limit reset after time window

### Unit Tests

**Services:**
- ⏳ `tests/Unit/UsageTrackingServiceTest.php` (10 tests)
- ⏳ `tests/Unit/CostCalculatorServiceTest.php` (6 tests)

### Browser Tests (Pest 4)

**Critical Flows:**
- ⏳ `tests/Browser/SubscriptionFlowTest.php`
  - View pricing page
  - Click subscribe button
  - Complete Stripe checkout (test mode)
  - Return to success page
  - View subscription in account
- ⏳ `tests/Browser/UsageLimitTest.php`
  - Create data points until limit
  - See upgrade prompt
  - Verify submit button disabled
  - Click upgrade link

**Target:** 50+ tests, 90%+ coverage for billing features

---

## Database Schema Changes

### New Tables

**1. `usage_meters` (if not using `api_usages`)**
```sql
CREATE TABLE usage_meters (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id),
    resource VARCHAR(50), -- 'data_points', 'satellite_analyses', 'report_exports'
    count INT DEFAULT 0,
    billing_cycle_start DATE,
    billing_cycle_end DATE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(user_id, resource, billing_cycle_start)
);
```

**2. `quota_overrides`**
```sql
CREATE TABLE quota_overrides (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id),
    resource VARCHAR(50),
    extra_amount INT,
    reason TEXT,
    granted_by BIGINT REFERENCES users(id), -- admin user_id
    expires_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Cashier tables:** (created by Laravel Cashier migrations)
- `subscriptions`
- `subscription_items`
- `customers`
- `payment_methods`

---

## Configuration Files

### `config/subscriptions.php` (NEW)

```php
<?php

return [
    'plans' => [
        'free' => [
            'name' => 'Free',
            'price' => 0,
            'stripe_price_id' => null,
            'limits' => [
                'data_points' => 50,
                'satellite_analyses' => 10,
                'report_exports' => 2,
            ],
            'features' => [
                'Basic maps',
                'Limited satellite data',
                'Community support',
            ],
        ],
        'pro' => [
            'name' => 'Pro',
            'price' => 29,
            'stripe_price_id' => env('STRIPE_PRICE_PRO'),
            'limits' => [
                'data_points' => 500,
                'satellite_analyses' => 100,
                'report_exports' => 20,
            ],
            'features' => [
                'All maps and visualization',
                'Full satellite indices (7)',
                'Advanced analytics',
                'Priority support',
                'Export to CSV/PDF',
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price' => 99,
            'stripe_price_id' => env('STRIPE_PRICE_ENTERPRISE'),
            'limits' => [
                'data_points' => PHP_INT_MAX,
                'satellite_analyses' => PHP_INT_MAX,
                'report_exports' => PHP_INT_MAX,
            ],
            'features' => [
                'Unlimited everything',
                'API access',
                'White-label option',
                'Custom integrations',
                'SLA guarantee',
                'Dedicated support',
            ],
        ],
    ],

    'rate_limits' => [
        'free' => 60, // requests per hour
        'pro' => 300,
        'enterprise' => 1000,
    ],
];
```

### `.env` additions

```env
# Stripe API Keys (Test Mode)
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Stripe Price IDs (from Stripe Dashboard)
STRIPE_PRICE_PRO=price_...
STRIPE_PRICE_ENTERPRISE=price_...
```

---

## Key Commands

```powershell
# Install Cashier
ddev composer require laravel/cashier

# Run migrations
ddev artisan migrate

# Test webhook locally (requires Stripe CLI)
stripe listen --forward-to https://ecosurvey.ddev.site/stripe/webhook
stripe trigger customer.subscription.created

# Check subscription status (Tinker)
ddev artisan tinker
$user = User::find(1);
$user->subscribed('default'); // true/false
$user->subscription('default')->stripe_price; // price_...

# Run tests
ddev artisan test --filter=Subscription
ddev artisan test --filter=Usage

# Format code
ddev pint --dirty
```

---

## Success Metrics

**Business Metrics:**
- ✅ Stripe integration functional (test mode)
- ✅ Subscriptions can be created/updated/cancelled
- ✅ Webhooks processed successfully
- ✅ Usage tracked accurately per billing cycle
- ✅ Rate limits enforced per tier

**Technical Metrics:**
- ✅ 50+ tests passing (subscriptions, usage, rate limits)
- ✅ 90%+ code coverage for billing features
- ✅ No N+1 queries in usage calculations
- ✅ Cache hit rate >80% for usage queries

**User Experience:**
- ✅ Checkout flow completes in <60 seconds
- ✅ Usage dashboard loads in <1 second
- ✅ Clear upgrade prompts when approaching limits
- ✅ Transparent cost breakdown visible

---

## Deliverables

**Day 1-2:**
- ✅ Laravel Cashier installed
- ✅ Stripe products configured
- ✅ Subscription checkout UI
- ✅ Webhook integration

**Day 3-4:**
- ✅ Usage tracking service
- ✅ Feature integration (data points, satellite, exports)
- ✅ Usage dashboard UI

**Day 5:**
- ✅ Subscription management page
- ✅ Upgrade/downgrade flows
- ✅ Cancellation handling

**Day 6:**
- ✅ Cost calculator
- ✅ Usage alerts and notifications

**Day 7:**
- ✅ Rate limiting middleware
- ✅ Form-level quota checks
- ✅ Admin subscription tools

**Final:**
- ✅ 50+ tests passing
- ✅ Documentation updated
- ✅ Ready for production Stripe account

---

## Risk Mitigation

**Stripe Integration Complexity:**
- Use official Laravel Cashier package (battle-tested)
- Follow Stripe best practices documentation
- Use test mode initially, production mode only after thorough testing
- Search docs: `search-docs` queries for Cashier-specific issues

**Usage Tracking Accuracy:**
- Use database transactions for atomic increments
- Cache usage counts with proper invalidation
- Schedule daily job to verify counts vs actual records
- Log discrepancies for investigation

**Rate Limiting Bypass:**
- Implement both middleware and application-level checks
- Use Redis for distributed rate limiting (if scaling)
- Log suspicious activity (sudden spikes)
- Admin dashboard to monitor rate limit hits

**Webhook Reliability:**
- Verify webhook signatures (security)
- Use idempotency keys to prevent duplicate processing
- Log all webhook events for debugging
- Retry failed webhooks (Stripe automatically retries)

---

## Future Enhancements (Post-Phase 10)

**Overage Billing:**
- Charge per extra data point beyond quota
- Metered billing via Stripe Usage Records API
- Invoice at end of billing cycle

**Team Plans:**
- Multi-user subscriptions (shared quota)
- Team billing and management
- Role-based access control per team

**Annual Billing:**
- 20% discount for annual plans
- Add annual price IDs to Stripe
- Prorated upgrades from monthly to annual

**Custom Enterprise Contracts:**
- Custom pricing for large organizations
- Manually created subscriptions (no self-service)
- Negotiated SLAs

**Referral Program:**
- Give 1 month free for each referral
- Track referrals in database
- Auto-apply credits to subscription

---

## Phase 10 Timeline

| Day | Tasks | Deliverables |
|-----|-------|-------------|
| **Day 1** | Install Cashier, Configure Stripe products | Cashier installed, products in Stripe |
| **Day 2** | Build checkout UI, Webhook integration | Working subscription flow |
| **Day 3** | Usage tracking service, Feature integration | Usage recorded for all actions |
| **Day 4** | Usage dashboard UI | Users can see quota usage |
| **Day 5** | Subscription management, Upgrade/downgrade | Self-service subscription management |
| **Day 6** | Cost calculator, Usage alerts | Cost transparency, proactive notifications |
| **Day 7** | Rate limiting, Admin tools | Complete billing system, ready for production |

**Total:** 7 days (1 week)

---

## Post-Phase 10 Next Steps

**Phase 11: Real-time Collaboration**
- Laravel Echo + Pusher/Soketi
- Live map updates when teammates add data
- Activity feed
- Real-time notifications

**Phase 12: Testing & Deployment**
- Comprehensive test suite
- Browser tests for critical flows
- Production deployment (Railway/Render/DigitalOcean)
- Performance optimization
- Documentation finalization

---

**Status:** ⏸️ PENDING  
**Ready to start:** January 20, 2026  
**Target completion:** January 27, 2026

**Prerequisites:**
- ✅ All Phase 1-9 features complete
- ✅ API usage tracking infrastructure in place
- ✅ User authentication working
- ⏳ Stripe account created (test mode)
- ⏳ Stripe CLI installed (for local webhook testing)

**Blockers:** None identified

---

## Resources

**Laravel Cashier Documentation:**
- https://laravel.com/docs/12.x/billing

**Stripe Documentation:**
- https://stripe.com/docs/billing/subscriptions/overview
- https://stripe.com/docs/webhooks
- https://stripe.com/docs/testing

**Use `search-docs` tool:**
- `["stripe cashier", "subscription billing", "cashier webhooks"]`
- `["rate limiting", "throttle middleware"]`
- `["usage tracking", "metered billing"]`

**Stripe Test Cards:**
- Success: `4242 4242 4242 4242`
- Requires authentication: `4000 0025 0000 3155`
- Declined: `4000 0000 0000 9995`

---

**Last Updated:** January 20, 2026  
**Author:** AI Assistant (GitHub Copilot)  
**Based on:** Development-Roadmap.md, Laravel Boost Guidelines, Stripe Best Practices
