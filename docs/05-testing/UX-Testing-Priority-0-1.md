# UX Testing Cookbook - Priority 0 & 1 Features

**Date:** January 8, 2026  
**Scope:** Priority 0 (Critical Fixes) + Priority 1 (Foundation)  
**Test Duration:** ~30-45 minutes  
**Prerequisites:** Application running with `ddev start`, logged in as authenticated user

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
     - 🟢 **Green solid circles** = Approved high-quality data (`status = approved`, `accuracy <= 50m`)
     - 🟡 **Yellow dashed circles** = Low confidence data (`accuracy > 50m`)
     - 🔴 **Red dashed circles** = Flagged data (has `qa_flags`)
     - 🔵 **Blue solid circles** = Normal/pending data

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

## Test Suite 3: Automatic Satellite Enrichment (Tasks 1.6, 1.7)

**Goal:** Verify that new data points trigger background satellite analysis job

### Test 3.1: Monitor Queue Job Dispatch

1. **Check Queue Status Before**
   ```powershell
   ddev artisan queue:monitor database
   ```

2. **Submit a New Data Point**
   - Follow Test 2.1 steps above
   - Submit a reading with valid GPS coordinates

3. **Check Queue Immediately After Submission**
   ```powershell
   # Check if job was queued
   ddev artisan queue:monitor database
   
   # OR check logs
   ddev exec bash -c "tail -20 storage/logs/laravel.log"
   ```

4. **Expected Results:**
   - Job `EnrichDataPointWithSatelliteData` should be queued
   - Queue worker (auto-running) processes it within seconds
   - Look for log entries like:
     - "Successfully enriched DataPoint {id} with satellite data"
     - OR "Failed to fetch NDVI..." (if API issues)

### Test 3.2: Verify SatelliteAnalysis Records Created

1. **Check Database** (using Tinker):
   ```powershell
   ddev artisan tinker
   ```

2. **Run in Tinker:**
   ```php
   // Get the most recent data point
   $dataPoint = \App\Models\DataPoint::latest()->first();
   
   // Check satellite analyses
   $dataPoint->satelliteAnalyses;
   
   // Should show records with NDVI and/or moisture data
   // Example output:
   // Illuminate\Database\Eloquent\Collection {
   //   #items: array:2 [
   //     0 => App\Models\SatelliteAnalysis {
   //       #attributes: array [
   //         "ndvi_value" => "0.6543"
   //         "satellite_source" => "Copernicus Sentinel-2"
   //       ]
   //     }
   //   ]
   // }
   
   exit
   ```

**Note:** If API credentials are not configured or API is down, the job will log errors but won't crash. This is expected behavior.

---

## Test Suite 4: Survey Zones & Spatial Methods (Task 1.8)

**Goal:** Test SurveyZone model's PostGIS spatial methods

### Test 4.1: Create Survey Zone via Tinker

1. **Open Tinker:**
   ```powershell
   ddev artisan tinker
   ```

2. **Create a Test Zone:**
   ```php
   use App\Models\SurveyZone;
   use App\Models\Campaign;
   
   // Get first campaign
   $campaign = Campaign::first();
   
   // Create zone using factory
   $zone = SurveyZone::factory()->create([
       'campaign_id' => $campaign->id,
       'name' => 'Test Zone - Copenhagen Park'
   ]);
   
   // Wait for factory afterCreating hook to set geometry
   $zone->refresh();
   
   echo "Zone created with ID: {$zone->id}\n";
   ```

3. **Test Spatial Methods:**
   ```php
   // Calculate area (should return ~2-5 km²)
   $area = $zone->calculateArea();
   echo "Area: {$area} km²\n";
   
   // Get centroid [lon, lat]
   $centroid = $zone->getCentroid();
   echo "Centroid: " . json_encode($centroid) . "\n";
   
   // Get bounding box [minLon, minLat, maxLon, maxLat]
   $bbox = $zone->getBoundingBox();
   echo "Bounding Box: " . json_encode($bbox) . "\n";
   
   // Export as GeoJSON
   $geojson = $zone->toGeoJSON();
   echo "GeoJSON Feature: " . json_encode($geojson, JSON_PRETTY_PRINT) . "\n";
   
   exit
   ```

4. **Expected Results:**
   - Area: ~0.5 to 3 km² (varies due to random generation)
   - Centroid: Coordinates near Copenhagen (55.6761, 12.5683)
   - Bounding Box: 4 coordinates defining rectangle
   - GeoJSON: Valid Feature with Polygon geometry

### Test 4.2: Test Contained Data Points Query

```php
// In Tinker
use App\Models\SurveyZone;

$zone = SurveyZone::first();

// Get data points within zone
$contained = $zone->getContainedDataPoints();

echo "Data points in zone: " . count($contained) . "\n";

// Note: May be 0 if data points don't fall within the zone geometry
exit
```

---

## Test Suite 5: Campaign Map Centering (Task 1.9)

**Goal:** Verify intelligent map center calculation

### Test 5.1: Test getMapCenter() Method

1. **Open Tinker:**
   ```powershell
   ddev artisan tinker
   ```

2. **Test Different Scenarios:**
   ```php
   use App\Models\Campaign;
   
   // Scenario 1: Campaign with survey zone
   $campaign = Campaign::has('surveyZones')->first();
   if ($campaign) {
       $center = $campaign->getMapCenter();
       echo "Campaign '{$campaign->name}' center (from zone): " . json_encode($center) . "\n";
       // Expected: Centroid of survey zone
   }
   
   // Scenario 2: Campaign with only data points (no zone)
   $campaign = Campaign::has('dataPoints')->doesntHave('surveyZones')->first();
   if ($campaign) {
       $center = $campaign->getMapCenter();
       echo "Campaign '{$campaign->name}' center (from data points): " . json_encode($center) . "\n";
       // Expected: Average of data point locations
   }
   
   // Scenario 3: Empty campaign (no zone, no data points)
   $campaign = Campaign::factory()->create([
       'name' => 'Empty Test Campaign',
       'status' => 'planning'
   ]);
   $center = $campaign->getMapCenter();
   echo "Empty campaign center (default): " . json_encode($center) . "\n";
   // Expected: [12.5683, 55.6761] (Copenhagen default)
   
   // Cleanup
   $campaign->delete();
   
   exit
   ```

3. **Expected Outputs:**
   - Zone-based: Coordinates matching zone centroid
   - Data-based: Average of data point coordinates
   - Default: `[12.5683, 55.6761]`

---

## Test Suite 6: Data Point Relationships (Priority 0 Fixes)

**Goal:** Verify all model relationships work correctly

### Test 6.1: Test DataPoint Relationships

```php
// In Tinker
use App\Models\DataPoint;

$dataPoint = DataPoint::with(['campaign', 'environmentalMetric', 'user', 'surveyZone', 'reviewer', 'satelliteAnalyses'])->first();

// Test each relationship
echo "Campaign: " . $dataPoint->campaign->name . "\n";
echo "Metric: " . $dataPoint->environmentalMetric->name . "\n";
echo "User: " . $dataPoint->user->name . "\n";
echo "Survey Zone: " . ($dataPoint->surveyZone ? $dataPoint->surveyZone->name : 'None') . "\n";
echo "Reviewer: " . ($dataPoint->reviewer ? $dataPoint->reviewer->name : 'None') . "\n";
echo "Satellite Analyses: " . $dataPoint->satelliteAnalyses->count() . " records\n";

exit
```

### Test 6.2: Test High Quality Scope

```php
// In Tinker
use App\Models\DataPoint;

// Get high quality data points
$highQuality = DataPoint::highQuality()->get();

echo "High quality data points: " . $highQuality->count() . "\n";

// Verify criteria
foreach ($highQuality->take(3) as $dp) {
    echo "ID: {$dp->id}, Status: {$dp->status}, Accuracy: {$dp->accuracy}m\n";
    // All should have status='approved' AND accuracy <= 50
}

exit
```

---

## Test Suite 7: Visual Inspection on Maps

**Goal:** Manually verify UI updates and visual quality

### Test 7.1: Survey Map Visual Quality

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

### Test 7.2: Satellite Viewer (Existing Feature - Verify No Regression)

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

### Priority 0: Critical Fixes ✅
- [ ] DataPoint model has all relationships working
- [ ] Campaign model has surveyZones relationship
- [ ] No regression in existing functionality

### Priority 1: Foundation ✅
- [x] QA/QC fields exist in database (check migrations)
- [x] DataPoint model has QA/QC fields in $fillable
- [x] Survey map shows color-coded markers based on quality
- [x] Low accuracy (>50m) shows yellow dashed markers
- [x] Approved data shows green solid markers
- [x] Flagged data shows red dashed markers
- [ ] New data points trigger EnrichDataPointWithSatelliteData job
- [ ] SatelliteAnalysis records created automatically (when API available)
- [ ] SurveyZone model has all PostGIS methods working
- [ ] Campaign getMapCenter() returns correct coordinates
- [ ] All 144 tests passing in test suite

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
**Last Updated:** January 8, 2026  
**Status:** Ready for UX Testing ✅
