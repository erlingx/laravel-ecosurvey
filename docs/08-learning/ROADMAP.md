# EcoSurvey Learning Roadmap

## Table of Contents

1. [Core Architecture](#1-core-architecture)
2. [PostGIS Spatial Database](#2-postgis-spatial-database)
3. [Copernicus Data Space API](#3-copernicus-data-space-api)
4. [Environmental Data APIs](#4-environmental-data-apis)
5. [Statistical Outlier Detection](#5-statistical-outlier-detection)
6. [Geospatial Service Methods](#6-geospatial-service-methods)
7. [Queue Architecture](#7-queue-architecture)
8. [Leaflet Map Visualization](#8-leaflet-map-visualization)
9. [Usage Tracking & Billing](#9-usage-tracking--billing)
10. [Key Integrations](#10-key-integrations)
11. [Critical Pitfalls](#11-critical-pitfalls)
12. [Scientific Correlations](#12-scientific-correlations)
13. [Subscription & Billing System](#13-subscription--billing-system)
14. [Data Quality Assurance](#14-data-quality-assurance)
15. [Photo Management](#15-photo-management)
16. [Performance Optimization](#16-performance-optimization)
17. [Temporal Correlation Analysis](#17-temporal-correlation-analysis)
18. [Survey Zone Management](#18-survey-zone-management)
19. [Offline Data Collection](#19-offline-data-collection)
20. [Filament Admin Panel](#20-filament-admin-panel)
21. [Edit Mode & CRUD Operations](#21-edit-mode--crud-operations)
22. [Learning Path Order](#22-learning-path-order)
23. [Code Entry Points](#23-code-entry-points)

---

## 1. Core Architecture

### Laravel 12 Foundation
- Service-oriented architecture (9 services)
- Queue-based async processing
- Volt (functional/class Livewire components)
- Filament v4 admin
- PostgreSQL + PostGIS

### Data Flow
```
User → DataPoint → Job → API Services → SatelliteAnalysis
                 ↓
            GeospatialService → PostGIS queries
                 ↓
            Leaflet map visualization
```

---

## 2. PostGIS Spatial Database

### Geometry vs Geography Types
- `geometry(POINT, 4326)` - Cartesian plane calculations
- `::geography` cast - Spherical earth calculations (meters)
- SRID 4326 = WGS84 coordinate system

### Critical PostGIS Functions

**Point Construction**
```sql
ST_SetSRID(ST_MakePoint(lon, lat), 4326)
```

**Coordinate Extraction**
```sql
ST_X(location::geometry) -- longitude
ST_Y(location::geometry) -- latitude
```

**Distance Calculations**
```sql
ST_Distance(point1::geography, point2::geography) -- meters
ST_DWithin(location::geography, center::geography, radius_meters)
```

**Spatial Queries**
```sql
ST_Within(point, polygon)
ST_Buffer(point::geography, radius_meters)
ST_Extent(geometry_collection) -- bounding box
```

**Indexing**
```sql
CREATE INDEX USING GIST (location)
```

### Pitfalls
- **Always cast to `::geography` for metric distances**
- Geometry operations are faster but use degrees, not meters
- GIST indexes required for performance
- Bounding box queries (ST_Extent) need `::geometry` cast

---

## 3. Copernicus Data Space API

### Architecture
- OAuth2 client credentials flow
- Token cached 1 hour (with 5min buffer)
- 401 auto-retry with token refresh
- PNG image responses

### Sentinel-2 L2A Satellite Indices

**NDVI** - Vegetation health
```
(NIR - Red) / (NIR + Red)
(B08 - B04) / (B08 + B04)
Range: -1 to 1
```

**NDMI** - Moisture content
```
(NIR - SWIR) / (NIR + SWIR)
(B08 - B11) / (B08 + B11)
```

**NDRE** - Chlorophyll/nitrogen
```
(NIR - RedEdge) / (NIR + RedEdge)
(B08 - B05) / (B08 + B05)
```

**EVI** - Enhanced vegetation (reduces atmospheric noise)
```
2.5 * ((NIR - Red) / (NIR + 6*Red - 7.5*Blue + 1))
2.5 * ((B08 - B04) / (B08 + 6*B04 - 7.5*B02 + 1))
```

**MSI** - Moisture stress
```
SWIR1 / NIR
B11 / B08
Range: 0 to 3+
```

**SAVI** - Soil-adjusted vegetation
```
((NIR - Red) / (NIR + Red + L)) * (1 + L)
L = 0.5 (soil brightness correction)
```

**GNDVI** - Green NDVI (chlorophyll sensitive)
```
(NIR - Green) / (NIR + Green)
(B08 - B03) / (B08 + B03)
```

### Sentinel-2 Band Reference
- B02 (490nm) = Blue
- B03 (560nm) = Green
- B04 (665nm) = Red
- B05 (705nm) = Red Edge
- B08 (842nm) = NIR (Near-Infrared)
- B11 (1610nm) = SWIR1
- B12 (2190nm) = SWIR2

### API Request Structure
```
POST /process
{
  input: {
    bounds: { bbox: [lon1, lat1, lon2, lat2] },
    data: [{ type: "sentinel-2-l2a", dataFilter: { timeRange } }]
  },
  output: { width, height, format: "image/png" },
  evalscript: "//VERSION=3\n..."
}
```

### Image Processing
- PNG pixel values: 0-255
- Map to index range: `((value / 255) * 2) - 1`
- 50x50 pixels → averaged for single metric value
- `imagecreatefromstring()` → `imagecolorat()` → average

### Pitfalls
- Token expires after 1 hour → implement refresh
- BBox order: `[west, south, east, north]` (lon, lat, lon, lat)
- Cloud coverage can return no data
- Rate limiting on free tier
- Date must be ISO 8601 format
- Cached responses don't count toward usage

---

## 4. Environmental Data APIs

### OpenWeatherMap - Air Quality
```php
GET /air_pollution?lat={lat}&lon={lon}&appid={key}
→ { aqi: 1-5, components: { co, no2, o3, pm2_5, pm10 } }
```

### WAQI - Official Monitoring Stations
```php
GET /feed/geo:{lat};{lon}/?token={key}
→ { aqi, city: { name, geo }, pollutants }
```

### Data Validation
- Compare user reading vs official station
- Calculate variance percentage
- Haversine distance to nearest station
- Flag outliers beyond threshold

---

## 5. Statistical Outlier Detection

### IQR Method (Interquartile Range)
```php
Q1 = 25th percentile
Q3 = 75th percentile
IQR = Q3 - Q1
Lower bound = Q1 - (1.5 * IQR)
Upper bound = Q3 + (1.5 * IQR)
```

### Z-Score Method
```php
mean = average(values)
std_dev = sqrt(variance)
z_score = (value - mean) / std_dev
Outlier if |z_score| > 3.0
```

### Application
- Per campaign + metric combination
- Minimum 4 points for IQR, 3 for Z-score
- Auto-flag in qa_flags array
- Status remains 'pending' (not auto-rejected)

---

## 6. Geospatial Service Methods

### GeoJSON Export
```php
getDataPointsAsGeoJSON($campaignId, $metricId, $approvedOnly)
→ { type: "FeatureCollection", features: [...] }
```
- Single JOIN query (no N+1)
- `ST_X()`, `ST_Y()` for coordinates
- Returns ready-to-render Leaflet format

### Spatial Queries
```php
findPointsInPolygon(array $coords)
findPointsInRadius($lat, $lon, $radiusMeters)
calculateDistance($lat1, $lon1, $lat2, $lon2)
createBufferZone($lat, $lon, $radiusMeters)
getBoundingBox($campaignId)
```

### Performance
- GIST spatial index essential
- Geography cast for accurate distances
- Cache GeoJSON results (5min TTL)
- Eager load relationships when needed

---

## 7. Queue Architecture

### Job Flow
```php
DataPoint created → EnrichDataPointWithSatelliteData
                 ↓
                 Check usage limits
                 ↓
                 Extract PostGIS coords
                 ↓
                 Fetch 7 satellite indices
                 ↓
                 Create SatelliteAnalysis (transaction)
                 ↓
                 Record usage for billing
```

### Configuration
- `queue:work --max-time=3600` (auto-restart hourly)
- Database queue driver
- Timeout: 60s, Tries: 3
- Auto-starts with DDEV daemon

### Pitfalls
- Must restart after code changes: `ddev artisan queue:restart`
- Check `failed_jobs` table
- Monitor with `queue:monitor database`
- DB transaction for atomicity (satellite + usage)

---

## 8. Leaflet Map Visualization

### Libraries
- Leaflet 1.9+
- MarkerCluster plugin
- GeoJSON layer

### Data Flow
```
Volt Component → GeospatialService → GeoJSON
             ↓
        Alpine.js dispatch
             ↓
        survey-map.js → Leaflet render
```

### Cluster Icons
- Quality-based color coding
- `iconCreateFunction` calculates counts
- Spiderfy on max zoom
- Click opens modal (Alpine event)

### Map State
- SessionStorage persistence
- Bounding box auto-fit
- Real-time filter updates (no page reload)

---

## 9. Usage Tracking & Billing

### Metered Resources
- Satellite analyses (7 indices = 1 call)
- API calls (overlay vs enrichment)
- Cost calculation per call type

### Implementation
```php
SatelliteApiCall → tracks all requests
UsageTrackingService → enforces limits
canPerformAction($user, 'satellite_analyses')
recordSatelliteAnalysis($user, $indexType)
```

### Pitfalls
- Only non-cached calls count
- Transaction ensures atomic billing
- Check limits BEFORE API call
- Separate tracking: overlay vs analysis

---

## 10. Key Integrations

### Service Dependencies
```
CopernicusDataSpaceService → 7 satellite indices
EnvironmentalDataService → AQI validation
GeospatialService → PostGIS queries
OutlierDetectionService → statistical QA
UsageTrackingService → billing limits
```

### Data Models
```
DataPoint (PostGIS location)
  ↓
SatelliteAnalysis (7 indices)
  ↓
SatelliteApiCall (usage tracking)
```

---

## 11. Critical Pitfalls

### PostGIS
- `::geography` for meters, `::geometry` for degrees
- GIST index mandatory for performance
- `ST_MakePoint(lon, lat)` NOT (lat, lon)
- Always specify SRID 4326

### API Integration
- OAuth token expiry handling
- Rate limiting on free tiers
- Cache responses to reduce costs
- 401 auto-retry logic

### Queue Processing
- Restart after Job/Service changes
- Transaction for atomic operations
- Check usage limits BEFORE API calls
- Monitor failed jobs

### Statistical Analysis
- Minimum data points for outlier detection
- Don't auto-reject, only flag
- Per-metric calculations essential

### Performance
- N+1 queries on map load (use JOINs)
- GeoJSON caching (5min TTL)
- Spatial index on location column
- Batch satellite index fetches

---

## 12. Scientific Correlations

### Satellite Index → Environmental Metric
- NDVI → Vegetation health, biomass
- NDMI → Soil moisture, irrigation needs
- NDRE → Chlorophyll, nitrogen levels
- EVI → Vegetation with atmospheric correction
- MSI → Plant water stress
- SAVI → Vegetation in sparse/dry areas
- GNDVI → Alternative chlorophyll measure

### Quality Assurance Chain
```
User reading → Outlier detection (IQR/Z-score)
            ↓
            Compare with official station (WAQI)
            ↓
            Satellite validation (7 indices)
            ↓
            QA flags array
            ↓
            Manual review (approved/rejected)
```

### R² Correlation Values
Check `metadata` in satellite responses for validation correlations

---

## 13. Subscription & Billing System

### Stripe Integration
```php
3-tier model:
- Free: 50 points/month
- Pro ($49/mo): Unlimited points + satellite
- Enterprise ($199/mo): Teams + API access
```

### Usage Metering
```php
UsageTrackingService
  ↓
Check limits BEFORE action
  ↓
Record usage in transaction
  ↓
Enforce tier-based restrictions
```

### Key Features
- Cancel subscription (immediate or end-of-period)
- Resume within grace period
- Update payment method (Stripe portal)
- View invoices & download PDFs
- Automatic sync via webhooks

### Middleware
```php
SubscriptionRateLimiter
- 30/60/300/1000 req/hr by tier
- Per-user independent limits
- 429 responses with retry_after
```

### Webhook Security
- Signature verification
- Idempotent processing
- Event type handling
- Failed webhook retry logic

---

## 14. Data Quality Assurance

### QA Workflow
```
Draft → Pending → Flagged → Reviewed → Approved/Rejected
```

### Automated Flagging
- IQR/Z-score outliers
- GPS accuracy >50m
- Out-of-range values
- Official station variance >30%

### Manual Review
- Bulk approve/reject
- Reviewer notes + audit trail
- QA flags preservation
- Status filtering

### Quality Dashboard
- 6 metrics (pending/approved/rejected)
- User leaderboard (top 5 with medals)
- 30-day activity tracking
- Approval rates & GPS accuracy

### Admin Tools
- Bulk clear flags
- Auto-approve high-quality option
- Quality check command
- Photo replacement

---

## 15. Photo Management

### Upload Flow
```
Geolocation → Camera → Upload → Store → Display
```

### Storage
- `public/files/data-points/`
- Unique filenames (timestamp-random)
- Old photo auto-deletion on replacement
- Symlink for public access

### Pitfalls
- Windows path separators (`\` vs `/`)
- Storage::disk('public') configuration
- `php artisan storage:link`
- File permissions on Linux

---

## 16. Performance Optimization

### N+1 Query Elimination
```php
// Bad (31 queries)
DataPoint::all()->each(fn($p) => $p->campaign->name)

// Good (1 query)
DataPoint::with('campaign')->get()
```

### Caching Strategy
- GeoJSON cache (5min TTL)
- Observer-based invalidation
- Key includes all filter params
- Database cache driver

### Results
- Map: 19.5x faster with cache
- API calls: 75% reduction
- 4.7x faster on cache hit

### Spatial Indexing
```sql
CREATE INDEX USING GIST (location)
```

### Query Optimization
- JOIN instead of eager load for maps
- Select only needed columns
- Aggregate on database (not PHP)

---

## 17. Temporal Correlation Analysis

### Satellite vs Ground Data
```
Field measurement date → Find matching satellite date
                      ↓
                  Temporal proximity
                      ↓
               Color-coded markers
```

### Proximity Colors
- 🟢 Green: 0-3 days (excellent)
- 🟡 Yellow: 4-7 days (good)
- 🟠 Orange: 8-14 days (acceptable)
- 🔴 Red: 15+ days (poor)

### Click-to-Analyze
- Button shows target date
- Auto-syncs satellite viewer
- Smooth flyTo animation
- Scientific ground-truthing workflow

### Scientific Use Cases
- Validation studies
- Phenology tracking
- Disaster response
- Long-term monitoring

---

## 18. Survey Zone Management

### Polygon Drawing
- Leaflet.draw plugin
- Click map to draw boundaries
- Auto-calculated area (km²)
- Edit/delete with confirmation

### Spatial Queries
```sql
-- Points in zone
ST_Within(point.location, zone.area)

-- Zone statistics
SELECT zone_id, COUNT(*), AVG(value)
FROM data_points
WHERE ST_Within(location, zone.area)
GROUP BY zone_id
```

### Display
- Blue dashed borders
- Interactive popups
- Metadata (name, description, area)
- Filter data by zone

---

## 19. Offline Data Collection

### PWA Features
- Service worker caching
- localStorage for drafts
- Background sync when online
- GPS in offline mode

### Draft Queue
- Save incomplete surveys
- Auto-fill on reconnect
- Conflict resolution
- Timestamp tracking

---

## 20. Filament Admin Panel

### Resources
```php
php artisan make:filament-resource DataPoint

// Auto-generates:
- Resource class (table, form, filters)
- List/Create/Edit pages
- CRUD operations
```

### Key Features
- Table builder (sortable, searchable, filterable)
- Form builder (validation, relationships)
- Actions (bulk approve/reject, export)
- Widgets (stats, charts, leaderboards)
- Policies integration (auto-enforced)

### Custom Actions
```php
Tables\Actions\BulkAction::make('approve')
    ->action(fn (Collection $records) => 
        $records->each->update(['status' => 'approved'])
    )
```

### Widgets
```php
// QA Statistics
protected function getHeaderWidgets(): array
{
    return [QAStatsWidget::class];
}

// Displays: pending, approved, rejected counts
```

### Files
```
app/Filament/Resources/DataPointResource.php
app/Filament/Widgets/QAStatsWidget.php
app/Filament/Widgets/UserLeaderboardWidget.php
```

---

## 21. Edit Mode & CRUD Operations

### Edit Flow
```
Map popup → Click edit (✏️) → Pre-filled form → Update → Refresh map
```

### Editable Fields
- Value
- Notes
- Photo (with replacement)
- Device info
- Manual GPS override
- Status

### Policies
- User can edit own points
- Admin can edit any
- No edit after approval (unless admin)

---

## 22. Learning Path Order

1. **PostGIS fundamentals** - geometry vs geography, SRID, ST_* functions
2. **Sentinel-2 bands** - which wavelengths measure what
3. **Vegetation indices formulas** - mathematical basis
4. **API authentication** - OAuth2 flows, token management
5. **Evalscript structure** - Sentinel Hub processing
6. **Image processing** - PNG decode, pixel averaging
7. **Statistical outlier detection** - IQR vs Z-score
8. **Spatial queries** - buffer zones, radius search, polygons
9. **Queue architecture** - async jobs, transactions, limits
10. **Map rendering** - GeoJSON → Leaflet → MarkerCluster
11. **Subscription billing** - Stripe integration, webhooks
12. **QA workflows** - Flagging, review, audit trails
13. **Performance** - N+1 elimination, caching, indexing

---

## 23. Code Entry Points

### Data Collection
```
app/Livewire/DataPoints/CreateDataPoint.php
app/Models/DataPoint.php (booted() method)
app/Observers/DataPointObserver.php (cache invalidation)
```

### Satellite Enrichment
```
app/Jobs/EnrichDataPointWithSatelliteData.php
app/Services/CopernicusDataSpaceService.php (all 7 indices)
```

### Map Visualization
```
resources/views/livewire/maps/survey-map-viewer.blade.php
resources/js/maps/survey-map.js
app/Services/GeospatialService.php
```

### Quality Assurance
```
app/Services/OutlierDetectionService.php
app/Services/EnvironmentalDataService.php
app/Filament/Resources/DataPointResource.php (admin review)
```

### Subscription & Billing
```
app/Services/UsageTrackingService.php
app/Http/Middleware/SubscriptionRateLimiter.php
app/Http/Controllers/StripeWebhookController.php
```

### Performance Optimization
```
app/Observers/DataPointObserver.php (cache)
app/Services/GeospatialService.php (JOIN queries)
database/migrations/*_create_data_points_table.php (GIST index)
```
