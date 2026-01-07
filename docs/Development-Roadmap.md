# EcoSurvey Development Roadmap

**Stack:** Laravel 12 | Livewire 3 + Volt | Flux UI | Filament v4 | Tailwind v4 | PostGIS | Leaflet.js

---
07.01.2026 - Updated to Copernicus Data Space (Sentinel-2) satellite integration

## Phase 1: Foundation (Week 1-2) ✅ COMPLETE

GITHUB quota 33%

### Database & Models ✅ COMPLETE
- ✅ Install PostGIS extension (permanent via DDEV config)
- ✅ Migrations: `campaigns`, `survey_zones`, `data_points`, `environmental_metrics`
- ✅ Models with PostGIS geometry support (Campaign, DataPoint, EnvironmentalMetric)
- ⚠️ SurveyZone model - migration exists but model class not created yet
- ✅ Factories & seeders with realistic geo data (EcoSurveySeeder with 70+ data points)
- ⚠️ PostGIS tests integrated into other feature tests (no dedicated Phase1DatabaseTest.php)

### Auth & Layout ✅ COMPLETE
- ✅ Laravel Fortify authentication (already installed)
- ✅ Base Tailwind v4 layout with Flux UI
- ✅ Dark mode support
- ✅ Navigation structure for EcoSurvey
- ✅ Component strategy finalized
  - ✅ Flux UI for user-facing features (buttons, inputs, modals, cards, badges, etc.)
  - ✅ Filament for admin panel (tables, forms, charts, CRUD)
  - ✅ WireUI removed (was redundant with Flux)
  - ✅ Clean dependency tree
- ✅ Filament v4 admin panel
  - ✅ Admin panel scaffold at `/admin`
  - ✅ User resource with CRUD
  - ✅ Dashboard widgets
  - ✅ Dark mode support

**Deliverable:** Authenticated users can access dashboard with admin panel

---

## Phase 2: Data Collection (Week 3-4) ✅ COMPLETE

### Volt Components ✅
- ✅ `resources/views/livewire/data-collection/reading-form.blade.php`
  - ✅ GPS auto-capture via browser geolocation
  - ✅ Real-time validation with wire:model.live
  - ✅ Photo upload with geotag (5MB max, image validation)
  - ✅ Livewire WithFileUploads trait integration
  - ✅ Custom x-card component (free Flux account compatible)
  - ✅ Native HTML select dropdowns (free Flux compatible)
  - ✅ All 16 tests passing (50 assertions)
  - ✅ Character counter for notes field
  - ⏳ Offline draft storage (localStorage) (future enhancement)

- ✅ `resources/views/livewire/datapointcapture.blade.php`
  - ✅ GPS auto-capture via browser geolocation
  - ✅ Real-time validation
  - ✅ Photo upload with geotag
  - ✅ Native HTML select dropdowns
  - ✅ Custom x-card component
  - ✅ All 9 tests passing (31 assertions)

- ✅ Database Seeders
  - ✅ EcoSurveySeeder with 8 environmental metrics
  - ✅ 3 sample campaigns (Copenhagen Air Quality, Urban Noise, Water Quality)
  - ✅ Command: `ddev artisan ecosurvey:populate`

- ⏳ `resources/views/livewire/campaigns/create-campaign.blade.php` (Future Phase)
  - Campaign setup form
  - Survey zone polygon drawing

### Map Integration ✅
- ✅ Leaflet.js setup in `resources/js/app.js` (implemented in Phase 3)
- ✅ Display user location marker (via data point markers in Phase 3)
- ✅ Basic basemap (OpenStreetMap - implemented in Phase 3)

**Deliverable:** ✅ Users submit GPS-tagged environmental readings with photos

---

## Phase 3: Geospatial Visualization (Week 5) ✅ COMPLETE

### Interactive Maps (Volt) ✅
- ✅ `resources/views/livewire/maps/survey-map-viewer.blade.php`
  - ✅ Display all data points with markers
  - ✅ Marker clustering for performance
  - ✅ Click marker → show reading details
  - ✅ Filter by campaign and metric type
  - ✅ Leaflet.js integration via npm (not CDN)
  - ✅ Auto-zoom to fit all data points
  - ✅ GeoJSON data format
  - ⏳ Draw polygon/circle survey zones (future)
  - ⏳ Date range filter (future)

### PostGIS Queries ✅
- ✅ Spatial queries in `app/Services/GeospatialService.php`
  - ✅ `getDataPointsAsGeoJSON()` - Convert to GeoJSON format
  - ✅ `findPointsInPolygon()` - Spatial polygon queries
  - ✅ `findPointsInRadius()` - Distance-based queries
  - ✅ `calculateDistance()` - Point-to-point distance
  - ✅ `createBufferZone()` - Buffer zone generation
  - ✅ `getBoundingBox()` - Auto-zoom calculations
  - ✅ Spatial indexing for performance

### JavaScript Integration ✅
- ✅ `resources/js/app.js` - Main entry point
- ✅ `resources/js/maps/survey-map.js` - Survey map module
  - ✅ Map initialization with OpenStreetMap
  - ✅ Marker clustering
  - ✅ Popup content with data point details
  - ✅ Reset view function
  - ✅ Proper Vite bundling (no CDN)
  - ✅ Icon path fixes for Vite

### Testing ✅
- ✅ `tests/Feature/GeospatialServiceTest.php` (6 tests)
  - ✅ GeoJSON generation
  - ✅ Campaign/metric filtering
  - ✅ Radius-based queries
  - ✅ Distance calculations (~1.8km accuracy)
  - ✅ Bounding box calculations
  - ✅ Buffer zone creation

- ✅ `tests/Feature/Maps/SurveyMapViewerTest.php` (13 tests)
  - ✅ Authentication requirements
  - ✅ Map accessibility
  - ✅ Filter dropdown population
  - ✅ Campaign filtering
  - ✅ Metric filtering
  - ✅ Combined filters
  - ✅ GeoJSON structure validation
  - ✅ Bounding box calculations
  - ✅ Empty data handling
  - ✅ Coordinate order validation

**Deliverable:** ✅ Real-time interactive map showing all survey data with filters

**Total Phase 3 Tests:** 19 tests passing (70 assertions) (GeospatialService: 6, SurveyMapViewer: 13)

---

## Phase 4: Satellite Integration (Week 6) ✅ COMPLETE

### Services Layer ✅
- ✅ `app/Services/CopernicusDataSpaceService.php`
  - ✅ Copernicus Data Space integration (OAuth2 authentication)
  - ✅ Sentinel-2 imagery retrieval (10m resolution, FREE unlimited)
  - ✅ NDVI data fetching and interpretation
  - ✅ Moisture index (NDMI) calculation
  - ✅ Overlay visualizations (NDVI, moisture, true color)
  - ✅ Intelligent caching (1-hour TTL, token caching)
  - ✅ Error handling and logging
  - ✅ All 16 tests passing (48 assertions)

### Volt Components ✅
- ✅ `resources/views/livewire/maps/satellite-viewer.blade.php`
  - ✅ Interactive Leaflet map with Sentinel-2 imagery
  - ✅ Campaign location filter
  - ✅ Date picker for historical imagery
  - ✅ Overlay type selector (NDVI, moisture, true color)
  - ✅ Real-time analysis panels
  - ✅ PostGIS coordinate extraction
  - ✅ Livewire reactive updates
  - ✅ All 16 tests passing (37 assertions)

### JavaScript Integration ✅
- ✅ `resources/js/maps/satellite-map.js`
  - ✅ `initSatelliteMap()` - Map initialization
  - ✅ `updateSatelliteImagery()` - Dynamic overlays
  - ✅ Livewire event listeners
  - ✅ Sentinel-2 imagery overlay support
  - ✅ Modular structure (separate from survey map)

### Routes & Navigation ✅
- ✅ Route: `/maps/satellite` → `maps.satellite-viewer`
- ✅ Sidebar navigation with globe-alt icon
- ✅ Authentication middleware

### Testing ✅
- ✅ `tests/Feature/CopernicusDataSpaceServiceTest.php` (16 tests, 48 assertions)
  - ✅ OAuth2 authentication flow
  - ✅ Token caching and reuse
  - ✅ Satellite imagery retrieval
  - ✅ NDVI data processing from PNG images
  - ✅ Moisture data processing
  - ✅ Overlay visualizations (NDVI, moisture, true color)
  - ✅ Caching strategy validation
  - ✅ Error handling (API failures, OAuth failures)
  - ✅ NDVI interpretation accuracy

- ✅ `tests/Feature/Maps/SatelliteViewerTest.php` (16 tests, 37 assertions)
  - ✅ Authentication requirements
  - ✅ Component rendering
  - ✅ Campaign filter
  - ✅ Date picker functionality
  - ✅ Overlay type switching
  - ✅ Coordinate display
  - ✅ Map element validation
  - ✅ Error handling

**Deliverable:** ✅ Copernicus Sentinel-2 satellite imagery with NDVI vegetation analysis (FREE unlimited access)

**Total Phase 4 Tests:** 32 passing tests (CopernicusDataSpaceService: 16, SatelliteViewer: 16, 85 assertions)

**Code Quality:**
- ✅ Legacy NASA API service removed
- ✅ Legacy tests removed
- ✅ Clean, single-source implementation
- ✅ Following Laravel & Volt best practices
- ✅ Modular JavaScript structure

**Note:** EnvironmentalDataService (OpenWeatherMap/WAQI) and data comparison features moved to future enhancements.

---

## Phase 5: Analytics & Heatmaps (Week 7) ✅ COMPLETE

### Volt Components ✅
- ✅ `resources/views/livewire/analytics/heatmap-generator.blade.php`
  - ✅ Leaflet heatmap layer with leaflet.heat
  - ✅ Color-coded intensity gradient (blue → green → red)
  - ✅ Toggle satellite/street view
  - ✅ Campaign and metric filters
  - ✅ Statistics panel (count, min, max, avg, median, std dev)
  - ✅ Auto-fit bounds to data

- ✅ `resources/views/livewire/analytics/trend-chart.blade.php`
  - ✅ Chart.js time-series visualization
  - ✅ Multiple datasets (average, min, max)
  - ✅ Statistics panel
  - ✅ Distribution histogram
  - ✅ Interval selection (daily, weekly, monthly)

### Services ✅
- ✅ `app/Services/AnalyticsService.php`
  - ✅ `getHeatmapData()` - Format data for Leaflet.heat
  - ✅ `calculateStatistics()` - Statistical calculations (min/max/avg/median/std dev)
  - ✅ `getTrendData()` - Time-series aggregation with PostgreSQL DATE_TRUNC
  - ✅ `getDistributionData()` - Histogram binning algorithm
  - ✅ Campaign and metric filtering
  - ✅ All 12 tests passing (41 assertions)

### JavaScript Integration ✅
- ✅ leaflet.heat plugin integration
- ✅ Chart.js v4 integration
- ✅ Dynamic chart updates via Livewire
- ✅ Responsive chart configuration
- ✅ Proper Vite bundling

### Routes & Navigation ✅
- ✅ Route: `/analytics/heatmap` → `analytics.heatmap-generator`
- ✅ Route: `/analytics/trends` → `analytics.trend-chart`
- ✅ Navigation group: "Analytics" with chart icons
- ✅ Authentication middleware

### Testing ✅
- ✅ `tests/Feature/AnalyticsServiceTest.php` (12 tests, 41 assertions)
  - ✅ Heatmap data formatting
  - ✅ Campaign/metric filtering
  - ✅ Statistical calculations (all metrics)
  - ✅ Median calculation (even/odd counts)
  - ✅ Time-series trend data
  - ✅ Distribution histogram
  - ✅ Edge cases (empty data, single values)

**Deliverable:** ✅ Visual analytics dashboard with heatmaps and charts

**Total Phase 5 Tests:** 12 tests passing (41 assertions)

---

## Phase 6: Reporting (Week 8) ⏸️ PENDING

### PDF Reports
- ⏳ `app/Services/ReportGeneratorService.php`
  - Generate PDF with DomPDF
  - Embed map snapshots
  - Include charts and statistics
  - Executive summary

### Export Features
- ⏳ CSV/JSON export
- ⏳ Scheduled report generation
- ⏳ Email delivery

**Deliverable:** One-click professional PDF reports

---

## Phase 7: Admin Panel (Week 9) ⏸️ PENDING

### Filament Resources
- ⏳ Campaign management
- ⏳ Data point review/approval
- ⏳ User management
- ⏳ API usage tracking widget
- ⏳ Quality assurance dashboard

### Data Quality
- ⏳ Flag suspicious readings
- ⏳ Bulk approval/rejection
- ⏳ User contribution leaderboard

**Deliverable:** Admin can manage campaigns and review data quality

---

## Phase 8: Premium Features (Week 10) ⏸️ PENDING

### Stripe Integration
- ⏳ Subscription tiers (Free/Pro/Enterprise)
- ⏳ Rate limiting (50 readings/month for free)
- ⏳ Stripe checkout flow
- ⏳ Webhook handling

### API Metering
- ⏳ Track API calls per user
- ⏳ Cost calculation dashboard
- ⏳ Usage alerts

**Deliverable:** Monetization via Stripe subscriptions

---

## Phase 9: Real-time Collaboration (Week 11) ⏸️ PENDING

### Livewire Features
- ⏳ Live notifications when teammate adds reading
- ⏳ Real-time map updates (Laravel Echo + Pusher/Soketi)
- ⏳ Collaborative campaign mode
- ⏳ Activity feed

**Deliverable:** Multi-user real-time collaboration

---

## Phase 10: Testing & Deployment (Week 12) ⏸️ PENDING

### Testing
- ✅ Pest feature tests for core workflows (Phases 2-4 complete)
- ✅ Service tests for geospatial and satellite features
- ⏳ Unit tests for additional services
- ⏳ Browser tests for critical flows
- ✅ PostGIS query tests integrated into GeospatialServiceTest

### Deployment
- ⏳ PostgreSQL + PostGIS installation
- ⏳ Environment configuration (.env)
- ⏳ Queue worker supervisor/systemd
- ⏳ Deploy to Railway/Render/DigitalOcean
- ⏳ Performance optimization
- ⏳ Documentation

**Deliverable:** Production-ready application with tests

---

## Component Strategy

**See:** `docs/Component-Strategy.md` for full details

### Quick Reference

**✅ Use Flux UI for:**
- User-facing forms and inputs
- Modals and dropdowns
- Buttons and badges
- Navigation and layouts
- Cards and containers

**✅ Use Filament for:**
- Admin panel CRUD operations
- Data tables (sorting, filtering)
- Dashboard widgets and charts
- User management
- Admin-only features

**✅ Use Custom Components for:**
- Leaflet.js map integrations
- Chart.js heatmaps/visualizations
- Domain-specific geospatial widgets
- ONLY when Flux/Filament doesn't provide it

**❌ Don't:**
- Build custom form components (use Flux)
- Duplicate Flux functionality
- Use WireUI (removed - was redundant)

---

## Key Commands

```powershell
# Setup (via DDEV)
ddev composer install
ddev npm install
ddev artisan migrate:fresh --seed

# Development (auto-starts with ddev start)
ddev start  # Starts queue worker + Vite dev server automatically
# OR manually:
ddev npm run dev -- --host
ddev artisan queue:work

# Create components
ddev artisan make:volt data-collection/reading-form --test
ddev artisan make:model DataPoint -mfs
ddev artisan make:class Services/GeospatialService

# Filament resources
ddev artisan make:filament-resource Campaign --generate --panel=admin

# Testing
ddev artisan test --filter=DataPoint
ddev pint --dirty

# Admin panel
# Access at: https://ecosurvey.ddev.site/admin
```

---

## File Structure Priority

```
app/
├── Models/              # Week 1
├── Services/            # Week 3-4
└── Filament/            # Week 7

resources/views/livewire/
├── data-collection/     # Week 2
├── maps/                # Week 3
├── analytics/           # Week 5
└── campaigns/           # Week 2

resources/js/
├── app.js               # Leaflet + Chart.js
└── map-utils.js         # Week 3

database/migrations/     # Week 1
tests/Feature/           # Week 10
```

---

## Critical Dependencies

### PHP/Laravel (composer.json)
```json
{
  "php": "^8.3",
  "laravel/framework": "^12.0",
  "laravel/fortify": "^1.30",
  "livewire/livewire": "^3.0",
  "livewire/volt": "^1.7",
  "livewire/flux": "^2.9",
  "filament/filament": "^4.3"
}
```

### Frontend (package.json)
```json
{
  "leaflet": "^1.9",
  "leaflet.heat": "^0.2",
  "chart.js": "^4.4",
  "tailwindcss": "^4.0"
}
```

**PHP Extensions Required:** `pgsql`, `postgis`

**Note:** WireUI was removed (redundant with Flux UI)

---

## Success Metrics

- ✅ Submit reading with GPS in <30s
- ✅ Map loads 1000+ points with clustering
- ✅ Heatmap generates in <2s
- ✅ API calls cached (60min TTL)
- ✅ PDF report generated in <5s
- ✅ Real-time updates <1s latency
- ✅ 90%+ test coverage

---

**Timeline:** 12 weeks (MVP) | 14 weeks (Full features)
