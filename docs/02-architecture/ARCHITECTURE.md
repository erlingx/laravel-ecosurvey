# EcoSurvey Architecture

**Complete system design and data flow documentation**

---

## 🏗️ System Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                         CLIENT LAYER                                 │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ Web Browser / Mobile App                                     │   │
│  │ ├─ Dashboard                                                │   │
│  │ ├─ Interactive Maps (Leaflet.js)                           │   │
│  │ ├─ Satellite Viewer                                        │   │
│  │ ├─ Data Entry Forms                                        │   │
│  │ └─ Subscription Management                                 │   │
│  └─────────────────────────────────────────────────────────────┘   │
└────────────────────────┬──────────────────────────────────────────┘
                         │
              HTTP/WebSocket (Livewire)
                         │
┌────────────────────────▼──────────────────────────────────────────┐
│                  APPLICATION LAYER (Laravel 12)                    │
│                                                                    │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ Livewire 3 + Volt Components                              │   │
│  │ ├─ Dashboard Component                                   │   │
│  │ ├─ Map Viewer Component                                 │   │
│  │ ├─ Satellite Analysis Component                         │   │
│  │ ├─ Data Entry Component                                 │   │
│  │ ├─ Subscription Component                               │   │
│  │ └─ Admin Filament Components                            │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                    │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ HTTP Controllers                                          │   │
│  │ ├─ CampaignController                                    │   │
│  │ ├─ SurveyDataController                                  │   │
│  │ ├─ SubscriptionController                                │   │
│  │ ├─ AnalyticsController                                   │   │
│  │ └─ SatelliteController                                   │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                    │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ Service Layer (Business Logic)                            │   │
│  │ ├─ SatelliteService                                      │   │
│  │ │  ├─ fetchSentinel2Images()                            │   │
│  │ │  ├─ calculateVegetationIndices()                      │   │
│  │ │  └─ storeImageData()                                  │   │
│  │ │                                                         │   │
│  │ ├─ UsageMetersService                                    │   │
│  │ │  ├─ trackDataPoint()                                  │   │
│  │ │  ├─ trackAnalysis()                                   │   │
│  │ │  ├─ checkLimits()                                     │   │
│  │ │  └─ getCycleUsage()                                   │   │
│  │ │                                                         │   │
│  │ ├─ AnalyticsService                                      │   │
│  │ │  ├─ generateHeatmap()                                 │   │
│  │ │  ├─ getTimeSeriesData()                               │   │
│  │ │  ├─ calculateStatistics()                             │   │
│  │ │  └─ exportReport()                                    │   │
│  │ │                                                         │   │
│  │ ├─ StripeSubscriptionService                             │   │
│  │ │  ├─ createSubscription()                              │   │
│  │ │  ├─ cancelSubscription()                              │   │
│  │ │  └─ resumeSubscription()                              │   │
│  │ │                                                         │   │
│  │ └─ MailService                                           │   │
│  │    ├─ sendUsageAlert()                                  │   │
│  │    └─ sendReportNotification()                          │   │
│  │                                                          │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                    │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ Eloquent Models & Relationships                           │   │
│  │ ├─ User                                                  │   │
│  │ │  ├─ campaigns()      [HasMany]                        │   │
│  │ │  ├─ surveys()        [HasManyThrough]                 │   │
│  │ │  ├─ subscription()   [HasOne]                         │   │
│  │ │  └─ usageMeters()    [HasMany]                        │   │
│  │ │                                                         │   │
│  │ ├─ Campaign                                              │   │
│  │ │  ├─ user()           [BelongsTo]                      │   │
│  │ │  ├─ surveys()        [HasMany]                        │   │
│  │ │  ├─ satelliteImages()  [HasMany]                      │   │
│  │ │  └─ surveyZones()    [HasMany]                        │   │
│  │ │                                                         │   │
│  │ ├─ Survey (Environmental Reading)                        │   │
│  │ │  ├─ campaign()       [BelongsTo]                      │   │
│  │ │  ├─ user()           [BelongsTo]                      │   │
│  │ │  ├─ location:Point   [PostGIS]                        │   │
│  │ │  └─ photos()         [HasMany]                        │   │
│  │ │                                                         │   │
│  │ ├─ SatelliteImage                                        │   │
│  │ │  ├─ campaign()       [BelongsTo]                      │   │
│  │ │  ├─ geometry:Polygon [PostGIS]                        │   │
│  │ │  ├─ ndvi, ndre, evi, etc. (Indices)                  │   │
│  │ │  └─ capturedAt      [Timestamp]                       │   │
│  │ │                                                         │   │
│  │ ├─ SurveyZone                                            │   │
│  │ │  ├─ campaign()       [BelongsTo]                      │   │
│  │ │  ├─ boundary:Polygon [PostGIS]                        │   │
│  │ │  └─ surveys()        [HasMany - Spatial Query]       │   │
│  │ │                                                         │   │
│  │ ├─ Subscription                                          │   │
│  │ │  ├─ user()           [BelongsTo]                      │   │
│  │ │  ├─ tier             [Free/Pro/Enterprise]            │   │
│  │ │  └─ billingCycle     [Carbon Date]                    │   │
│  │ │                                                         │   │
│  │ └─ UsageMeter                                            │   │
│  │    ├─ user()           [BelongsTo]                      │   │
│  │    ├─ type             [data_point/analysis/export]     │   │
│  │    └─ quantity         [Counted]                        │   │
│  │                                                          │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                    │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ Job Queue (Background Processing)                        │   │
│  │ ├─ ProcessSatelliteImages                                │   │
│  │ ├─ SyncStripeSubscriptions                               │   │
│  │ ├─ GenerateReports                                       │   │
│  │ ├─ CalculateStatistics                                   │   │
│  │ └─ SendNotifications                                     │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                    │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ Middleware                                                │   │
│  │ ├─ SubscriptionRateLimiter (Tier-based)                  │   │
│  │ ├─ Authenticate                                          │   │
│  │ ├─ VerifySubscriptionStatus                              │   │
│  │ ├─ EnforceUsageLimits                                    │   │
│  │ └─ LogAnalytics                                          │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                    │
└────────────────────────┬──────────────────────────────────────────┘
          ┌─────────────┼─────────────┬──────────────────┐
          │             │             │                  │
          ▼             ▼             ▼                  ▼
    ┌──────────┐  ┌─────────┐  ┌──────────┐      ┌─────────────┐
    │PostgreSQL   │  Redis    │  │  Queue   │      │External API │
    │ + PostGIS   │  (Cache)  │  │(Database)│      │  Integrations
    └──────────┘  └─────────┘  └──────────┘      └─────────────┘
         │             │             │                   │
         ▼             ▼             ▼                   ▼
    ┌──────────────────────────────────┐   ┌─────────────────────┐
    │  DATA LAYER                      │   │  EXTERNAL SERVICES  │
    │                                  │   │                     │
    │ Tables:                          │   │ ├─ Copernicus API   │
    │ ├─ users                         │   │ ├─ NASA EONET       │
    │ ├─ campaigns                     │   │ ├─ OpenWeatherMap   │
    │ ├─ surveys                       │   │ ├─ WAQI             │
    │ ├─ satellite_images              │   │ └─ Stripe           │
    │ ├─ survey_zones                  │   │                     │
    │ ├─ subscriptions                 │   └─────────────────────┘
    │ ├─ usage_meters                  │
    │ ├─ photos                        │
    │ └─ flags (QA)                    │
    │                                  │
    │ PostGIS Indexes:                 │
    │ ├─ GIST(location)                │
    │ ├─ GIST(boundary)                │
    │ └─ BRIN(satellite coverage)      │
    │                                  │
    │ Cache (Redis):                   │
    │ ├─ analytics:heatmap:{id}        │
    │ ├─ usage:meter:{user_id}         │
    │ └─ satellite:indices:{image_id}  │
    │                                  │
    └──────────────────────────────────┘
```

---

## 📊 Data Flow Diagrams

### 1. Data Collection Flow

```
User Creates Survey Reading
        │
        ▼
┌─────────────────────────────┐
│ Survey Form (Livewire)      │
│ ├─ GPS Location (Point)     │
│ ├─ Environmental Metrics    │
│ ├─ Photo Upload             │
│ └─ Timestamp                │
└──────────┬──────────────────┘
           │
           ▼
┌─────────────────────────────┐
│ Validate & Store            │
│ ├─ Check rate limit         │
│ ├─ Verify subscription      │
│ ├─ Track usage meter        │
│ └─ Save to Database         │
└──────────┬──────────────────┘
           │
           ▼
┌─────────────────────────────┐
│ PostGIS Processing          │
│ ├─ Validate geometry        │
│ ├─ Create spatial index     │
│ ├─ Check zone containment   │
│ └─ Update heatmap cache     │
└──────────┬──────────────────┘
           │
           ▼
┌─────────────────────────────┐
│ Update Dashboard            │
│ ├─ Refresh survey list      │
│ ├─ Update map markers       │
│ ├─ Show success message     │
│ └─ Display usage meter      │
└─────────────────────────────┘
```

### 2. Satellite Data Processing Flow

```
Daily Scheduler (Automated)
        │
        ▼
┌──────────────────────────────┐
│ ProcessSatelliteImages Job   │
│ Triggered: 02:00 UTC         │
└──────────┬───────────────────┘
           │
           ▼
┌──────────────────────────────┐
│ Fetch from Copernicus API    │
│ ├─ Query Sentinel-2 imagery  │
│ ├─ Filter by campaign bounds │
│ ├─ Check cloud coverage      │
│ └─ Download GeoTIFF tiles    │
└──────────┬───────────────────┘
           │
           ▼
┌──────────────────────────────┐
│ Calculate Indices (Parallel) │
│ ├─ NDVI (Red, NIR bands)     │
│ ├─ GNDVI (Green, NIR)        │
│ ├─ NDRE (Red, Red Edge)      │
│ ├─ EVI (Enhanced)            │
│ ├─ SAVI (Soil-adjusted)      │
│ ├─ OSAVI (Optimized)         │
│ └─ CVI (Chlorophyll)         │
└──────────┬───────────────────┘
           │
           ▼
┌──────────────────────────────┐
│ Store in PostGIS             │
│ ├─ Create polygon geometry   │
│ ├─ Store raster data         │
│ ├─ Attach metadata           │
│ └─ Index with GIST/BRIN      │
└──────────┬───────────────────┘
           │
           ▼
┌──────────────────────────────┐
│ Generate Visualizations      │
│ ├─ Render heatmap            │
│ ├─ Create time-series        │
│ ├─ Cache for 1 hour          │
│ └─ Alert users (if anomaly)  │
└──────────┬───────────────────┘
           │
           ▼
┌──────────────────────────────┐
│ Notify Users                 │
│ ├─ Dashboard updated         │
│ ├─ Send email summary        │
│ └─ Show satellite badge      │
└──────────────────────────────┘
```

### 3. Subscription & Billing Flow

```
User Initiates Checkout
        │
        ▼
┌─────────────────────────────┐
│ Stripe Checkout Session     │
│ ├─ Display billing period   │
│ ├─ Show tier features       │
│ ├─ Calculate cost            │
│ └─ Collect payment          │
└──────────┬──────────────────┘
           │
           ▼
┌─────────────────────────────┐
│ Payment Processing          │
│ ├─ Validate card            │
│ ├─ Charge subscription fee  │
│ └─ Create invoice           │
└──────────┬──────────────────┘
           │
           ├─ Webhook (Prod) ──────────┐
           │                            │
           └─ Success Page (Dev) ───────┤
                                        │
                                        ▼
                        ┌───────────────────────────┐
                        │ SyncStripeSubscriptions   │
                        │ ├─ Fetch customer data    │
                        │ ├─ Verify subscription    │
                        │ ├─ Detect tier upgrade    │
                        │ ├─ Reset usage meters     │
                        │ └─ Set billing cycle      │
                        └───────────┬───────────────┘
                                    │
                                    ▼
                        ┌───────────────────────────┐
                        │ Activate Features         │
                        │ ├─ Unlock tier limits     │
                        │ ├─ Enable satellite API   │
                        │ ├─ Allow exports          │
                        │ └─ Update dashboard       │
                        └───────────┬───────────────┘
                                    │
                                    ▼
                        ┌───────────────────────────┐
                        │ Send Confirmation Email   │
                        │ ├─ Receipt                │
                        │ ├─ Tier features         │
                        │ ├─ Usage limits          │
                        │ └─ Next billing date     │
                        └───────────────────────────┘

Every Hour (Automatic Check)
        │
        ▼
┌─────────────────────────────┐
│ Check Billing Status        │
│ ├─ Verify subscription      │
│ ├─ Compare with Stripe      │
│ ├─ Handle grace periods     │
│ └─ Log discrepancies        │
└─────────────────────────────┘
```

### 4. Usage Metering & Enforcement

```
User Action (Survey, Analysis, Export)
        │
        ▼
┌─────────────────────────────┐
│ Check Rate Limit            │
│ (Tier-based middleware)     │
│ ├─ Get user's subscription  │
│ ├─ Look up tier limits      │
│ ├─ Count requests/hour      │
│ └─ Compare to threshold     │
└─────────┬───────────────────┘
          │
          ├─ Within Limit ──┐
          │                 │
          └─ Exceeded ──┐   │
                        │   │
                        ▼   ▼
                 ┌────────────────────┐
                 │ Return 429         │ Return 200
                 │ Too Many Requests  │ (Proceed)
                 └────────────────────┘    │
                                           ▼
                                ┌─────────────────────┐
                                │ Track Usage Meter   │
                                │ ├─ Type: data_point │
                                │ ├─ Quantity: 1      │
                                │ ├─ Period: Nov 2025 │
                                │ └─ Cache: Redis TTL │
                                └────────┬────────────┘
                                         │
                                         ▼
                                ┌─────────────────────┐
                                │ Check Hard Limit    │
                                │ (Stored daily)      │
                                │ ├─ Get monthly use  │
                                │ ├─ Compare to tier  │
                                │ └─ Block if over    │
                                └────────┬────────────┘
                                         │
                                ┌────────────────────┐
                                │ Dashboard Update   │
                                │ ├─ Usage bars      │
                                │ ├─ % Progress      │
                                │ └─ Warnings        │
                                └────────────────────┘
```

---

## 🗄️ Database Schema Highlights

### PostGIS Spatial Features

```sql
-- Survey Location (Point Geometry)
surveys
├── id
├── campaign_id
├── user_id
├── location (Point) ← PostGIS
├── temperature
├── humidity
├── metadata (JSON)
├── photo_count
└── created_at

-- Survey Zone (Polygon Geometry)
survey_zones
├── id
├── campaign_id
├── name
├── boundary (Polygon) ← PostGIS
├── created_at

-- Satellite Image (Raster + Polygon)
satellite_images
├── id
├── campaign_id
├── coverage_area (Polygon) ← PostGIS
├── ndvi (Float array)
├── gndvi (Float array)
├── ndre (Float array)
├── evi (Float array)
├── savi (Float array)
├── osavi (Float array)
├── cvi (Float array)
├── captured_at
└── processed_at

-- Usage Meters
usage_meters
├── id
├── user_id
├── type (data_point/analysis/export)
├── quantity
├── billing_cycle_start
├── billing_cycle_end
└── created_at

-- Subscriptions
subscriptions
├── id
├── user_id
├── stripe_id
├── tier (free/pro/enterprise)
├── status (active/grace_period/cancelled)
├── billing_cycle_starts_at
├── billing_cycle_ends_at
└── created_at
```

### Query Examples

```sql
-- Find surveys within a zone
SELECT surveys.* FROM surveys
WHERE ST_Contains(zone.boundary, surveys.location)

-- Distance between user and nearest survey
SELECT surveys.*,
       ST_Distance(surveys.location, point(-118.2437, 34.0522)) as distance
FROM surveys
ORDER BY distance
LIMIT 10

-- Aggregate satellite data per zone
SELECT 
  zones.id,
  AVG(images.ndvi) as avg_ndvi,
  AVG(images.evi) as avg_evi
FROM survey_zones zones
JOIN satellite_images images 
  ON ST_Intersects(zones.boundary, images.coverage_area)
WHERE images.captured_at > NOW() - INTERVAL '30 days'
GROUP BY zones.id
```

---

## 🔄 Subscription Tiers & Usage Limits

```
┌──────────────┬────────────┬──────────────┬─────────────┐
│ Feature      │ Guest      │ Free         │ Pro         │ Enterprise
├──────────────┼────────────┼──────────────┼─────────────┤
│ Monthly Cost │ Free       │ Free         │ $49         │ Custom
│ Data Points  │ 30/month   │ 100/month    │ 5,000/month │ Unlimited
│ Analyses     │ 1/month    │ 10/month     │ 100/month   │ Unlimited
│ Exports      │ 3/month    │ 10/month     │ Unlimited   │ Unlimited
│ Campaigns    │ 1          │ 3            │ Unlimited   │ Unlimited
│ Users        │ 1          │ 1            │ 5           │ Unlimited
│ Rate Limit   │ 30/hour    │ 60/hour      │ 300/hour    │ 1000/hour
│ Support      │ Community  │ Community    │ Email       │ Priority
└──────────────┴────────────┴──────────────┴─────────────┘
```

---

## 🔐 Security Architecture

```
┌────────────────────────────────────────────────────────┐
│             Authentication & Authorization              │
├────────────────────────────────────────────────────────┤
│                                                        │
│ 1. LOGIN LAYER                                         │
│    ├─ Email verification                              │
│    ├─ Password hashing (bcrypt)                       │
│    └─ Session management (database driver)            │
│                                                        │
│ 2. POLICY LAYER                                        │
│    ├─ Campaign ownership (canView, canEdit)           │
│    ├─ Subscription status (isSubscribed, hasTier)    │
│    ├─ Usage enforcement (checkLimits)                │
│    └─ Admin authorization (isAdmin)                   │
│                                                        │
│ 3. MIDDLEWARE LAYER                                    │
│    ├─ Authenticate (verify session)                   │
│    ├─ SubscriptionRateLimiter (tier-based)           │
│    ├─ VerifySubscriptionStatus (not expired)         │
│    ├─ EnforceUsageLimits (hard limit check)          │
│    └─ VerifyCsrfToken (form security)                │
│                                                        │
│ 4. DATA LAYER                                          │
│    ├─ SQL injection prevention (Eloquent)             │
│    ├─ XSS protection (Blade escaping)                 │
│    ├─ CSRF tokens on all forms                        │
│    └─ HTTPS/SSL in production                         │
│                                                        │
│ 5. PAYMENT LAYER                                       │
│    ├─ PCI-DSS compliance (Stripe)                     │
│    ├─ No card data stored locally                     │
│    ├─ Webhook signature verification                  │
│    └─ API key rotation                                │
│                                                        │
└────────────────────────────────────────────────────────┘
```

---

## 🚀 Deployment Architecture

```
┌─────────────────────────────────────────────────────────┐
│                   CI/CD Pipeline                         │
│                                                          │
│  1. GitHub Push                                          │
│     └─▶ GitHub Actions Workflow                         │
│         ├─ Run tests (Pest)                             │
│         ├─ Check code style (Pint)                      │
│         ├─ Static analysis (Larastan)                   │
│         └─ Build artifacts                              │
│                                                          │
│  2. Build & Deploy                                       │
│     └─▶ Railway / Render                                │
│         ├─ Docker build                                 │
│         ├─ Database migrations                          │
│         ├─ Asset compilation (Vite)                     │
│         └─ Health checks                                │
│                                                          │
│  3. Production Environment                               │
│     ├─ PostgreSQL 16 + PostGIS (managed)               │
│     ├─ Redis cache (managed)                            │
│     ├─ Stripe webhooks configured                       │
│     ├─ DNS & SSL (Let's Encrypt)                        │
│     └─ Monitoring & Logging                             │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

---

## 📈 Performance Optimization

### Caching Strategy

```
Layer 1: HTTP Cache Headers
  ├─ Static assets (far-future expires)
  └─ API responses (ETag-based)

Layer 2: Database Query Cache (Redis)
  ├─ analytics:heatmap:{id} → TTL: 1 hour
  ├─ usage:meter:{user_id} → TTL: 5 min
  ├─ satellite:indices:{image_id} → TTL: 24 hours
  └─ campaign:stats:{id} → TTL: 15 min

Layer 3: ORM Query Optimization
  ├─ Eager loading with ->with()
  ├─ Select only needed columns
  ├─ Use database-level aggregations
  └─ Denormalization for metrics

Layer 4: CDN (Optional)
  ├─ Satellite images
  ├─ Generated PDFs
  └─ Static assets
```

### Database Indexes

```
Critical Indexes Created:
  ├─ surveys.location (GIST - spatial)
  ├─ survey_zones.boundary (GIST - spatial)
  ├─ satellite_images.coverage_area (GIST - spatial)
  ├─ usage_meters.user_id, billing_cycle_start (Composite)
  ├─ subscriptions.user_id, status (Composite)
  ├─ campaigns.user_id (Regular)
  └─ surveys.campaign_id, created_at (Composite)
```

---

## 🎯 Technology Decision Matrix

| Decision | Choice | Why |
|----------|--------|-----|
| Backend Framework | Laravel 12 | Modern, batteries-included, excellent for SaaS |
| Frontend | Livewire 3 + Volt | Real-time updates without JavaScript framework |
| Database | PostgreSQL + PostGIS | Superior spatial query support |
| Caching | Redis | Fast, distributed cache layer |
| Payments | Stripe + Cashier | Industry standard, Laravel integration |
| Testing | Pest | Modern, expressive PHP testing |
| Styling | Tailwind v4 | Rapid UI development, dark mode |
| Maps | Leaflet.js | Lightweight, no API key overhead |
| Hosting | Railway/Render | Easy PostgreSQL+PostGIS setup, webhooks |

---

## 📊 Scalability Roadmap

### Current (MVP)
- Single PostgreSQL instance
- Redis for caching
- Queue worker (1 instance)
- ~1000 users

### Phase 2 (10k users)
- PostgreSQL read replicas
- Message queue (RabbitMQ/SQS)
- Multiple queue workers
- CDN for static assets
- Elasticsearch for analytics

### Phase 3 (100k+ users)
- Sharded PostgreSQL by user_id
- Microservices for satellite processing
- Separate analytics warehouse
- Real-time data pipeline (Kafka)
- Global CDN + edge computing

---

## 🔍 Monitoring & Observability

```
Application Monitoring
├─ Laravel Telescope (Development)
│  ├─ Query inspector
│  ├─ Request/response
│  └─ Job monitoring
│
├─ Error Tracking (Sentry - optional)
│  ├─ Exception tracking
│  ├─ Performance monitoring
│  └─ Release tracking
│
├─ Logs (Laravel Log Channel)
│  ├─ Stack trace on error
│  ├─ Queue job failures
│  └─ API integration errors
│
└─ Health Checks
   ├─ Database connectivity
   ├─ API integrations
   ├─ Queue worker status
   └─ Cache layer
```

---

**For detailed API integration information, see [API-REFERENCE.md](API-REFERENCE.md)**  
**For deployment instructions, see [DEPLOYMENT.md](DEPLOYMENT.md)**

