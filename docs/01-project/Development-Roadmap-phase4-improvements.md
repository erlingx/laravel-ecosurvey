# EcoSurvey Development Roadmap - Phase 4 Improvements

**Based on:** EcoSurvey-Improvement-Plan-FINAL.md v2.0  
**Start Date:** January 8, 2026  
**Duration:** 4 weeks (18-20 development days)  
**Status:** 🔄 PENDING

---

## Overview

This roadmap implements the recommendations from the consolidated review (ChatGPT 5.2 + Claude Sonnet 4.5 + Claude Opus 4.5). After completion, the original roadmap will continue with Phase 5.

**Key Goals:**
1. ✅ Fix foundational code gaps (Priority 0)
2. ✅ Implement QA/QC workflow and satellite persistence (Priority 1)
3. ✅ Integrate manual data with satellite maps (Priority 2)
4. ✅ Demonstrate advanced PostGIS expertise (Priority 3)
5. ✅ Add scientific export features (Priority 4)

---

## Priority 0: Critical Fixes (Day 1) ⚡

**Time:** 2-3 hours  
**Must complete before other work**

### Task 0.1: Fix DataPoint Model ✅
- ✅ Update `app/Models/DataPoint.php`
  - ✅ Add missing fillable fields: `survey_zone_id`, `status`, `reviewed_by`, `reviewed_at`, `review_notes`
  - ✅ Add `use SoftDeletes` trait
  - ✅ Add `reviewed_at` to casts
  - ✅ Add `surveyZone()` relationship (BelongsTo)
  - ✅ Add `reviewer()` relationship (BelongsTo to User)
  - ✅ Add `scopeHighQuality()` method
- ✅ Run existing tests to verify no regressions (10 tests passed)
- ✅ Update DataPointFactory if needed (added `approved()` and `highQuality()` states)

**Deliverable:** Migration-defined fields are now usable via mass assignment and relationships

**Tests:** Existing DataPoint tests should pass

---

### Task 0.2: Fix Campaign Model ✅
- ✅ Update `app/Models/Campaign.php`
  - ✅ Add `surveyZones()` hasMany relationship
  - ✅ Keep legacy `survey_zone` string field for backward compatibility
- ✅ Run existing tests (14 tests passed)

**Deliverable:** Campaign can access related survey zones

**Tests:** Existing Campaign tests should pass

---

**Priority 0 Checklist:**
- [✅] DataPoint fillable fields updated
- [✅] SoftDeletes trait added to DataPoint
- [✅] surveyZone() relationship added
- [✅] reviewer() relationship added
- [✅] scopeHighQuality() method added
- [✅] Campaign surveyZones() relationship added
- [✅] All existing tests passing

**Note:** User model already has dataPoints(), campaigns(), and reviewedDataPoints() relationships.  
**Note:** EnvironmentalMetric model already has dataPoints() relationship.  
**Verified:** All 138 tests passing ✅

---

## Priority 1: Foundation (Week 1) ✅ COMPLETE

**Time:** 5 days  
**Goal:** QA/QC workflow + satellite data persistence + survey zones working  
**Status:** ✅ COMPLETE (January 8, 2026)

**Completed:**
- QA/QC fields migration and model updates
- Visual differentiation for low-confidence data on maps (5 status colors with pie chart clusters)
- SatelliteAnalysis model with PostGIS geometry support
- Auto-enrichment via DataPointObserver + background job
- SurveyZone model with advanced PostGIS spatial methods
- Campaign intelligent map centering
- Data point overlay with clustering on satellite viewer
- Temporal proximity color-coding (green/yellow/orange/red)
- **Temporal correlation analysis** - compare field data with satellite data from same date
- **Intelligent analyze button** - shows target date, always syncs for scientific validity
- All 144 tests passing

### Task 1.1: QA/QC Fields Migration ✅
- ✅ Create migration: `add_qa_workflow_to_data_points.php`
  - ✅ Add `qa_flags` JSON field (nullable)
  - ✅ Add `device_model` string (nullable)
  - ✅ Add `sensor_type` string (nullable)
  - ✅ Add `calibration_at` timestamp (nullable)
  - ✅ Add `protocol_version` string (default '1.0')
- ✅ Run migration: `ddev artisan migrate`

**Deliverable:** Database supports QA/QC metadata

**Tests:** Migration runs successfully ✅

---

### Task 1.2: Update DataPoint Model for QA/QC ✅
- ✅ Update `app/Models/DataPoint.php`
  - ✅ Add new fields to `$fillable`: `qa_flags`, `device_model`, `sensor_type`, `calibration_at`, `protocol_version`
  - ✅ Add to casts: `qa_flags` => 'array', `calibration_at` => 'datetime'
  - ✅ Add `flagAsOutlier(string $reason)` method
- ✅ Update DataPointFactory with new fields

**Deliverable:** QA/QC fields usable in code ✅

**Tests:** Factory can create DataPoint with QA fields ✅

---

### Task 1.3: Visual Differentiation for Low-Confidence Data ✅
- ✅ Update `resources/js/maps/survey-map.js`
  - ✅ Add `getMarkerStyle()` function
  - ✅ Yellow dashed outline for `accuracy > 50m`
  - ✅ Red outline for points with `qa_flags`
  - ✅ Normal black outline for approved data
- ✅ Update GeoJSON properties to include `qa_flags` and `accuracy`

**Deliverable:** Map visually distinguishes data quality ✅

**Tests:** Manual verification on survey map ✅

---

### Task 1.4: SatelliteAnalysis Model and Migration ✅
- ✅ Create migration: `create_satellite_analyses_table.php`
  - ✅ Fields: `data_point_id`, `campaign_id`, `ndvi_value`, `moisture_index`, `temperature_kelvin`
  - ✅ Fields: `acquisition_date`, `satellite_source`, `cloud_coverage_percent`, `metadata`
  - ✅ PostGIS geometry column for location
  - ✅ Indexes: `[data_point_id, acquisition_date]`, `[campaign_id, acquisition_date]`, spatial index
- ✅ Create model: `app/Models/SatelliteAnalysis.php`
  - ✅ Define `$fillable` array
  - ✅ Define casts for decimal values and dates
  - ✅ Add `dataPoint()` relationship (BelongsTo)
  - ✅ Add `campaign()` relationship (BelongsTo)
- ✅ Migration run successfully

**Deliverable:** Satellite analyses can be persisted and queried ✅

**Tests:** 
- ⏳ `tests/Feature/SatelliteAnalysisTest.php` - basic CRUD (deferred to Priority 2)
- ⏳ Test temporal correlation method (deferred to Priority 2)

---

### Task 1.5: DataPoint Relationship to SatelliteAnalysis ✅
- ✅ Update `app/Models/DataPoint.php`
  - ✅ Add `satelliteAnalyses()` hasMany relationship

**Deliverable:** DataPoint can eager load satellite analyses ✅

**Tests:** `$dataPoint->satelliteAnalyses` works ✅

---

### Task 1.6: DataPointObserver for Auto-Enrichment ✅
- ✅ Create observer: `app/Observers/DataPointObserver.php`
  - ✅ Implement `created()` method
  - ✅ Dispatch `EnrichDataPointWithSatelliteData` job
- ✅ Register observer in `AppServiceProvider::boot()`
  - ✅ `DataPoint::observe(DataPointObserver::class)`

**Deliverable:** New data points automatically trigger satellite enrichment ✅

**Tests:** 
- ⏳ Verify job is dispatched when DataPoint created (deferred to Priority 2)
- ⏳ Use `Queue::fake()` in test (deferred to Priority 2)

---

### Task 1.7: EnrichDataPointWithSatelliteData Job ✅
- ✅ Create job: `app/Jobs/EnrichDataPointWithSatelliteData.php`
  - ✅ Implements `ShouldQueue`
  - ✅ Extract lat/lon from DataPoint using PostGIS
  - ✅ Fetch NDVI via `CopernicusDataSpaceService::getNDVIData()`
  - ✅ Fetch NDMI via `CopernicusDataSpaceService::getMoistureData()`
  - ✅ Create SatelliteAnalysis records for each index
  - ✅ Log success/failure
- ✅ Queue configuration already exists

**Deliverable:** Background job enriches data points with satellite context ✅

**Tests:**
- ⏳ `tests/Feature/Jobs/EnrichDataPointWithSatelliteDataTest.php` (deferred to Priority 2)
- ⏳ Mock Copernicus service (deferred to Priority 2)
- ⏳ Verify SatelliteAnalysis records created (deferred to Priority 2)

---

### Task 1.8: SurveyZone Model ✅
- ✅ Create model: `app/Models/SurveyZone.php` (already exists)
  - ✅ Define `$fillable`: `campaign_id`, `name`, `description`, `area`, `area_km2`
  - ✅ Define casts: `area_km2` => 'decimal:2'
  - ✅ Add `campaign()` relationship (BelongsTo)
  - ✅ Add `dataPoints()` relationship (HasMany)
  - ✅ Add `getContainedDataPoints()` method (PostGIS `ST_Contains`)
  - ✅ Add `calculateArea()` method (PostGIS `ST_Area`)
  - ✅ Add `getCentroid()` method (PostGIS `ST_Centroid`)
  - ✅ Add `getBoundingBox()` method (PostGIS `ST_Envelope`)
  - ✅ Add `toGeoJSON()` method (PostGIS `ST_AsGeoJSON`)
- ✅ Create factory: `database/factories/SurveyZoneFactory.php`
  - ✅ Generate test polygon using `ST_GeogFromText`
  - ✅ Use `afterCreating()` hook to set PostGIS geometry

**Deliverable:** Survey zones fully functional with spatial operations ✅

**Tests:**
- ⏳ `tests/Feature/SurveyZoneTest.php` (deferred to Priority 2)
- ⏳ Test area calculation (deferred to Priority 2)
- ⏳ Test centroid extraction (deferred to Priority 2)
- ⏳ Test bounding box (deferred to Priority 2)
- ⏳ Test GeoJSON conversion (deferred to Priority 2)
- ⏳ Test contained data points query (deferred to Priority 2)

---

### Task 1.9: Update Campaign Model for SurveyZones ✅
- ✅ Update `app/Models/Campaign.php`
  - ✅ Add `getMapCenter()` method
    - Use survey zone centroid if exists
    - Fallback to data points bounding box center
    - Default to Copenhagen (12.5683, 55.6761)

**Deliverable:** Campaign provides intelligent map centering ✅

**Tests:** Test map center calculation logic (manual verification) ✅

---

**Priority 1 Checklist:**
- [✅] QA/QC migration created and run
- [✅] DataPoint model updated with QA fields
- [✅] Visual differentiation on survey map
- [✅] SatelliteAnalysis model + migration created
- [✅] DataPointObserver registered
- [✅] EnrichDataPointWithSatelliteData job created
- [✅] SurveyZone model + factory created
- [✅] Campaign getMapCenter() method added
- [✅] All tests passing (144 tests, 402 assertions)

---

## Priority 2: Integration (Week 2)

**Time:** 5 days  
**Goal:** Manual data + satellite data truly integrated

### Task 2.1: Add DataPoints Overlay to Satellite Viewer ✅
- ✅ Update `resources/views/livewire/maps/satellite-viewer.blade.php`
  - ✅ Add state: `showDataPoints` => true
  - ✅ Add computed property: `dataPointsGeoJSON`
    - Use `GeospatialService::getDataPointsAsGeoJSON()`
  - ✅ Add UI toggle checkbox for showing/hiding datapoints
  - ✅ Add debug logging for data point counts
- ✅ Update `resources/js/maps/satellite-map.js`
  - ✅ Add `dataPointsClusterGroup` variable for clustering
  - ✅ Implement Leaflet MarkerCluster for multiple points at same location
  - ✅ Configure cluster radius: 50px with count badge icons
  - ✅ Update `updateSatelliteImagery()` to accept `dataPointsGeoJSON`
  - ✅ Render datapoints as `L.circleMarker` with temporal proximity colors
  - ✅ Add popup with metric value and "Click to analyze" message
  - ✅ Clear and re-add cluster group on updates
- ✅ Update `resources/css/app.css`
  - ✅ Add `.satellite-marker-cluster` styling

**Deliverable:** Satellite map shows field data points with clustering (128 points → visible clusters) ✅

**Tests:**
- ✅ Visual verification: Noise Pollution campaign shows clusters matching survey map
- ⏳ Update `tests/Feature/Maps/SatelliteViewerTest.php` (deferred)
- ⏳ Test datapoints GeoJSON structure (deferred)
- ⏳ Test toggle functionality (deferred)

---

### Task 2.2: Click-to-Analyze Interaction (Temporal Correlation Analysis) ✅
- ✅ Update `resources/js/maps/satellite-map.js`
  - ✅ Add click event listener to datapoint markers
  - ✅ Dispatch Livewire event: `jump-to-datapoint` with lat/lon/date/forceSync
  - ✅ **Improved button UX:** "📅 View satellite on [DATE]" (shows target date)
  - ✅ **Force sync mode:** Always syncs date for temporal correlation (scientific best practice)
  - ✅ Add event propagation prevention to avoid cluster interference
  - ✅ Use `flyTo()` for smooth animation (0.8s duration)
  - ✅ Disable cluster animations during jump
- ✅ Update `resources/views/livewire/maps/satellite-viewer.blade.php`
  - ✅ Add Alpine.js listener: `@jump-to-datapoint.window`
  - ✅ Implement `jumpToDataPoint($latitude, $longitude, $date, $forceSync)` method
    - ✅ Set `selectedLat`, `selectedLon`
    - ✅ Update `selectedDate` if syncMode OR forceSync is true
    - ✅ Increment `updateRevision`
  - ✅ Add detailed logging for debugging

**Deliverable:** Clicking analyze button enables temporal correlation analysis - comparing field measurements with satellite data from the same date ✅

**Scientific Value:** 
- ✅ Users can validate satellite data against ground truth from same day
- ✅ Temporal alignment ensures environmental conditions match
- ✅ Follows remote sensing best practices for data validation

**Tests:**
- ✅ Visual verification: Button shows target date, date always syncs
- ⏳ Test Livewire event dispatch (deferred)
- ⏳ Test coordinates update on jump (deferred)
- ⏳ Test forceSync parameter (deferred)

---

### Task 2.3: Use Survey Zone Geometry for Satellite Requests ✅
- ✅ Update `resources/views/livewire/maps/satellite-viewer.blade.php`
  - ✅ Modify `updatedCampaignId()` method
    - ✅ Priority 1: Use survey zone centroid if exists
    - ✅ Priority 2: Use first datapoint location
    - ✅ Priority 3: Default to Copenhagen (55.7072, 12.5704)
  - ✅ Log zone selection for debugging

**Deliverable:** Satellite viewer intelligently centers on survey zones ✅

**Tests:**
- ⏳ Test with campaign that has survey zone (deferred)
- ⏳ Test with campaign that has only datapoints (deferred)
- ⏳ Test with empty campaign (fallback) (deferred)

---

### Task 2.4: Temporal Alignment Visualization
- ⏳ Update `resources/views/livewire/maps/satellite-viewer.blade.php`
  - ⏳ Display temporal correlation quality when datapoint selected
  - ⏳ Show warning if `days_difference > 7`
  - ⏳ Use color coding: green (excellent), yellow (acceptable), red (poor)
- ⏳ Update popup content in `satellite-map.js`
  - ⏳ Include satellite observation date
  - ⏳ Show days difference

**Deliverable:** Users see temporal alignment quality

**Tests:** Visual verification

---

### Task 2.5: Dynamic Date Selection
- ⏳ Update `resources/views/livewire/maps/satellite-viewer.blade.php`
  - ⏳ Change default `selectedDate` from hardcoded to dynamic:
    - Use most recent datapoint's `collected_at` date when campaign selected
    - Fallback to `now()->subDays(7)` if no datapoints
- ⏳ Add date range constraints based on campaign duration
  - ⏳ Calculate min/max dates from campaign's datapoints

**Deliverable:** Date picker shows relevant dates for selected campaign

**Tests:**
- ⏳ Test date selection with campaign
- ⏳ Test date selection without campaign

---

### Task 2.6: Production-Ready UX Enhancements ✅ COMPLETED

**Goal:** Improve user experience with clear visual feedback and educational elements

#### Enhancement 1: Temporal Proximity Color-Coding on Markers ✅
- ✅ Update `resources/js/maps/satellite-map.js`
  - ✅ Add color-coding function based on temporal difference:
    - **Green**: 0-3 days (excellent alignment)
    - **Yellow**: 4-7 days (good alignment)
    - **Orange**: 8-14 days (acceptable)
    - **Red**: 15+ days (poor alignment)
  - ✅ Apply color to marker fill/border
  - ✅ Include temporal proximity in popup info

**Deliverable:** Visual indication of data quality at-a-glance

**Tests:**
- ✅ Test color assignment logic
- ✅ Visual verification on map

---

#### Enhancement 2: Optional Sync Mode for Advanced Users ✅
- ✅ Update `resources/views/livewire/maps/satellite-viewer.blade.php`
  - ✅ Add state: `syncMode` => false
  - ✅ Add checkbox toggle: "Sync satellite date with field data"
  - ✅ When enabled:
    - Clicking datapoint auto-updates date picker to collection date
    - Map centers and refreshes satellite imagery
  - ✅ When disabled (default):
    - Current behavior (manual date selection)
  - ✅ Add info tooltip explaining sync mode

**Deliverable:** Advanced users can auto-sync dates for rapid exploration

**Tests:**
- ⏳ Test sync mode toggle
- ⏳ Test date auto-update when enabled
- ⏳ Test manual mode when disabled

---

#### Enhancement 3: Clearer Labeling with Educational Tooltips ✅
- ✅ Update `resources/views/livewire/maps/satellite-viewer.blade.php`
  - ✅ Add Flux UI tooltips to key elements:
    - **"Show Field Data" checkbox**: "Overlay manual measurements on satellite imagery"
    - **"Sync Mode" checkbox**: "Automatically match satellite date to field data collection date"
    - **Date picker**: "Select satellite image acquisition date (cloud-free images may be limited)"
    - **Campaign selector**: "Filter view to specific research campaign"
  - ✅ Add legend for temporal color-coding:
    - Display color scale with day ranges
    - Position in top-right corner of map
  - ✅ Add info icon (ⓘ) next to "Temporal Alignment" label
    - Tooltip: "Shows how close satellite observation is to field measurement (closer = better correlation)"

**Deliverable:** Self-explanatory interface for new users

**Tests:**
- ⏳ Visual verification of tooltips
- ⏳ Test tooltip accessibility
- ⏳ Test legend display

---

**Priority 2 Checklist:**
- [x] DataPoints overlay on satellite map ✅
- [x] Marker clustering implemented (prevents stacked points) ✅
- [x] Toggle control for showing/hiding overlay ✅
- [x] Click-to-analyze interaction working ✅
- [x] **Temporal correlation analysis implemented** ✅
- [x] **Always-on date sync for analyze button** ✅
- [x] **Button shows target date in text** ✅
- [x] **Smooth zoom behavior (no erratic zoom-out)** ✅
- [x] Survey zone centering implemented ✅
- [x] Temporal correlation displayed ✅
- [x] Dynamic date selection based on campaign
- [x] **Temporal proximity color-coding implemented** ✅
- [x] **Educational tooltips and legend added** ✅
- [ ] Integration tests passing (estimate: 12+ tests)
- [ ] UX testing completed
- [ ] Browser compatibility verified

---

## Priority 2.5: Advanced Satellite Indices (Week 2.5)

**Time:** 2-3 days  
**Goal:** Add missing satellite indices for better metric correlation  
**Based on:** `docs/01-project/Satellite-Manual-Metrics-Analysis.md`

### Background
Current implementation has only 2 satellite indices (NDVI, NDMI) but Sentinel-2 provides 13 bands enabling 15+ scientifically-validated indices. Recent migration added 12 manual metrics with satellite correlation potential, but overlays don't exist yet.

**Gap Analysis:**
- ✅ Manual metrics added: Chlorophyll Content, LAI, Soil Moisture, etc.
- ❌ Missing NDRE (best for chlorophyll - R² > 0.8)
- ❌ Missing EVI (better than NDVI for dense vegetation)
- ❌ Missing MSI (moisture stress complement to NDMI)

### Task 2.5.1: Add NDRE Overlay (Chlorophyll Detection) 🔴 P0
- ⏳ Update `app/Services/CopernicusDataSpaceService.php`
  - ⏳ Add `getNDREData(float $lat, float $lon, ?string $date = null): ?array`
    - Formula: `(B08 - B05) / (B08 + B05)` where B05 is Red Edge (705nm)
    - Use PNG decoding pattern like NDVI
    - Return average NDRE value + metadata
  - ⏳ Add `getNDREVisualizationScript(): string`
    - Color scale: Red (low chlorophyll) → Yellow → Green (high chlorophyll)
  - ⏳ Add `getNDREScriptSimple(): string`
    - Output grayscale RGB for value extraction
  - ⏳ Update `getOverlayVisualization()` to handle `'ndre'` type
- ⏳ Update satellite viewer UI
  - ⏳ Add "NDRE (Chlorophyll)" option to overlay selector
  - ⏳ Add legend showing NDRE value interpretation

**Deliverable:** NDRE overlay available on satellite map - directly validates "Chlorophyll Content" manual metric

**Scientific Value:**
- Red Edge bands highly sensitive to chlorophyll concentration
- Less saturation than NDVI in dense canopy
- Direct correlation with field SPAD meter readings

**Tests:**
- ⏳ `test('getNDREData returns valid values')`
- ⏳ `test('NDRE overlay renders on map')`

---

### Task 2.5.2: Add EVI Overlay (Enhanced Vegetation Index) 🔴 P0
- ⏳ Update `app/Services/CopernicusDataSpaceService.php`
  - ⏳ Add `getEVIData(float $lat, float $lon, ?string $date = null): ?array`
    - Formula: `2.5 * ((B08 - B04) / (B08 + 6*B04 - 7.5*B02 + 1))`
    - Uses B02 (Blue), B04 (Red), B08 (NIR)
    - Return average EVI value + metadata
  - ⏳ Add `getEVIVisualizationScript(): string`
    - Color scale similar to NDVI but optimized for EVI range (0-1)
  - ⏳ Add `getEVIScriptSimple(): string`
  - ⏳ Update `getOverlayVisualization()` to handle `'evi'` type
- ⏳ Update satellite viewer UI
  - ⏳ Add "EVI (Enhanced Vegetation)" option to overlay selector
  - ⏳ Add tooltip: "Better than NDVI for dense forests and crops"

**Deliverable:** EVI overlay available - validates LAI and FAPAR metrics

**Scientific Value:**
- Improved sensitivity in high-biomass regions
- Reduces atmospheric influence (uses blue band correction)
- Standard product for global vegetation monitoring (MODIS, Copernicus)

**Tests:**
- ⏳ `test('getEVIData returns valid values')`
- ⏳ `test('EVI values differ from NDVI in dense vegetation')`

---

### Task 2.5.3: Add MSI Overlay (Moisture Stress Index) 🟡 P1
- ⏳ Update `app/Services/CopernicusDataSpaceService.php`
  - ⏳ Add `getMSIData(float $lat, float $lon, ?string $date = null): ?array`
    - Formula: `B11 / B08` (SWIR1 / NIR)
    - Higher values = more water stress
    - Return average MSI value + metadata
  - ⏳ Add `getMSIVisualizationScript(): string`
    - Color scale: Green (low stress) → Yellow → Red (high stress)
  - ⏳ Add `getMSIScriptSimple(): string`
  - ⏳ Update `getOverlayVisualization()` to handle `'msi'` type
- ⏳ Update satellite viewer UI
  - ⏳ Add "MSI (Moisture Stress)" option to overlay selector
  - ⏳ Add tooltip: "Plant water stress - complements NDMI"

**Deliverable:** MSI overlay available - provides alternative moisture stress indicator

**Scientific Value:**
- Different wavelength ratio than NDMI (both use SWIR but different combinations)
- Validated for crop stress detection
- Simpler calculation (ratio vs. normalized difference)

**Tests:**
- ⏳ `test('getMSIData returns valid values')`
- ⏳ `test('MSI overlay renders on map')`

---

### Task 2.5.4: Add SAVI Overlay (Soil-Adjusted Vegetation Index) 🟡 P1
- ⏳ Update `app/Services/CopernicusDataSpaceService.php`
  - ⏳ Add `getSAVIData(float $lat, float $lon, ?string $date = null): ?array`
    - Formula: `((B08 - B04) / (B08 + B04 + L)) * (1 + L)` where L=0.5
    - Corrects for soil brightness in sparse vegetation
    - Return average SAVI value + metadata
  - ⏳ Add visualization scripts
  - ⏳ Update `getOverlayVisualization()` to handle `'savi'` type
- ⏳ Update satellite viewer UI
  - ⏳ Add "SAVI (Soil-Adjusted)" option
  - ⏳ Add tooltip: "Better for sparse vegetation and agricultural areas"

**Deliverable:** SAVI overlay available - improves LAI estimation in sparse canopy

**Tests:**
- ⏳ `test('getSAVIData returns valid values')`

---

### Task 2.5.5: Update SatelliteAnalysis Model
- ⏳ Create migration: `add_advanced_satellite_indices_to_satellite_analyses.php`
  - ⏳ Add `ndre_value` decimal(5,3) nullable
  - ⏳ Add `evi_value` decimal(5,3) nullable
  - ⏳ Add `msi_value` decimal(5,3) nullable
  - ⏳ Add `savi_value` decimal(5,3) nullable
  - ⏳ Add `gndvi_value` decimal(5,3) nullable (future)
- ⏳ Update `app/Models/SatelliteAnalysis.php`
  - ⏳ Add new fields to `$fillable`
  - ⏳ Add to casts as decimal values
- ⏳ Run migration: `ddev artisan migrate`

**Deliverable:** Database can store all new satellite indices

**Tests:**
- ⏳ Migration runs successfully
- ⏳ `test('SatelliteAnalysis can store new indices')`

---

### Task 2.5.6: Update EnrichDataPointWithSatelliteData Job
- ⏳ Update `app/Jobs/EnrichDataPointWithSatelliteData.php`
  - ⏳ Fetch NDRE data: `$service->getNDREData($lat, $lon, $date)`
  - ⏳ Fetch EVI data: `$service->getEVIData($lat, $lon, $date)`
  - ⏳ Fetch MSI data: `$service->getMSIData($lat, $lon, $date)`
  - ⏳ Fetch SAVI data: `$service->getSAVIData($lat, $lon, $date)`
  - ⏳ Store all 6 indices in SatelliteAnalysis record:
    ```php
    SatelliteAnalysis::create([
        'data_point_id' => $dataPoint->id,
        'campaign_id' => $dataPoint->campaign_id,
        // Existing
        'ndvi_value' => $ndvi['value'] ?? null,
        'ndmi_value' => $ndmi['value'] ?? null,
        // New
        'ndre_value' => $ndre['value'] ?? null,
        'evi_value' => $evi['value'] ?? null,
        'msi_value' => $msi['value'] ?? null,
        'savi_value' => $savi['value'] ?? null,
        // ...
    ]);
    ```
  - ⏳ Handle partial failures gracefully (log missing indices, don't fail job)

**Deliverable:** All new data points enriched with 6 satellite indices (was 2)

**Tests:**
- ⏳ `test('job fetches all 6 indices')`
- ⏳ `test('job handles partial API failures')`
- ⏳ `test('SatelliteAnalysis created with all indices')`

---

### Task 2.5.7: Add Metric-to-Satellite Recommendation UI
- ⏳ Update `resources/views/livewire/maps/satellite-viewer.blade.php`
  - ⏳ Add info panel: "Recommended satellite indices for this metric"
  - ⏳ When user selects metric (future feature), show:
    - **Chlorophyll Content** → Primary: NDRE, Secondary: GNDVI
    - **LAI** → Primary: EVI, Secondary: NDVI, SAVI
    - **Soil Moisture** → Primary: NDMI, Secondary: MSI
    - **FAPAR** → Primary: EVI, Secondary: NDVI
  - ⏳ Style as info callout using Flux UI

**Deliverable:** Users guided to best satellite index for their metric

**Tests:** Visual verification

---

### Task 2.5.8: Documentation Updates
- ⏳ Update `README.md`
  - ⏳ Document all 6 satellite indices
  - ⏳ Add scientific references for each index
- ⏳ Update `SCIENTIFIC-METHODS.md` (create if not exists)
  - ⏳ Document satellite index formulas
  - ⏳ Explain metric-to-satellite correlations
  - ⏳ Include expected correlation coefficients (R² values)

**Deliverable:** Comprehensive documentation for publication citation

---

**Priority 2.5 Checklist:**
- [ ] NDRE overlay implemented (chlorophyll detection)
- [ ] EVI overlay implemented (enhanced vegetation)
- [ ] MSI overlay implemented (moisture stress)
- [ ] SAVI overlay implemented (soil-adjusted vegetation)
- [ ] SatelliteAnalysis migration + model updated
- [ ] EnrichDataPointWithSatelliteData job updated (6 indices)
- [ ] Metric-to-satellite recommendation UI added
- [ ] Documentation updated
- [ ] All tests passing (estimate: +10 tests)

**Scientific Impact:**
- 🎯 Satellite validation coverage: 30% → 80% of manual metrics
- 🎯 Multi-index correlation reduces uncertainty
- 🎯 Publication-ready remote sensing integration

---

## Priority 3: Advanced PostGIS (Week 3)

**Time:** 5 days  
**Goal:** Portfolio-worthy PostGIS expertise demonstrated

### Task 3.1: Spatial Join (Zone-Based Aggregation)
- ⏳ Add to `app/Services/GeospatialService.php`
  - ⏳ Method: `getZoneStatistics(int $campaignId): array`
  - ⏳ Use SQL with `ST_Contains` join
  - ⏳ Group by zone and metric
  - ⏳ Calculate: count, avg, min, max, stddev
  - ⏳ Return statistics grouped by zone name

**Deliverable:** Can aggregate data points by survey zone

**Tests:**
- ⏳ Add to `tests/Feature/GeospatialServiceTest.php`
- ⏳ Test zone statistics calculation
- ⏳ Test with points inside/outside zones

---

### Task 3.2: KNN Nearest Neighbor Queries
- ⏳ Add to `app/Services/GeospatialService.php`
  - ⏳ Method: `findNearestDataPoints(float $lat, float $lon, int $limit = 5): array`
  - ⏳ Use `<->` operator for KNN
  - ⏳ Calculate actual distance with `ST_Distance`
  - ⏳ Order by proximity
  - ⏳ Include distance in results

**Deliverable:** Fast nearest neighbor search

**Tests:**
- ⏳ Test KNN query returns closest points
- ⏳ Test limit parameter
- ⏳ Test distance accuracy

---

### Task 3.3: Grid-Based Heatmap Aggregation
- ⏳ Add to `app/Services/GeospatialService.php`
  - ⏳ Method: `generateGridHeatmap(int $campaignId, int $metricId, float $cellSizeDegrees = 0.001): array`
  - ⏳ Use `ST_SnapToGrid` to create grid cells
  - ⏳ Aggregate values per cell
  - ⏳ Calculate avg, count, stddev per cell
  - ⏳ Filter cells with `COUNT(*) >= 3`

**Deliverable:** Grid-based heatmap data for scientific visualization

**Tests:**
- ⏳ Test grid generation
- ⏳ Test aggregation accuracy
- ⏳ Test minimum sample size filtering

---

### Task 3.4: DBSCAN Spatial Clustering
- ⏳ Add to `app/Services/GeospatialService.php`
  - ⏳ Method: `detectClusters(int $campaignId, int $metricId, float $epsilonDegrees = 0.01, int $minPoints = 5): array`
  - ⏳ Use `ST_ClusterDBSCAN`
  - ⏳ Group by cluster_id
  - ⏳ Calculate cluster statistics (center, avg value, point count)
  - ⏳ Filter out noise points (cluster_id = null)

**Deliverable:** Automatic hotspot detection

**Tests:**
- ⏳ Test cluster detection
- ⏳ Test noise point filtering
- ⏳ Test cluster statistics

---

### Task 3.5: Voronoi Diagrams
- ⏳ Add to `app/Services/GeospatialService.php`
  - ⏳ Method: `generateVoronoiDiagram(int $campaignId): array`
  - ⏳ Use `ST_VoronoiPolygons` with `ST_Collect`
  - ⏳ Use `ST_Dump` to extract individual cells
  - ⏳ Return as GeoJSON FeatureCollection

**Deliverable:** Voronoi diagram showing influence zones

**Tests:**
- ⏳ Test Voronoi generation
- ⏳ Test GeoJSON structure

---

### Task 3.6: Convex Hull
- ⏳ Add to `app/Services/GeospatialService.php`
  - ⏳ Method: `getCampaignConvexHull(int $campaignId): ?array`
  - ⏳ Use `ST_ConvexHull` with `ST_Collect`
  - ⏳ Calculate area using `ST_Area`
  - ⏳ Return as GeoJSON Feature with area property

**Deliverable:** Actual surveyed area calculation

**Tests:**
- ⏳ Test convex hull generation
- ⏳ Test area calculation
- ⏳ Test null handling for empty campaigns

---

### Task 3.7: Volt Component to Showcase Advanced PostGIS
- ⏳ Create `resources/views/livewire/analytics/spatial-analysis.blade.php`
  - ⏳ Display zone statistics table
  - ⏳ Show cluster detection results
  - ⏳ Render Voronoi diagram on map
  - ⏳ Display convex hull overlay
  - ⏳ Campaign and metric filters
- ⏳ Add route: `/analytics/spatial`
- ⏳ Add to navigation menu

**Deliverable:** Portfolio-ready UI showcasing all PostGIS features

**Tests:**
- ⏳ Component renders correctly
- ⏳ Filters work
- ⏳ Data calculations accurate

---

**Priority 3 Checklist:**
- [ ] Zone statistics (spatial join) implemented
- [ ] KNN nearest neighbor queries working
- [ ] Grid-based heatmap aggregation
- [ ] DBSCAN clustering implemented
- [ ] Voronoi diagram generation
- [ ] Convex hull calculation
- [ ] Spatial analysis component created
- [ ] All PostGIS tests passing (estimate: 12+ tests)

---

## Priority 4: Scientific Features (Week 4)

**Time:** 3-4 days  
**Goal:** Publication-ready export and scientific credibility

### Task 4.1: DataExportService
- ⏳ Create service: `app/Services/DataExportService.php`
  - ⏳ Method: `exportForPublication(Campaign $campaign): array`
    - ⏳ Include metadata (export date, campaign info, QC counts, coordinate system)
    - ⏳ Extract coordinates using PostGIS
    - ⏳ Include satellite analyses (NDVI, NDMI)
    - ⏳ Include temporal correlation quality
    - ⏳ Filter to approved data only
  - ⏳ Method: `exportAsCSV(Campaign $campaign): string`
    - ⏳ Format for R/Python analysis
    - ⏳ Include all relevant fields

**Deliverable:** Full provenance export for scientific publications

**Tests:**
- ⏳ `tests/Feature/DataExportServiceTest.php`
- ⏳ Test JSON export structure
- ⏳ Test CSV format
- ⏳ Test metadata completeness
- ⏳ Test satellite context inclusion

---

### Task 4.2: Export Controller and Routes
- ⏳ Create controller: `app/Http/Controllers/ExportController.php`
  - ⏳ Method: `exportJSON(Campaign $campaign)`
    - Set proper headers
    - Return JSON response with attachment
  - ⏳ Method: `exportCSV(Campaign $campaign)`
    - Set CSV headers
    - Return CSV response with attachment
- ⏳ Add routes to `routes/web.php`:
  - ⏳ `GET /campaigns/{campaign}/export/json`
  - ⏳ `GET /campaigns/{campaign}/export/csv`
- ⏳ Add middleware: `auth`

**Deliverable:** Export endpoints accessible

**Tests:**
- ⏳ Test route accessibility
- ⏳ Test file download
- ⏳ Test authentication requirement

---

### Task 4.3: Export UI in Campaign View
- ⏳ Add export buttons to campaign detail page
  - ⏳ "Export JSON" button
  - ⏳ "Export CSV" button
  - ⏳ Show export preview (sample of first few rows)
- ⏳ Style with Flux UI components

**Deliverable:** User-friendly export interface

**Tests:** Manual UI testing

---

### Task 4.4: Temporal Correlation Visualization
- ⏳ Update `resources/views/livewire/maps/satellite-viewer.blade.php`
  - ⏳ Add info panel showing temporal correlation
  - ⏳ Color-coded quality indicator (green/yellow/red)
  - ⏳ Display days difference
  - ⏳ Show warning message if quality is poor
- ⏳ Style with Tailwind classes

**Deliverable:** Visual feedback on temporal alignment

**Tests:** Manual verification

---

### Task 4.5: Filament Admin Panel for Zone Management
- ⏳ Create Filament resource: `SurveyZoneResource`
  - ⏳ Table view: name, campaign, area_km2
  - ⏳ Form: name, description, campaign selector
  - ⏳ Add polygon drawing tool (future enhancement - note for now)
- ⏳ Add to Filament navigation

**Deliverable:** Admin can view/edit survey zones

**Tests:**
- ⏳ Test resource accessibility
- ⏳ Test CRUD operations via Filament

---

### Task 4.6: Documentation Updates
- ⏳ Update `README.md`
  - ⏳ Document new features
  - ⏳ Add export instructions
  - ⏳ Add PostGIS feature showcase
- ⏳ Create `SCIENTIFIC-METHODS.md`
  - ⏳ Document QA/QC workflow
  - ⏳ Explain temporal correlation
  - ⏳ Cite PostGIS functions used
  - ⏳ Export format specification

**Deliverable:** Comprehensive documentation

**Tests:** Documentation review

---

**Priority 4 Checklist:**
- [ ] DataExportService created
- [ ] Export controller and routes added
- [ ] Export UI added to campaign view
- [ ] Temporal correlation visualization
- [ ] Filament SurveyZone resource
- [ ] Documentation updated
- [ ] All export tests passing (estimate: 6+ tests)

---

## Testing Summary

### New Test Files Created
1. ⏳ `tests/Feature/SatelliteAnalysisTest.php`
2. ⏳ `tests/Feature/SurveyZoneTest.php`
3. ⏳ `tests/Feature/Jobs/EnrichDataPointWithSatelliteDataTest.php`
4. ⏳ `tests/Feature/DataExportServiceTest.php`

### Enhanced Test Files
1. ⏳ `tests/Feature/GeospatialServiceTest.php` (add 12+ new tests)
2. ⏳ `tests/Feature/Maps/SatelliteViewerTest.php` (add integration tests)

### Test Count Estimate
- Priority 0: 0 new tests (regression testing only)
- Priority 1: ~15 tests
- Priority 2: ~8 tests
- Priority 3: ~12 tests
- Priority 4: ~6 tests
- **Total: ~41 new tests**

---

## Deployment Checklist

Before marking Phase 4 Improvements as complete:

### Code Quality
- [ ] All new tests passing (41+ tests)
- [ ] No existing test regressions
- [ ] Run `ddev pint --dirty` (code formatting)
- [ ] No linting errors

### Database
- [ ] All migrations run successfully
- [ ] Seeders updated with new fields
- [ ] Database indexes created

### Documentation
- [ ] README.md updated
- [ ] SCIENTIFIC-METHODS.md created
- [ ] API documentation for export endpoints
- [ ] Code comments added

### Performance
- [ ] Spatial indexes verified
- [ ] N+1 query prevention checked
- [ ] Caching strategy reviewed

### Security
- [ ] Export routes require authentication
- [ ] Mass assignment protection verified
- [ ] File upload validation checked

---

## Success Metrics

After Phase 4 Improvements completion:

### Scientific Credibility ✅
- [ ] QA/QC workflow operational
- [ ] Satellite data persisted with audit trail
- [ ] Temporal correlation quantified
- [ ] Export includes full provenance

### PostGIS Expertise ✅
- [ ] 6 advanced PostGIS patterns demonstrated
- [ ] Spatial joins working
- [ ] KNN queries functional
- [ ] Clustering algorithms implemented

### Data Integration ✅
- [ ] Manual data overlaid on satellite maps
- [ ] Click-to-analyze interaction
- [ ] Survey zones used for map centering
- [ ] Temporal alignment visualized

### Production Readiness ✅
- [ ] Background jobs processing satellite enrichment
- [ ] Observer pattern automation
- [ ] Export service operational
- [ ] Admin panel for zone management

---

## Timeline Overview

```
Week 0 (Day 1): Priority 0 - Critical Fixes (2-3 hours)
├── Fix DataPoint model
└── Fix Campaign model

Week 1: Priority 1 - Foundation (5 days)
├── QA/QC fields and workflow
├── SatelliteAnalysis persistence
├── SurveyZone model
├── Auto-enrichment job
└── Testing

Week 2: Priority 2 - Integration (5 days)
├── DataPoints overlay on satellite map
├── Click-to-analyze interaction
├── Survey zone centering
├── Temporal correlation display
└── Testing

Week 3: Priority 3 - Advanced PostGIS (5 days)
├── Spatial joins (zone statistics)
├── KNN queries
├── Grid aggregation
├── DBSCAN clustering
├── Voronoi diagrams
├── Convex hull
├── Spatial analysis component
└── Testing

Week 4: Priority 4 - Scientific Features (3-4 days)
├── DataExportService
├── Export controller and routes
├── Export UI
├── Temporal correlation visualization
├── Filament zone management
├── Documentation
└── Final testing

Total: 18-20 development days (4 weeks)
```

---

## Next Steps After Completion

Once Phase 4 Improvements are complete:

1. ✅ Mark this roadmap as complete
2. ✅ Update main `Development-Roadmap.md` to reflect Phase 4 completion
3. ✅ Continue with **Phase 5: Analytics & Heatmaps** (Week 7) from original roadmap
4. ✅ Consider implementing optional biodiversity features (Darwin Core, GBIF) if relevant

---

## Key Files Created/Modified

**New Files (23):**
- Migrations: 2
- Models: 2 (SatelliteAnalysis, SurveyZone)
- Factories: 2
- Jobs: 1
- Observers: 1
- Services: 1 (DataExportService)
- Controllers: 1
- Tests: 4
- Documentation: 1 (SCIENTIFIC-METHODS.md)
- Filament Resources: 1

**Modified Files (8):**
- Models: 2 (DataPoint, Campaign)
- Services: 1 (GeospatialService - add 6 methods)
- Components: 1 (satellite-viewer.blade.php)
- JavaScript: 1 (satellite-map.js)
- Routes: 1 (web.php)
- Tests: 2 (enhance existing)

**Total: 31 files**

---

## Command Reference

```powershell
# Setup
ddev start
ddev composer install
ddev npm install

# Migrations
ddev artisan make:migration add_qa_workflow_to_data_points
ddev artisan make:migration create_satellite_analyses_table
ddev artisan migrate

# Models & Factories
ddev artisan make:model SatelliteAnalysis -mf
ddev artisan make:model SurveyZone -f
ddev artisan make:factory SatelliteAnalysisFactory
ddev artisan make:factory SurveyZoneFactory

# Jobs & Observers
ddev artisan make:job EnrichDataPointWithSatelliteData
ddev artisan make:observer DataPointObserver --model=DataPoint

# Services & Controllers
ddev artisan make:class Services/DataExportService
ddev artisan make:controller ExportController

# Filament
ddev artisan make:filament-resource SurveyZone --generate --panel=admin

# Testing
ddev artisan make:test SatelliteAnalysisTest --pest
ddev artisan make:test SurveyZoneTest --pest
ddev artisan make:test Jobs/EnrichDataPointWithSatelliteDataTest --pest
ddev artisan make:test DataExportServiceTest --pest

# Run tests
ddev artisan test --filter=SatelliteAnalysis
ddev artisan test --filter=SurveyZone
ddev artisan test --filter=GeospatialService
ddev artisan test  # Full suite

# Code formatting
ddev pint --dirty

# Development
ddev npm run dev  # Frontend (auto-starts with ddev start)
ddev artisan queue:work  # Queue worker (auto-starts with ddev start)
```

---

**Status:** Ready to begin implementation  
**Start Date:** TBD  
**Completion Target:** 4 weeks from start  

---

**Notes:**
- This roadmap focuses ONLY on implementing review recommendations
- Original Phase 5 (Analytics & Heatmaps) already complete - will continue with Phase 6
- Optional biodiversity features (Darwin Core, GBIF) can be added as Phase 5+ if needed
- All tasks marked with ⏳ are pending completion
- Update checkboxes [ ] to [✅] as tasks complete

