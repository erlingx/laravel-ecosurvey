# EcoSurvey improvements (ChatGPT)

**Date:** 2026-01-08  
**Last Updated:** 2026-01-10

## Executive summary

EcoSurvey already has a strong foundation for a geospatial, research-oriented platform:

- A clear separation between **manual survey data** (points) and **satellite-derived context** (Copernicus overlays).
- Correct use of several essential PostGIS operations (geography-based distance, bounding-box extent, buffering).
- A working Copernicus Data Space integration with graceful fallback and cached responses.

To make the project stand out for **real-world scientific use**, to better **demonstrate PostGIS depth**, and to meaningfully **integrate manual survey maps with satellite maps**, the next improvements should focus on:

1. **Scientific credibility** (provenance, QA/QC, uncertainty handling, reproducibility)
2. **PostGIS “portfolio proof” queries** (zones/polygons, spatial joins, indexing patterns, grid/cluster analytics)
3. **True manual↔satellite coupling** (shared geometry, point-click analysis on satellite layer, temporal alignment, persistence)

---

## ✅ Completed Improvements (January 2026)

### 1. QA/QC Workflow & Data Quality (COMPLETED Jan 8-10, 2026)

**What was implemented:**

#### A. QA/QC Fields and Workflow ✅
- Added `qa_flags` (JSON array) to `data_points` table
- Added `status` field (`pending`, `approved`, `rejected`)
- Added `reviewed_by`, `reviewed_at`, `review_notes` for audit trail
- Auto-flagging logic:
  - `location_uncertainty`: Triggered when GPS accuracy > 80m
  - `calibration_overdue`: Triggered when calibration date > 90 days old

#### B. Visual Quality Differentiation ✅
- Survey map now displays different marker styles based on quality:
  - 🟢 **Green solid** = Approved high-quality (`status=approved`, `accuracy≤50m`)
  - 🟡 **Yellow dashed** = Low confidence (`accuracy>50m`)
  - 🔴 **Red dashed** = Flagged data (has QA flags)
  - 🔵 **Blue solid** = Normal/pending data
- Popups display QA flags with descriptive names
- Status and accuracy prominently shown

#### C. Measurement Protocol Metadata ✅
- Added fields to `data_points`:
  - `device_model` (varchar)
  - `sensor_type` (varchar)
  - `calibration_at` (timestamp)
  - `protocol_version` (varchar)
- Form captures this metadata during data collection
- Display in map popups and edit forms

#### D. GPS Accuracy Handling ✅
- Auto-capture GPS with browser geolocation API
- Accuracy automatically captured from device (in meters)
- Manual coordinate entry sets accuracy to 0m (surveyed/exact location)
- Accuracy value shown in all data displays
- Used in visual differentiation (yellow markers for >50m accuracy)

### 2. Data Point CRUD Operations (COMPLETED Jan 10, 2026)

**What was implemented:**

#### A. Data Point Edit Feature ✅
- Edit link in map popups (✏️ icon)
- Dedicated edit route: `/data-points/{id}/edit`
- Form pre-populated with existing data
- All fields editable:
  - Campaign, metric type, value
  - GPS coordinates (manual or recapture)
  - Notes, photo, device info, calibration date
- Success message on save
- Form stays on edit page (doesn't redirect)
- Changes immediately reflected on map

#### B. Photo Upload System (Complete Rewrite) ✅
- **Problem solved:** Windows + DDEV + Mutagen symlink incompatibility
- **Solution:** Direct storage in `public/files/` (no symlink needed)
- New `uploads` filesystem disk in `config/filesystems.php`
- Photos stored in `public/files/data-points/`
- Seeded demo photos in `public/files/seed-photos/`
- Photo persistence verified:
  - Thumbnail shows immediately after upload
  - Photo survives page refresh
  - Old photo deleted when uploading new one
  - Works perfectly on Windows development environment
- `DataPoint::photo_url` accessor handles both:
  - New uploads (`files/data-points/`)
  - Legacy seeded photos (`files/seed-photos/` or external URLs)
- All 21 ReadingForm tests passing ✅
- Issue documented in: `docs/99-issues/2026-01-photo-upload-windows-symlink-issue.md`

#### C. Data Collection Form Enhancements ✅
- Campaign auto-selection when only one active campaign
- GPS capture with live feedback
- Manual coordinate entry option
- Photo upload with preview
- Photo thumbnail on edit form
- Validation with helpful error messages
- Form auto-fills accuracy based on input method:
  - GPS captured: Uses device-reported accuracy
  - Manual entry: Sets accuracy to 0m (scientific best practice)

### 3. Map Visualization Improvements (COMPLETED Jan 9, 2026)

#### A. Enhanced Map Popups ✅
- Draggable popups with grab handle
- Photos displayed at top (when available)
- Organized content order:
  1. Environmental metric name (title)
  2. QA Flags (if any)
  3. Value with unit
  4. GPS accuracy
  5. Location (lat/long)
  6. Collection date
  7. Campaign name
  8. Submitted by user
  9. Status
  10. Notes (if provided)
  11. Photo
  12. Edit link (✏️)
- Data Point ID displayed
- Cursor changes to normal over selectable text

#### B. Map Interaction ✅
- Markers cluster when zoomed out
- Auto-zoom to fit visible data
- Filter by campaign and metric
- Point count badge
- Reset view button
- No page reload on filter changes (Livewire)

---

## 1) Real-world scientific use

### What you already have

**Data provenance signals in the data model** (`app/Models/DataPoint.php`):
- `accuracy` (GPS uncertainty)
- `collected_at` (timestamp)
- `official_value`, `official_station_name`, `official_station_distance`, `variance_percentage`
- `ndvi_value`, `satellite_image_url`

**Remote-sensing context in-app**:
- NDVI and moisture-style index support via `app/Services/CopernicusDataSpaceService.php`
- End-user visualization in `resources/views/livewire/maps/satellite-viewer.blade.php`

### Current limitations (for scientific credibility)

1. **QA/QC isn’t an explicit workflow**
   - Scientific datasets typically need a review/status lifecycle per reading.

2. **Uncertainty exists but isn’t used in analysis**
   - GPS uncertainty should affect filtering, visualization, and aggregation defaults.

3. **Measurement context metadata is missing**
   - To be reproducible, readings need minimal protocol/device metadata.

4. **Satellite–ground comparison isn’t a first-class, reproducible record**
   - NDVI is computed “live” but there’s no persistent audit trail for what was computed, when, and how.

### High-impact improvements for scientific use

#### A. Add QA/QC fields and workflow
Add a minimal lifecycle to each datapoint:
- `qa_status` (e.g., `draft`, `submitted`, `verified`, `rejected`)
- `qa_notes`
- `qa_flags` (JSON array for outlier flags, missing metadata, etc.)
- (Optional) reviewer attribution: `reviewed_by`, `reviewed_at`

Outcome:
- You can claim controlled data quality rather than “pins on a map”.

#### B. Use uncertainty in defaults
Examples:
- Default to excluding points where `accuracy` > X meters unless user opts in.
- Display “low-confidence” markers differently.

Outcome:
- Better scientific defensibility.

#### C. Capture measurement protocol metadata
Minimal additions:
- `device_model`
- `sensor_type`
- `calibration_at`
- `protocol_version`

Outcome:
- Enables “methods” sections in reports and better auditability.

#### D. Persist remote-sensing samples to make analyses reproducible
Instead of only “compute NDVI on-demand,” store an analysis record:
- `data_point_id`
- `provider`
- `index_type` (NDVI, NDMI/moisture)
- `index_value`
- `date`
- `bbox`
- `resolution_m`
- `script_version` (or evalscript identifier)

Outcome:
- Results are reproducible and explainable.

---

## 2) Demonstration of PostGIS knowledge

### What you already demonstrate well

`app/Services/GeospatialService.php` includes correct usage of:
- `ST_X`, `ST_Y` coordinate extraction
- `ST_Within` (point-in-polygon)
- `ST_DWithin` and `ST_Distance` with `::geography` for meter-accurate distance
- `ST_Buffer` and `ST_AsGeoJSON`
- `ST_Extent` and min/max functions for bounds

This already shows awareness of important correctness details (geometry vs geography).

### What’s currently missing for “advanced PostGIS” portfolio depth

1. **No first-class polygon/zone model**
   - Your docs refer to survey zones, but the codebase has no `SurveyZone` model.
   - `Campaign::$fillable` contains `survey_zone`, but it’s not represented as a geometry-centric workflow.

2. **No spatial joins used in application logic**
   - Portfolio-grade PostGIS work often includes joins like:
     - “which zone contains this datapoint?”
     - “aggregate by zone”

3. **No index-driven nearest neighbor patterns**
   - KNN queries (`<->`) are a strong signal of real PostGIS familiarity.

4. **No grid/cluster analytics powering real UI**
   - Your docs mention heatmaps/clustering, but the app code doesn’t currently demonstrate DB-side grid/cluster generation.

### High-impact PostGIS improvements

#### A. Implement survey zones as real geometries
Options:
- Add `survey_zones` table and `SurveyZone` model (preferred)
- Or store a campaign polygon in `campaigns.survey_zone` as geometry and treat it as first-class

Then leverage typical PostGIS tools:
- `ST_Area(survey_zone::geography)` for area reporting
- `ST_Centroid` for map centering
- `ST_Envelope` for bbox generation

#### B. Add spatial joins for real features
Examples:
- Fetch datapoints inside a zone:
  - `JOIN survey_zones ON ST_Contains(survey_zones.geom, data_points.location)`
- “zone stats” page:
  - counts, averages, min/max by zone

#### C. Add grid/aggregation queries (portfolio proof)
Examples:
- Heatmap-style aggregation:
  - `ST_SnapToGrid(location, cellSize)` grouped with `AVG(value)` and `COUNT(*)`

#### D. Add KNN nearest-neighbor example
Examples:
- “Nearest datapoints to a click”:
  - `ORDER BY location <-> ST_SetSRID(ST_MakePoint(lon, lat), 4326)`

---

## 3) Manual survey maps ↔ Copernicus satellite maps integration

### What exists today

#### Manual survey map
`resources/views/livewire/maps/survey-map-viewer.blade.php`:
- Shows datapoints via GeoJSON (`GeospatialService::getDataPointsAsGeoJSON`)
- Filters by campaign and metric

#### Satellite viewer
`resources/views/livewire/maps/satellite-viewer.blade.php`:
- Uses a single (lat/lon) location
- Changes location based on the **first datapoint** in a selected campaign
- Fetches a Copernicus overlay image around that point
- Shows NDVI or moisture “analysis panel” values on the page

### Current integration level

The two maps share the same *conceptual* campaign, but they are mostly **adjacent** rather than truly integrated:
- Satellite view is not driven by the survey map’s selected points or survey geometry.
- Satellite view doesn’t overlay your manual datapoints.
- There’s no “click a datapoint → compute satellite indices for that datapoint/date”.
- Satellite results aren’t persistently tied back to manual datapoints for reproducibility.

### High-impact integration improvements

#### A. Use campaign geometry (extent / zone) to drive satellite requests
Instead of using only a single point:
- If you implement survey zones or campaign polygons, compute:
  - centroid: `ST_Centroid(zone)`
  - bbox: `ST_Envelope(zone)`
- Request satellite imagery for the actual survey region.

Outcome:
- The satellite viewer becomes “about the study area”, not “about one arbitrary point”.

#### B. Overlay survey datapoints on satellite layer
Add an optional overlay of the same GeoJSON features on the satellite viewer map.

Outcome:
- Users can visually compare collected ground readings against satellite-derived context.

#### C. Point-click analysis on satellite map
Allow:
- click a datapoint → set `selectedLat/selectedLon` to that point
- default `selectedDate` to the datapoint’s `collected_at` date
- compute NDVI/NDMI for the point
- optionally “Save analysis to datapoint” or store in a separate remote-sensing table

Outcome:
- Manual data becomes meaningfully integrated with satellite data.

#### D. Temporal alignment rules
Real science needs a reproducible rule such as:
- pick “closest observation within ±N days”
- optionally filter by cloud coverage (if you later add that from Copernicus metadata)

Outcome:
- Reduces bias and improves credibility.

---

## Suggested prioritization (fastest path to biggest impact)

### Priority 1 (best portfolio ROI)
- Make survey zones/polygons first-class (model + geometry + UI)
- Use zone-derived bbox/centroid for satellite viewer
- Overlay manual datapoints on satellite map

### Priority 2 (scientific credibility)
- Add QA/QC fields + workflow
- Use GPS accuracy to filter & visualize uncertainty
- Persist remote-sensing computations as records

### Priority 3 (advanced PostGIS portfolio)
- Grid-based heatmap aggregation queries
- KNN nearest-neighbor examples with indexing
- Cluster analysis (DBSCAN) if you want to showcase more spatial analytics

---

## Files referenced in this review

- `app/Services/GeospatialService.php`
- `app/Services/CopernicusDataSpaceService.php`
- `resources/views/livewire/maps/survey-map-viewer.blade.php`
- `resources/views/livewire/maps/satellite-viewer.blade.php`
- `app/Models/DataPoint.php`
- `app/Models/Campaign.php`
- `docs/ProjectDescription-EcoSurvey.md`
- `docs/COPERNICUS-COMPLETE.md`
- `docs/GIS-PostGIS-Crashcourse.md`
