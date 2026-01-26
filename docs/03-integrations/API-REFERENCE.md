# API Integration Reference

**Complete guide to EcoSurvey's external API integrations**

---

## 📡 External APIs Used

### 1. Copernicus Dataspace (Sentinel-2 Satellite Imagery)

**Purpose**: Fetch high-resolution satellite images and calculate vegetation indices

**Endpoint**: `https://sh.dataspace.copernicus.eu/api/v1/catalog/`

**Authentication**: OAuth2 with client credentials flow

**Configuration (.env)**:
```env
COPERNICUS_USERNAME=your_email@example.com
COPERNICUS_PASSWORD=your_password
COPERNICUS_CLIENT_ID=your_client_id
COPERNICUS_CLIENT_SECRET=your_client_secret
```

**Key Features**:
- 10m resolution imagery
- Daily updates for covered areas
- Cloud masking support
- Free tier available

**Vegetation Indices Calculated**:
```
NDVI = (NIR - Red) / (NIR + Red)              [Vegetation presence]
GNDVI = (NIR - Green) / (NIR + Green)         [Broader spectral range]
NDRE = (NIR - RedEdge) / (NIR + RedEdge)      [Crop stress]
EVI = 2.5 * (NIR - Red) / (NIR + 2.4*Red + 1) [Enhanced sensitivity]
SAVI = (NIR - Red) / (NIR + Red + 0.5) * 1.5  [Soil effect correction]
OSAVI = (NIR - Red) / (NIR + Red + 0.16)      [Optimized]
CVI = (NIR / Green) - 1                        [Chlorophyll]
```

**Rate Limits**:
- 100 requests/hour
- 1000 requests/day

**Backup Provider**: [NASA EONET](#nasa-eonet)

**Service Class**: `App\Services\SatelliteService`

---

### 2. NASA EONET (Earth Observation Natural Event Tracking)

**Purpose**: Track natural disasters (fires, floods, storms) near campaigns

**Endpoint**: `https://eonet.gsfc.nasa.gov/api/v3/events`

**Authentication**: API Key (free, no authentication required)

**Configuration (.env)**:
```env
NASA_EONET_API_KEY=XXXXXXXXXXXXXXXXXXXXXXX
```

**Key Features**:
- Real-time event detection
- Multiple event categories (fires, floods, volcanoes, etc.)
- Geometry (point and polygon)
- Historical data available

**Event Types**:
```
├─ Volcanoes
├─ Floods
├─ Storms (Tropical Cyclones, Severe Weather)
├─ Wildfires
├─ Snow/Ice
├─ Drought
├─ Earthquakes
└─ Sea/Lake Ice
```

**Rate Limits**:
- 100 requests/hour
- Unlimited daily

**Use Cases**:
- Alert users of nearby events
- Exclude affected data from analysis
- Document natural impacts on environment

**Service Class**: `App\Services\NasaEonetService`

---

### 3. OpenWeatherMap API

**Purpose**: Get real-time weather conditions and forecasts

**Endpoint**: `https://api.openweathermap.org/data/2.5/weather`

**Authentication**: API Key

**Configuration (.env)**:
```env
OPENWEATHER_API_KEY=XXXXXXXXXXXXXXXXXXXXXXX
```

**Key Data Points**:
```json
{
  "temperature": 22.5,
  "humidity": 65,
  "pressure": 1013,
  "wind_speed": 3.2,
  "wind_direction": 230,
  "cloud_cover": 40,
  "precipitation": 0,
  "visibility": 10000,
  "uv_index": 6
}
```

**Rate Limits**:
- Free: 60 calls/minute
- Professional: Variable (paid tier)

**Caching**: Data cached for 30 minutes

**Use Cases**:
- Correlate weather with environmental measurements
- Flag unreliable data (extreme conditions)
- Weather context in reports

**Service Class**: `App\Services\WeatherService`

---

### 4. WAQI - World Air Quality Index

**Purpose**: Get air quality index (AQI) and pollutant levels

**Endpoint**: `https://api.waqi.info/feed/`

**Authentication**: API Token

**Configuration (.env)**:
```env
WAQI_API_KEY=XXXXXXXXXXXXXXXXXXXXXXX
```

**Air Quality Parameters**:
```
AQI Scale:
0-50     = Good (Green)           ✓
51-100   = Moderate (Yellow)      ⚠
101-150  = Unhealthy for Sensitive Groups (Orange)
151-200  = Unhealthy (Red)        ❌
201-300  = Very Unhealthy (Purple)
300+     = Hazardous (Maroon)     ⚠⚠⚠

Pollutants Measured:
├─ PM2.5 (Fine particulate)
├─ PM10 (Coarse particulate)
├─ O3 (Ozone)
├─ NO2 (Nitrogen dioxide)
├─ SO2 (Sulfur dioxide)
└─ CO (Carbon monoxide)
```

**Rate Limits**:
- 10,000 requests/month (free tier)

**Use Cases**:
- Correlate air quality with vegetation indices
- Health/safety warnings for field teams
- Environmental impact assessment

**Service Class**: `App\Services\AirQualityService`

---

### 5. Stripe Payment Processing

**Purpose**: Subscription billing, metering, and payment processing

**Base URL**: `https://api.stripe.com/v1/`

**Authentication**: Secret API Key (Bearer token)

**Configuration (.env)**:
```env
STRIPE_PUBLIC_KEY=pk_live_XXXXXXXXXXX
STRIPE_SECRET_KEY=sk_live_XXXXXXXXXXX
STRIPE_WEBHOOK_SECRET=whsec_XXXXXXXXXXX
```

**Key Integration Points**:

#### Subscription Management
```
Create Subscription
  POST /customers/{customer_id}/subscriptions
  ├─ price_id (Free/Pro/Enterprise)
  ├─ billing_cycle_anchor (align all users)
  └─ metadata (user_id, tier)

Cancel Subscription
  POST /subscriptions/{subscription_id}/cancel
  └─ cancellation_details (reason, feedback)

Resume Subscription
  POST /subscriptions/{subscription_id}
  └─ items (re-add to active subscriptions)

Retrieve Invoice
  GET /invoices/{invoice_id}/pdf
  └─ Returns PDF binary data
```

#### Usage Metering (Billing.com Model)
```
Record Usage
  POST /subscriptions/{subscription_id}/usage_records
  ├─ metric_id (data_points, analyses, exports)
  └─ quantity (increment)

Automatic Billing
  ├─ Monthly cycle: Charge on billing_cycle_start
  ├─ Metered quantities included
  └─ Overage charges (if configured)
```

#### Webhook Events
```
Subscriptions:
├─ customer.subscription.created
├─ customer.subscription.updated
├─ customer.subscription.deleted
├─ customer.subscription.trial_will_end
│
Invoices:
├─ invoice.created
├─ invoice.payment_succeeded
├─ invoice.payment_failed
└─ invoice.finalized

Payment Events:
├─ payment_intent.succeeded
├─ payment_intent.payment_failed
└─ charge.refunded
```

**Error Handling**:
```php
try {
    $subscription = $user->newSubscription('default', $priceId)->create();
} catch (\Stripe\Exception\ApiErrorException $e) {
    // Handle:
    // - card_error (invalid card)
    // - rate_limit_error (API limit)
    // - authentication_error (invalid API key)
    // - api_error (server error)
}
```

**Service Class**: `App\Services\StripeSubscriptionService`

**Cashier Documentation**: `vendor/laravel/cashier/docs/`

**Rate Limits**: None specified (generous)

---

## 🔗 Internal API Endpoints

### Authentication
```
POST   /login              → Fortify authentication
POST   /logout             → Destroy session
POST   /register           → Create new account
POST   /forgot-password    → Request reset link
POST   /reset-password     → Reset password with token
```

### Campaigns
```
GET    /campaigns          → List user's campaigns
POST   /campaigns          → Create new campaign
GET    /campaigns/{id}     → View campaign details
PUT    /campaigns/{id}     → Update campaign
DELETE /campaigns/{id}     → Delete campaign
GET    /campaigns/{id}/map → Interactive map view
GET    /campaigns/{id}/analytics → Dashboard charts
```

### Survey Data
```
GET    /surveys            → List all surveys (paginated)
POST   /surveys            → Create new reading
GET    /surveys/{id}       → View survey details
PUT    /surveys/{id}       → Update survey
DELETE /surveys/{id}       → Delete survey
POST   /surveys/{id}/flag  → Flag for QA review
```

### Satellite Data
```
GET    /satellites         → List satellite images for campaign
GET    /satellites/{id}    → Get specific image + indices
GET    /satellites/{id}/heatmap → Generate heatmap layer
GET    /satellites/{id}/timeseries → Time-series data
POST   /satellites/sync    → Manually trigger Copernicus sync
```

### Analytics & Reporting
```
GET    /analytics/dashboard     → Summary statistics
GET    /analytics/timeseries    → Chart data
GET    /analytics/heatmap       → Spatial density data
GET    /analytics/statistics    → Statistical analysis
GET    /exports/csv             → CSV export
GET    /exports/pdf             → PDF report
GET    /exports/json            → JSON data export
```

### Subscriptions
```
GET    /subscriptions           → View current subscription
POST   /subscriptions/checkout  → Initiate Stripe checkout
POST   /subscriptions/cancel    → Cancel subscription
POST   /subscriptions/resume    → Resume after grace period
GET    /subscriptions/invoices  → List billing history
GET    /subscriptions/invoices/{id}/pdf → Download invoice
PUT    /subscriptions/payment-method → Update Stripe card
```

### Admin (Filament)
```
GET    /admin                   → Dashboard
GET    /admin/campaigns         → Manage all campaigns
GET    /admin/users             → User management
GET    /admin/subscriptions     → Subscription analytics
GET    /admin/flags             → QA review queue
POST   /admin/flags/{id}/approve → Approve survey
```

---

## 📊 Data Exchange Formats

### Survey Data (JSON)
```json
{
  "id": "uuid",
  "campaign_id": "uuid",
  "user_id": "uuid",
  "location": {
    "type": "Point",
    "coordinates": [-118.2437, 34.0522]
  },
  "measurements": {
    "temperature": 22.5,
    "humidity": 65,
    "soil_moisture": 45,
    "ph": 6.8,
    "nitrates": 12.5
  },
  "photos": [
    {
      "url": "https://cdn.example.com/photos/...",
      "captured_at": "2025-01-15T14:30:00Z"
    }
  ],
  "metadata": {
    "accuracy": 5,
    "altitude": 125,
    "device": "iPhone 14"
  },
  "created_at": "2025-01-15T14:30:00Z"
}
```

### Satellite Image (GeoJSON)
```json
{
  "type": "Feature",
  "geometry": {
    "type": "Polygon",
    "coordinates": [[[-118.25, 34.05], [-118.24, 34.05], ...]]
  },
  "properties": {
    "image_id": "uuid",
    "captured_at": "2025-01-15T09:00:00Z",
    "cloud_coverage": 15,
    "indices": {
      "ndvi": {
        "min": 0.2,
        "max": 0.8,
        "mean": 0.55,
        "std_dev": 0.12
      },
      "evi": { ... },
      "gndvi": { ... },
      "ndre": { ... },
      "savi": { ... },
      "osavi": { ... },
      "cvi": { ... }
    }
  }
}
```

### Export Format (CSV)
```csv
survey_id,campaign_id,date,latitude,longitude,temperature,humidity,soil_moisture,ndvi,evi,notes
uuid-1,uuid-camp,2025-01-15,34.0522,-118.2437,22.5,65,45,0.65,0.58,Healthy vegetation
uuid-2,uuid-camp,2025-01-16,34.0525,-118.2440,21.8,68,42,0.62,0.55,Post-rainfall
```

---

## ⚙️ Error Handling & Retry Logic

### API Error Responses
```json
{
  "error": true,
  "status": 429,
  "message": "Rate limit exceeded",
  "retry_after": 3600
}
```

### Exponential Backoff (for failing APIs)
```php
$maxAttempts = 3;
$initialDelay = 1; // second

for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    try {
        $response = $this->fetchSatelliteData();
        break; // Success
    } catch (ApiException $e) {
        $delay = $initialDelay * (2 ** ($attempt - 1));
        sleep($delay);
    }
}
```

### Graceful Degradation
```
Primary (Copernicus) → Fails
├─ Log error
├─ Retry in background job
└─ Show cached data to user

Timeout (> 30 seconds)
├─ Cache partial results
├─ Show "Loading..." UI
└─ Complete in background
```

---

## 🔐 API Security

### Request Authentication
```
Header: Authorization: Bearer {api_token}
```

### Rate Limiting (Per Tier)
```
Guest:      30 requests/hour
Free:       60 requests/hour
Pro:        300 requests/hour
Enterprise: 1000 requests/hour
```

### Data Validation
```php
// All inputs validated before external API calls
$validated = request()->validate([
    'latitude' => 'required|numeric|between:-90,90',
    'longitude' => 'required|numeric|between:-180,180',
    'campaign_id' => 'required|uuid|exists:campaigns,id',
]);
```

### CORS & CSRF
```
CORS: Disabled (server-side API only)
CSRF: Laravel Sanctum tokens on all state-changing requests
```

---

## 📈 Monitoring & Logging

### API Health Checks
```php
// Scheduled: Every 15 minutes
$this->checkCopernicusHealth();
$this->checkNasaEonetHealth();
$this->checkStripeHealth();
```

### Failed Request Logging
```
Log File: storage/logs/laravel.log

Format:
[2025-01-15 14:30:00] API.ERROR: Copernicus API timeout
  → Endpoint: https://sh.dataspace.copernicus.eu/api/v1/...
  → Status: 504
  → Retry: Queued for 15:00
```

### Usage Metrics
```
Dashboard shows:
├─ API calls per day
├─ Error rate per service
├─ Average response time
├─ Cache hit rate
└─ Cost tracking (if applicable)
```

---

## 🧪 Testing API Integrations

### Unit Tests
```bash
# Test Copernicus integration
ddev artisan test tests/Unit/SatelliteServiceTest.php

# Test Stripe integration
ddev artisan test tests/Unit/StripeSubscriptionServiceTest.php
```

### Feature Tests
```bash
# Test end-to-end satellite sync
ddev artisan test tests/Feature/SatelliteSyncTest.php

# Test subscription checkout flow
ddev artisan test tests/Feature/SubscriptionCheckoutTest.php
```

### Integration Testing (with real APIs)
```
⚠️ Only run in development environment
⚠️ Requires valid API credentials
⚠️ May incur charges

ddev artisan test --filter=CopernicusIntegration
```

---

## 🚀 Production Checklist

- [ ] All API keys rotated and stored in environment variables
- [ ] Webhook endpoints secured with signature verification
- [ ] Rate limiting configured per tier
- [ ] Error logging and alerting configured
- [ ] Fallback providers tested and ready
- [ ] API credits/costs monitored
- [ ] Response timeouts configured
- [ ] Retry logic tested for resilience
- [ ] Database backup strategy in place
- [ ] Monitoring dashboard set up

---

## 📚 Additional Resources

- [Copernicus Dataspace Documentation](https://documentation.dataspace.copernicus.eu/)
- [NASA EONET API Docs](https://eonet.gsfc.nasa.gov/api/v3/)
- [OpenWeatherMap API](https://openweathermap.org/api)
- [WAQI API Docs](https://aqicn.org/api/)
- [Stripe API Reference](https://stripe.com/docs/api)
- [Laravel Cashier Docs](https://laravel.com/docs/cashier)

---

**Last Updated**: January 26, 2026  
**Maintained By**: EcoSurvey Development Team

