# UX Testing Cookbook - Priority 0 & 1 Features

**Date:** January 8-12, 2026  
**Scope:** Priority 0 (Critical Fixes) + Priority 1 (Foundation)  
**Test Duration:** ~30-45 minutes  
**Prerequisites:** Application running with `ddev start`, logged in as authenticated user  
**Status:** ✅ ALL TESTS COMPLETED - January 12, 2026

> **📝 Note:** Backend logic tests (relationships, spatial methods, scopes) have been migrated to automated Pest tests in `tests/Feature/`. This document now focuses exclusively on browser-based manual UX testing.

---

## Setup Before Testing

```powershell
# 1. Start application (if not already running)
ddev start

# 2. Ensure fresh test data (optional - skip if you want to keep existing data)
ddev artisan migrate:fresh --seed

# 3. Open browser to application
# URL: https://ecosurvey.ddev.site
```

**Test User Credentials:**
- Check your seeded users or create a test account via registration

---

## Test Suite 1: Visual Differentiation on Survey Map (Task 1.3) ✅

**Goal:** Verify that data points show different colors/styles based on quality  
**Status:** COMPLETED - January 9, 2026

### Test 1.1: View Survey Map with Different Quality Markers ✅

1. **Navigate to Survey Map**
   - Click "Maps" → "Survey Map" in sidebar
   - URL: `/maps/survey`

2. **Expected Results:**
   - Map loads with data points clustered
   - Zoom in to see individual markers
   - Look for **different colored markers:**
     - 🔴 **Red dashed circles** = Flagged data (has `qa_flags`)
     - ⚫ **Gray dashed circles** = Rejected data (`status = rejected`)
     - 🟡 **Yellow dashed circles** = Low confidence data (`accuracy > 50m`)
     - 🟢 **Green solid circles** = Approved high-quality data (`status = approved`, `accuracy <= 50m`)
     - 🔵 **Blue solid circles** = Pending/Draft data (`status = pending` or `draft`)

3. **How to Verify:**
   - Click on each marker type to see popup
   - Popup should show:
     - **Photo** (if uploaded with data point) - displayed at top of popup
     - **Value** with unit
     - **Location** coordinates (Latitude/Longitude in decimal degrees)
     - **Campaign** name
     - **Submitted by** user name
     - **Date** of collection
     - **Accuracy** value (check if > 50m for yellow markers)
     - **Notes** (if provided)
     - **QA Flags** with descriptive names (check for red markers)
     - **Status** (check for "approved" on green markers)

### Test 1.2: Filter and Observe Quality Changes ✅

1. **Apply Campaign Filter**
   - Select different campaigns from dropdown
   - Observe how marker distributions change

2. **Apply Metric Filter**
   - Select different metrics (Temperature, Humidity, etc.)
   - Verify markers update correctly

3. **Expected Behavior:**
   - Map updates without page reload (Livewire)
   - Point count badge updates
   - Map auto-zooms to fit visible points

---

## Test Suite 2: QA/QC Workflow (Tasks 1.1, 1.2) ✅

**Goal:** Verify QA/QC metadata can be captured and displayed
**Status:** COMPLETED - January 10, 2026


### Test 2.1: Submit Data Point with QA/QC Fields ✅

1. **Navigate to Data Collection**
   - Click "Data Collection" → "Submit Reading" in sidebar
   - URL: `/data-collection/submit`

2. **Fill Out Form:**
   - **Campaign:** Select any active campaign
   - **Metric:** Select any metric (e.g., Temperature, Air Quality)
   - **GPS Location:** Two options:
     - **Option A - Auto-capture GPS:** Click "📍 Capture GPS" button
       - Browser will request location permission (grant it)
       - GPS coordinates and accuracy are **automatically captured** from device GPS
       - Accuracy value comes from `navigator.geolocation.getCurrentPosition()` API
       - You'll see: "📍 55.676098, 12.568337 (±25m accuracy)" (values vary by device/location)
     - **Option B - Manual entry:** Enter latitude/longitude manually
       - Fill in **Latitude** field (decimal degrees, -90 to 90)
       - Fill in **Longitude** field (decimal degrees, -180 to 180)
       - When manually entered, accuracy is **automatically set to 0m** (scientific best practice for surveyed/exact locations)
       - You'll see: "📍 55.676098, 12.568337 (Surveyed/exact location - 0m accuracy)"
   - **Value:** Enter a measurement (e.g., `22.5` for temperature)
   - **Notes:** (Optional) "Test QA/QC submission"
   - **Photo:** (Optional) Upload an image
   - **Device Model:** (Optional) e.g., "iPhone 14"
   - **Sensor Type:** (Optional) Select from dropdown
   - **Calibration Date:** (Optional) Select a date >90 days ago to trigger "calibration_overdue" flag

3. **Submit and Verify**
   - Click "Submit Reading"
   - Form should reset
   - Success message appears: "✓ Reading submitted successfully!"

4. **Check on Map**
   - Navigate back to Survey Map (`/maps/survey`)
   - Filter by the campaign you submitted to
   - Find your new data point:
     - **Green marker** if manually entered coordinates (accuracy = 0m) AND status = approved
     - **Blue marker** if manually entered coordinates (accuracy = 0m) AND status = pending
     - **Yellow marker** if GPS captured with accuracy > 50m (common on mobile devices)
     - **Blue marker** if GPS captured with accuracy ≤ 50m (rare, needs professional GPS)
     - **Red marker** if calibration date was >90 days ago
   - Click marker to verify all data appears in popup

**Important Notes:**
- **Manual coordinate entry:** When you manually enter lat/long, accuracy is automatically set to 0m (scientific best practice for surveyed/exact locations)
- **GPS Accuracy is automatic:** The browser provides accuracy in meters based on GPS signal quality, WiFi triangulation, and cell tower data
- **Typical GPS accuracy values:**
  - Mobile phone indoors: 50-150m (yellow marker)
  - Mobile phone outdoors: 10-50m (could be yellow or blue)
  - Professional GPS device: 1-10m (blue marker)
  - Manually surveyed coordinates: 0m (green when approved, blue when pending)
- **QA Flags auto-calculated:**
  - `location_uncertainty`: Triggered if accuracy > 80m (NOT triggered for 0m manual entries)
  - `calibration_overdue`: Triggered if calibration date > 90 days ago

### Test 2.2: Edit Existing Data Point ✅

1. **Navigate to Survey Map**
   - Click "Maps" → "Survey Map" in sidebar
   - Find any data point on the map

2. **Open Edit Form:**
   - Click on a marker to open the popup
   - Click the "✏️ Edit" link in the popup
   - Form loads pre-populated with existing data

3. **Edit Data Point:**
   - **Value:** Change the measurement value
   - **Notes:** Update or add notes
   - **Photo:** Upload a new photo (replaces old one) or leave unchanged
   - **Device/Sensor Info:** Update if needed
   - **GPS Coordinates:** Can be updated if needed

4. **Save Changes:**
   - Click "Update Reading"
   - Success message: "✓ Reading updated successfully!"
   - Form stays on edit page (doesn't redirect)
   - "Current photo" thumbnail shows immediately if photo was uploaded

5. **Verify Changes:**
   - Refresh the page - all changes persist
   - Photo displays correctly after refresh
   - Navigate back to map - marker updates reflect changes
   - Click marker popup - all updated data appears

**Photo Upload Verification:**
- **New photo upload:** File saved to `public/files/data-points/`
- **Photo persists after save:** Thumbnail appears immediately
- **Photo persists after refresh:** Still visible when page reloads
- **Old photo deleted:** When uploading new photo, old one is removed
- **No symlink needed:** Photos stored directly in `public/files/` (works on Windows + DDEV + Mutagen)


---

## Test Suite 3: Data Point Status Management ✅

**Goal:** Verify status changes for flagged data points via UI  
**Status:** COMPLETED - January 12, 2026

### Test 3.1: Change Status of Flagged Data Point ✅

1. **Find a flagged data point on map:**
   - Navigate to `/maps/survey`
   - Look for **red dashed marker** (flagged data)
   - Click it to open popup

2. **Edit and approve it:**
   - Click "✏️ Edit" in popup
   - Scroll down to **Quality Review** section (below device info)
   - Change **Status** dropdown from "Flagged" to "Approved"
   - (Optional) Add **Review Notes** like "Verified - GPS accuracy acceptable"
   - Click "Update Reading"

3. **Verify marker color changed:**
   - Navigate back to `/maps/survey`
   - Find same data point - now shows **green marker** (if accuracy ≤ 50m)

4. **Test rejection workflow:**
   - Find an approved data point (green marker)
   - Click "✏️ Edit" in popup
   - Change **Status** to "Rejected"
   - Add **Review Notes** like "Data quality issues - sensor malfunction"
   - Click "Update Reading"
   - Navigate back to map - marker now shows **gray dashed circle**

**Note:** Status and Review Notes fields only appear when editing existing data points, not when submitting new ones.

---

## Test Suite 4: Automatic Satellite Enrichment (Tasks 1.6, 1.7) ✅

**Goal:** Verify that new data points trigger background satellite analysis job (UX verification only - detailed tests in automated suite)
**Status:** COMPLETED - January 12, 2026

### Test 4.1: Verify Queue Job Dispatches When Submitting Data ✅

1. **Submit a New Data Point**
   - Follow Test 2.1 steps above
   - Submit a reading with valid GPS coordinates

2. **Check Queue Status**
   ```powershell
   # Check if job was queued
   ddev artisan queue:monitor database
   ```

3. **Expected Results:**
   - Job `EnrichDataPointWithSatelliteData` should appear in queue
   - Queue worker (auto-running) processes it within seconds
   - No errors in browser console

4. **Optional: Check Logs**
   ```powershell
   ddev exec bash -c "tail -20 storage/logs/laravel.log"
   ```
   - Look for: "Successfully enriched DataPoint {id}" or "Failed to fetch NDVI..." (if API down)

**Note:** Detailed testing of satellite enrichment logic is in automated tests (`tests/Feature/SatelliteEnrichmentTest.php`). This UX test just verifies the job dispatches correctly from the UI.

---

> **Note:** Tests for Survey Zones (spatial methods), Campaign map centering, and DataPoint relationships have been moved to automated Pest tests. See `tests/Feature/` for these tests. This UX testing document now focuses only on browser-based manual testing.

---

## Test Suite 8: Visual Inspection on Maps ✅

**Goal:** Manually verify UI updates and visual quality  
**Status:** COMPLETED - January 12, 2026

### Test 8.1: Survey Map Visual Quality ✅

1. **Open Survey Map:** `/maps/survey`
2. **Check Visual Elements:**
   - ✅ Map loads without errors
   - ✅ Markers cluster when zoomed out
   - ✅ Markers show different colors (green/yellow/red/blue)
   - ✅ Filter dropdowns populate correctly
   - ✅ Point count badge shows correct number
   - ✅ Map auto-zooms to fit data

3. **Interact with Map:**
   - Click cluster: Should expand to show individual markers
   - Click marker: Popup shows data point details
   - Change filters: Map updates reactively
   - Click "Reset View": Map resets to campaign bounds

### Test 8.2: Satellite Viewer (Existing Feature - Verify No Regression) ✅

1. **Open Satellite Viewer:** `/maps/satellite`
2. **Verify Still Works:**
   - ✅ Map loads with satellite imagery
   - ✅ Campaign filter works
   - ✅ Date picker functional
   - ✅ NDVI overlay works
   - ✅ Moisture overlay works

---

## Troubleshooting Guide

### Issue: No markers on Survey Map

**Possible Causes:**
- No data points in database
- Filter excluding all data
- JavaScript errors

**Solutions:**
```powershell
# Reseed database
ddev artisan migrate:fresh --seed

# Check browser console for errors
# Press F12 → Console tab
```

### Issue: Queue job not processing

**Check Queue Worker:**
```powershell
# Verify queue worker is running
ddev exec bash -c "ps aux | grep -E 'queue:work' | grep -v grep"

# Expected output should show:
# erik  1967  php artisan queue:work --sleep=3...

# If not running, restart DDEV
ddev restart
```

### Issue: Satellite enrichment fails

**Expected Behavior:**
- API may be down or credentials missing
- Job should log error but not crash
- Application continues working normally

**Check Logs:**
```powershell
ddev exec bash -c "tail -50 storage/logs/laravel.log | grep -E 'Copernicus|NDVI|satellite'"
```

### Issue: Tinker commands fail

**Common Fixes:**
```powershell
# Clear caches
ddev artisan optimize:clear

# Regenerate autoloader
ddev composer dump-autoload

# Try again
ddev artisan tinker
```

---

## Success Criteria Checklist

After completing all tests, verify:

### Priority 0 & 1: UX Testing Complete ✅
- [x] Survey map shows color-coded markers based on quality
- [x] Low accuracy (>50m) shows yellow dashed markers
- [x] Approved data shows green solid markers
- [x] Flagged data shows red dashed markers
- [x] QA/QC fields can be entered via data submission form
- [x] Data point status can be changed via edit form
- [x] Queue jobs dispatch when submitting data points
- [x] No visual regressions on existing maps
- [x] Forms validate correctly and show appropriate errors
- [x] Photos upload and display correctly

### Backend Testing (Automated)
- [ ] All automated tests passing: `ddev artisan test`
- [ ] Model relationships tested in `tests/Feature/`
- [ ] Spatial queries tested in `tests/Feature/SurveyZoneTest.php`
- [ ] Satellite enrichment tested in `tests/Feature/SatelliteEnrichmentTest.php`

**Note:** Backend logic (relationships, spatial methods, scopes) should be verified with automated Pest tests, not manual Tinker commands.

---

## Performance Notes

**What Should Be Fast:**
- Map loading (<2s)
- Filter changes (<1s)
- Form submissions (<1s)
- Queue job dispatch (immediate)
- Queue job processing (3-10s depending on API)

**What May Be Slow:**
- Initial satellite API calls (5-15s, but cached)
- Complex PostGIS queries on large datasets (optimized with indexes)

---

## Browser Console Commands (Advanced Testing)

Open browser console (F12 → Console) on Survey Map:

```javascript
// Check if map data is loaded
console.log(window.mapData);

// Check if map instance exists
console.log(window.surveyMap);

// Check cluster group
console.log(window.surveyClusterGroup);

// Manually trigger map resize
if (window.surveyMap) {
    window.surveyMap.invalidateSize();
}
```

---

## Next Steps After Testing

1. **Document Issues:**
   - Note any bugs or unexpected behavior
   - Take screenshots of visual issues
   - Copy error messages from console/logs

2. **Verify Test Results:**
   - All 144 tests should pass: `ddev artisan test`
   - No console errors in browser

3. **Code Formatting:**
   ```powershell
   ddev pint --dirty
   ```

4. **Ready for Priority 2:**
   - If all tests pass, proceed to Priority 2 tasks
   - Priority 2 will add data point overlays to satellite viewer

---

## Quick Reference Commands

```powershell
# Start/Stop
ddev start
ddev stop
ddev restart

# Database
ddev artisan migrate:fresh --seed
ddev artisan tinker

# Queue
ddev artisan queue:monitor database
ddev artisan queue:restart

# Tests
ddev artisan test
ddev artisan test --filter=DataPoint

# Logs
ddev logs
ddev exec bash -c "tail -50 storage/logs/laravel.log"

# Code Quality
ddev pint --dirty
```

---

**Testing Duration:** 30-45 minutes  
**Last Updated:** January 12, 2026  
**Status:** UX TESTS COMPLETED ✅ (Backend tests moved to automated suite)

## Test Completion Summary (January 12, 2026)

### ✅ UX Test Suites Completed (Browser-based Manual Testing):
1. ✅ **Test Suite 1**: Visual Differentiation on Survey Map
2. ✅ **Test Suite 2**: QA/QC Workflow
3. ✅ **Test Suite 3**: Data Point Status Management
4. ✅ **Test Suite 4**: Automatic Satellite Enrichment (Queue Dispatch Verification)
5. ✅ **Test Suite 8**: Visual Inspection on Maps

### 🔄 Backend Tests (Moved to Automated Pest Tests):
These tests were originally in this document but have been moved to `tests/Feature/`:
- **Survey Zones & Spatial Methods** → `tests/Feature/SurveyZoneTest.php`
- **Campaign Map Centering** → `tests/Feature/CampaignTest.php`
- **Data Point Relationships** → `tests/Feature/DataPointTest.php`
- **Satellite Analysis Records** → `tests/Feature/SatelliteEnrichmentTest.php`

### Priority 0 & Priority 1 Features: UX Testing COMPLETE ✅

**What This Document Tests:** Browser-based UI functionality
**What Automated Tests Cover:** Backend logic, relationships, spatial queries, scopes

**Next steps:**
- Run full automated test suite: `ddev artisan test`
- Verify all backend tests pass
- Code formatting: `ddev pint --dirty`
- Ready to proceed with Priority 2 tasks
