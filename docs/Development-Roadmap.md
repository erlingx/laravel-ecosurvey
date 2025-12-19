# EcoSurvey Development Roadmap

**Stack:** Laravel 12 | Livewire 3 + Volt | Flux UI | Filament v4 | Tailwind v4 | PostGIS | Leaflet.js

---
18.12.2025

## Phase 1: Foundation (Week 1-2) ✅ COMPLETE

GITHUB quota 33%

### Database & Models ✅ COMPLETE
- ✅ Install PostGIS extension (permanent via DDEV config)
- ✅ Migrations: `campaigns`, `survey_zones`, `data_points`, `environmental_metrics`
- ✅ Models with PostGIS geometry support (Campaign, SurveyZone, DataPoint, EnvironmentalMetric)
- ✅ Factories & seeders with realistic geo data (EcoSurveySeeder with 70+ data points)
- ✅ Feature tests for PostGIS functionality (Phase1DatabaseTest.php)

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

## Phase 2: Data Collection (Week 3-4) ⏳ IN PROGRESS

### Volt Components
- ✅ `resources/views/livewire/data-collection/reading-form.php`
  - ✅ GPS auto-capture via browser geolocation
  - ✅ Real-time validation
  - ⏳ Photo upload with geotag (next task)
  - ⏳ Offline draft storage (localStorage) (future enhancement)

- ⏳ `resources/views/livewire/campaigns/create-campaign.php`
  - Campaign setup form
  - Survey zone polygon drawing

### Map Integration
- ⏳ Leaflet.js setup in `resources/js/app.js`
- ⏳ Display user location marker
- ⏳ Basic basemap (OpenStreetMap)

**Deliverable:** Users submit GPS-tagged environmental readings

---

## Phase 3: Geospatial Visualization (Week 5) ⏸️ PENDING

### Interactive Maps (Volt)
- ⏳ `resources/views/livewire/maps/survey-map-viewer.php`
  - Display all data points with markers
  - Marker clustering for performance
  - Click marker → show reading details
  - Filter by date range, metric type
  - Draw polygon/circle survey zones

### PostGIS Queries
- ⏳ Spatial queries in `app/Services/GeospatialService.php`
  - Points within polygon
  - Distance calculations
  - Buffer zones
  - Spatial indexing

**Deliverable:** Real-time interactive map showing all survey data

---

## Phase 4: External APIs (Week 6) ⏸️ PENDING

### Services Layer
- ⏳ `app/Services/EnvironmentalDataService.php`
  - OpenWeatherMap: Current AQI
  - WAQI: Compare with official stations
  - Caching strategy to minimize API costs

- ⏳ `app/Services/SatelliteService.php`
  - NASA Earth: Satellite imagery
  - NDVI overlays

### Data Comparison
- ⏳ Store official vs user readings
- ⏳ Variance calculations
- ⏳ Display nearest monitoring station

**Deliverable:** Enrich user data with official environmental data

---

## Phase 5: Analytics & Heatmaps (Week 7) ⏸️ PENDING

### Volt Components
- ⏳ `resources/views/livewire/analytics/heatmap-generator.php`
  - Leaflet heatmap layer
  - Color-coded intensity
  - Toggle satellite/street view

- ⏳ `resources/views/livewire/analytics/trend-chart.php`
  - Chart.js time-series visualization
  - Statistics panel (min/max/avg/median)
  - Distribution histogram

### Calculations
- ⏳ `app/Services/AnalyticsService.php`
  - Aggregate spatial data for heatmaps
  - Statistical calculations
  - Trend analysis

**Deliverable:** Visual analytics dashboard with heatmaps and charts

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
- ⏳ Pest feature tests for all workflows
- ⏳ Unit tests for services
- ⏳ Browser tests for critical flows
- ✅ PostGIS query tests (Phase1DatabaseTest.php complete)

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
