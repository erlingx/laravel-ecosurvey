# Learning Roadmap Index

## Start Here
**Read:** `ROADMAP.md` - Overview of entire system

---

## By Topic

### 1. Spatial Database
**File:** `postgis.md`
**Learn:**
- Geometry vs geography types
- ST_* functions (MakePoint, Distance, DWithin, Buffer)
- GIST indexing
- Coordinate extraction

**Practice:**
- Write spatial queries in Tinker
- Test distance calculations
- Create buffer zones

---

### 2. Satellite Data
**File:** `satellite-indices.md`
**Learn:**
- Sentinel-2 band wavelengths
- 7 vegetation index formulas
- Evalscript structure
- PNG → metric value processing

**Practice:**
- Trace NDVI calculation through code
- Understand correlation values
- Map bands to environmental metrics

---

### 3. API Integration
**File:** `api-patterns.md`
**Learn:**
- OAuth2 token management
- Rate limiting strategies
- Error handling patterns
- Usage tracking

**Practice:**
- Implement retry logic
- Cache API responses
- Track costs per call type

---

### 4. Statistics
**File:** `statistical-methods.md`
**Learn:**
- IQR vs Z-score outlier detection
- Variance calculation
- Haversine distance
- Correlation interpretation

**Practice:**
- Run outlier detection on test data
- Calculate user vs official variance
- Understand R² values

---

### 5. Queue Processing
**File:** `queue-architecture.md`
**Learn:**
- Job lifecycle
- Transaction safety
- Rate limiting in jobs
- Monitoring & debugging

**Practice:**
- Dispatch test jobs
- Check failed_jobs table
- Implement retry logic

---

### 6. Map Display
**File:** `map-visualization.md`
**Learn:**
- Leaflet basics
- GeoJSON structure
- Marker clustering
- Livewire integration

**Practice:**
- Create custom markers
- Build interactive popups
- Implement filtering

---

### 7. Subscription & Billing
**File:** `subscription-billing.md`
**Learn:**
- Stripe integration
- Webhook handling
- Usage metering
- Rate limiting middleware

**Practice:**
- Test payment flows
- Handle webhook events
- Implement tier-based limits

---

### 8. Performance
**File:** `performance.md`
**Learn:**
- N+1 query elimination
- Caching strategies
- Database indexing
- Query optimization

**Practice:**
- Profile slow queries
- Implement cache invalidation
- Add spatial indexes

---

### 9. Filament Admin
**File:** `ROADMAP.md` → Section 20
**Learn:**
- Resource generation
- Table/form builders
- Bulk actions
- Custom widgets

**Practice:**
- Create admin resource
- Add custom action
- Build stats widget

---

## By Learning Path

### Beginner (Day 1-2)
1. PostGIS basics (geometry, coordinates)
2. GeoJSON structure
3. Leaflet map rendering
4. Basic API calls

### Intermediate (Day 3-5)
1. Sentinel-2 bands & indices
2. Evalscript formulas
3. Statistical outlier detection
4. Queue job structure
5. Subscription system basics
6. N+1 query detection
7. Filament resources & widgets

### Advanced (Day 6-7)
1. Spatial query optimization
2. API rate limiting & caching
3. Transaction atomicity
4. Performance monitoring
5. Stripe webhook handling
6. Cache invalidation patterns

---

## By Use Case

### "I want to understand the data flow"
1. `ROADMAP.md` → Section 1 (Architecture)
2. `queue-architecture.md` → Workflow Patterns
3. Trace: `CreateDataPoint.php` → `EnrichDataPointWithSatelliteData.php`

### "I need to understand PostGIS queries"
1. `postgis.md` → All sections
2. `ROADMAP.md` → Section 6 (Geospatial Service)
3. Examine: `GeospatialService.php`

### "I want to know how satellite validation works"
1. `satellite-indices.md` → All formulas
2. `api-patterns.md` → Copernicus section
3. Trace: `CopernicusDataSpaceService.php` → each index method

### "I need to debug queue issues"
1. `queue-architecture.md` → Monitoring section
2. `ROADMAP.md` → Section 7 (Queue Architecture)
3. Check: `failed_jobs` table + logs

### "I want to optimize map performance"
1. `map-visualization.md` → Performance section
2. `performance.md` → N+1 queries + caching
3. Profile: GeoJSON size, marker count

### "I need to implement billing"
1. `subscription-billing.md` → All sections
2. `ROADMAP.md` → Section 13 (Subscription System)
3. Examine: `UsageTrackingService.php` + `StripeWebhookController.php`

### "I want to add QA workflows"
1. `ROADMAP.md` → Section 14 (Data Quality)
2. `statistical-methods.md` → Outlier detection
3. Trace: `OutlierDetectionService.php` → Filament admin

---

## Code Entry Points

### Data Collection Flow
```
resources/views/livewire/data-points/create-data-point.blade.php
app/Livewire/DataPoints/CreateDataPoint.php
app/Models/DataPoint.php (booted() method)
```

### Satellite Enrichment
```
app/Jobs/EnrichDataPointWithSatelliteData.php
app/Services/CopernicusDataSpaceService.php
  → getNDVIData()
  → getNDMIData()
  → ... (7 indices)
```

### Map Rendering
```
resources/views/livewire/maps/survey-map-viewer.blade.php
resources/js/maps/survey-map.js
app/Services/GeospatialService.php → getDataPointsAsGeoJSON()
```

### Quality Assurance
```
app/Services/OutlierDetectionService.php
  → detectOutliersIQR()
  → detectOutliersZScore()
app/Services/EnvironmentalDataService.php
  → compareWithOfficial()
```

---

## Key Connections

### PostGIS ↔ GeoJSON
```
PostGIS: ST_MakePoint(lon, lat)
      ↓
DataPoint: location column (geometry)
      ↓
GeospatialService: ST_X(), ST_Y()
      ↓
GeoJSON: [lon, lat]
      ↓
Leaflet: L.marker([lat, lon])  ← REVERSED!
```

### User Reading → Validation
```
User enters value
      ↓
Outlier detection (IQR/Z-score)
      ↓
Compare with WAQI official station
      ↓
Queue satellite enrichment (7 indices)
      ↓
Calculate correlations
      ↓
Assign QA flags
      ↓
Manual review (approve/reject)
```

### API Call → Billing
```
Check usage limit
      ↓
Make API call
      ↓
Cache response (key includes params)
      ↓
Track API call (SatelliteApiCall model)
      ↓
Calculate cost (based on type, cached status)
      ↓
Record usage (UsageTrackingService)
      ↓
All in DB transaction
```

---

## Important Pitfalls (Cross-Reference)

### Coordinate Systems
- PostGIS: `ST_MakePoint(lon, lat)` 
- GeoJSON: `[lon, lat]`
- Leaflet: `[lat, lon]` ← DIFFERENT!

### Distance Calculations
- Meters → `::geography`
- Degrees → `::geometry`
- Don't mix!

### API Tokens
- Expire after 3600s
- Refresh with 5min buffer
- Handle 401 auto-retry

### Queue Workers
- Must restart after code changes
- Use `queue:restart` not `ddev restart`
- Check `failed_jobs` table

### Statistical Methods
- IQR needs ≥4 points
- Z-score needs ≥3 points
- Don't auto-reject outliers

---

## Questions to Ask

### PostGIS
- Why cast to `::geography` for distance?
- What's SRID 4326?
- When to use GIST index?

### Satellite
- Which band measures chlorophyll?
- Why EVI vs NDVI?
- What does R² = 0.80 mean?

### API
- How to prevent token expiry?
- When does caching help?
- How to calculate API costs?

### Statistics
- IQR vs Z-score: which to use?
- What's an acceptable variance %?
- How many points needed?

### Architecture
- Why use transactions for satellite jobs?
- How to handle rate limits?
- When to queue vs sync dispatch?

### Subscription
- How to meter usage accurately?
- When to use webhooks vs polling?
- How to handle failed payments?

### Performance
- When to use cache vs database?
- How to detect N+1 queries?
- What indexes are essential?

---

## Next Steps

After reading all docs:
1. **Trace a full data point lifecycle** in code
2. **Run outlier detection** on test dataset
3. **Test satellite API** in Tinker
4. **Build a custom spatial query**
5. **Optimize map rendering** for 1000+ points
6. **Test subscription flow** in Stripe test mode
7. **Profile and optimize** a slow query

Then review:
- Test files in `tests/Feature/Services/`
- Migrations in `database/migrations/`
- API logs in `storage/logs/`
- Stripe webhook events
