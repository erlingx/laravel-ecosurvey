# Phase 4 Features - Browser Testing Cookbook

**Last Updated:** January 15, 2026  
**Estimated Time:** 18-20 minutes  
**Prerequisites:** Logged in as authenticated user

---

## Quick Test Checklist

- [ ] Campaign CRUD (Filament Admin) (3 min)
- [ ] Survey Zone Management (5 min)
- [ ] Satellite Viewer with Zones (3 min)
- [ ] Data Point Temporal Correlation (3 min)
- [ ] Scientific Data Export (2 min)
- [ ] Advanced PostGIS (Backend - Optional)

---

## 1. Campaign CRUD (3 minutes)

### Test: List Campaigns

**URL:** `/admin/campaigns`

**Expected Results:**
✅ Table displays all campaigns  
✅ Navigation badge shows active count  
✅ Searchable name column  
✅ Status badges (draft/active/completed/archived)  
✅ Data points and zones counts visible

---

### Test: Create Campaign

**Steps:**
1. Click **Create** button
2. Enter name: "Test Campaign 2026"
3. Enter description: "Browser testing"
4. Select status: **Active**
5. Click **Create**

**Expected Results:**
✅ Redirects to edit page  
✅ Campaign saved to database  
✅ User auto-assigned as owner

---

### Test: Edit Campaign

**Steps:**
1. From list, click **Edit** on any campaign
2. Change status to **Completed**
3. Click **Save**

**Expected Results:**
✅ Updates persist  
✅ Statistics visible (data points, approved, zones)  
✅ Delete button in header

---

### Test: Manage Zones Link

**Steps:**
1. From campaign list, click **Actions** dropdown on a campaign
2. Click **Manage Zones**

**Expected Results:**
✅ Opens zone manager in new tab  
✅ Correct campaign loaded

---

### Test: Delete Campaign

**Steps:**
1. Edit a test campaign
2. Click **Delete** in header
3. Confirm deletion

**Expected Results:**
✅ Campaign removed  
✅ Redirects to list

---

## 2. Survey Zone Management (5 minutes)

### Test: Create a Survey Zone

**URL:** `/campaigns/2/zones/manage` (Noise Pollution Study)

**Steps:**
1. Click the **polygon icon** (⬡) in the map toolbar
2. Click 5-6 points on the map to draw a zone boundary
3. Double-click to complete the polygon
4. When prompted, enter:
   - **Name:** "Test Zone Alpha"
   - **Description:** "Created during Phase 4 testing"
5. Zone should appear in the right sidebar
6. Verify **area is calculated** (e.g., "2.80 km²")

**Expected Results:**
✅ Polygon appears on map with blue dashed border  
✅ Zone listed in sidebar with name and area  
✅ Data points visible as green circles  
✅ Success message: "Survey zone 'Test Zone Alpha' created successfully!"

---

### Test: Edit Zone Metadata

**Steps:**
1. Find "Test Zone Alpha" in sidebar
2. Click **Edit** button
3. Change name to "Modified Test Zone"
4. Add description: "Updated during testing"
5. Click **Save**

**Expected Results:**
✅ Zone name updates in sidebar  
✅ Success message appears  
✅ Zone still visible on map

---

### Test: Delete Zone

**Steps:**
1. Find "Modified Test Zone" in sidebar
2. Click **Delete** button
3. Confirm deletion in modal

**Expected Results:**
✅ Zone removed from sidebar  
✅ Zone removed from map  
✅ Success message appears  
✅ Data points still visible (not deleted)

---

## 3. Satellite Viewer with Survey Zones (3 minutes)

### Test: View Survey Zones on Satellite Map

**URL:** `/maps/satellite`

**Steps:**
1. Select **Campaign:** "Noise Pollution Study"
2. Choose any **Date** (e.g., current date)
3. Check **"Show Field Data"** checkbox
4. Observe the map

**Expected Results:**
✅ Survey zones display as **blue dashed polygons**  
✅ Data points show as **colored circles** (clustered)  
✅ Zone has **interactive popup** (click to see details)  
✅ Popup shows: Zone name, description, area in km²

---

### Test: Zone Popup Details

**Steps:**
1. Click on a survey zone polygon
2. Read popup content

**Expected Results:**
✅ Popup displays:
   - Zone name (e.g., "Central Copenhagen Zone")
   - Description (if available)
   - Area: X.XX km²

---

## 4. Temporal Correlation Visualization (3 minutes)

### Test: Color-Coded Data Points

**URL:** `/maps/satellite`

**Steps:**
1. Select **Campaign:** "Noise Pollution Study"
2. Select **Date:** Any date with satellite coverage
3. Check **"Show Field Data"**
4. Look at marker colors

**Expected Results:**
✅ Markers are color-coded:
   - 🟢 **Green** = 0-3 days difference (Excellent)
   - 🟡 **Yellow** = 4-7 days (Good)
   - 🟠 **Orange** = 8-14 days (Acceptable)
   - 🔴 **Red** = 15+ days (Poor)

---

### Test: Temporal Alignment Legend

**Steps:**
1. With data points visible, look at **top-right corner** of map
2. Verify legend is visible

**Expected Results:**
✅ Legend shows color scale with day ranges  
✅ Info tooltip (ⓘ) explains temporal alignment  
✅ Legend only shows when data points are visible

---

### Test: Data Point Popup

**Steps:**
1. Click any data point marker
2. Read popup content

**Expected Results:**
✅ Popup shows:
   - Metric name and value
   - Collection date/time
   - GPS accuracy
   - **Temporal Alignment:** Quality label (Excellent/Good/Acceptable/Poor)
   - **Days from satellite:** X day(s)
   - "📅 View satellite on [DATE]" button

---

### Test: Jump to Data Point

**Steps:**
1. Click "📅 View satellite on [DATE]" button in popup
2. Observe behavior

**Expected Results:**
✅ Satellite date **auto-updates** to match field data collection date  
✅ Map **re-centers** (smooth animation, no erratic zoom)  
✅ Satellite imagery refreshes (if available for that date)

---

## 5. Scientific Data Export (2 minutes)

### Test: JSON Export

**URL:** `/campaigns/2/export/json`

**Steps:**
1. Navigate to URL (or click export link if available)
2. Browser downloads a JSON file

**Expected Results:**
✅ File downloads: `campaign-2-export.json` (or similar)  
✅ File contains:
   - `metadata` object (campaign info, export date, QA stats)
   - `satellite_indices` list (all 7 indices)
   - `data_points` array with approved data
   - Each point has: location, measurement, quality_control, satellite_context

---

### Test: CSV Export

**URL:** `/campaigns/2/export/csv`

**Steps:**
1. Navigate to URL
2. Browser downloads a CSV file
3. Open in Excel/Sheets

**Expected Results:**
✅ File downloads: `campaign-2-export.csv`  
✅ Headers include:
   - `id, collected_at, latitude, longitude, accuracy_meters`
   - `metric_name, metric_unit, measurement_value`
   - `ndvi, ndmi, ndre, evi, msi, savi, gndvi`
   - `satellite_date, temporal_offset_days, temporal_quality`
   - `device_model, sensor_type, notes`
✅ Data is properly formatted (no malformed CSV)

---

### Test: Export Data Integrity

**Steps:**
1. Open JSON export in a text editor
2. Check satellite_context for a data point

**Expected Results:**
✅ All 7 satellite indices present (or null if not available):
   - `ndvi_value`
   - `ndmi_value`
   - `ndre_value`
   - `evi_value`
   - `msi_value`
   - `savi_value`
   - `gndvi_value`
✅ `temporal_quality` is one of: excellent/good/acceptable/poor/no_satellite_data

---

## 6. Advanced PostGIS Operations (Backend - Optional)

These are backend methods - test via Tinker if desired.

### Test: Zone Statistics

**Command:**
```bash
ddev artisan tinker
```

**Code:**
```php
$service = app(\App\Services\GeospatialService::class);
$stats = $service->getZoneStatistics(2); // Campaign ID 2
print_r($stats);
```

**Expected Results:**
✅ Array of zone statistics with:
   - `zone_name`, `metric_name`, `point_count`
   - `avg_value`, `min_value`, `max_value`, `stddev_value`

---

### Test: Nearest Neighbors (KNN)

**Code:**
```php
$service = app(\App\Services\GeospatialService::class);
$nearest = $service->findNearestDataPoints(55.6761, 12.5683, 5);
print_r($nearest);
```

**Expected Results:**
✅ Array of 5 nearest data points  
✅ Each has: `id`, `value`, `metric_name`, `latitude`, `longitude`, `distance_meters`  
✅ Ordered by distance (closest first)

---

### Test: DBSCAN Clustering

**Code:**
```php
$service = app(\App\Services\GeospatialService::class);
$clusters = $service->detectClusters(2, 1, 0.01, 5);
print_r($clusters);
```

**Expected Results:**
✅ Array of detected clusters  
✅ Each cluster has: `cluster_id`, `point_count`, `avg_value`, `center_latitude`, `center_longitude`, `points`  
✅ Noise points filtered out

---

### Test: Convex Hull

**Code:**
```php
$service = app(\App\Services\GeospatialService::class);
$hull = $service->getCampaignConvexHull(2);
print_r($hull);
```

**Expected Results:**
✅ GeoJSON Feature with polygon geometry  
✅ Properties include: `area_square_meters`, `area_hectares`

---

## Troubleshooting

### Survey Zone Not Displaying
**Issue:** Zone created but not visible on map  
**Solution:** Refresh page, check zone list in sidebar

---

### Data Points Not Showing
**Issue:** "Show Field Data" checked but no markers  
**Solution:** Verify campaign has approved data points, check campaign filter

---

### Satellite Overlay Missing
**Issue:** No satellite imagery visible  
**Solution:** Check selected date has satellite coverage (try nearby dates)

---

### Export Returns 404
**Issue:** Export URL returns "Not Found"  
**Solution:** Verify campaign ID exists, check authentication

---

### Temporal Colors All Same
**Issue:** All markers are one color  
**Solution:** Check satellite date is different from collection dates

---

## Quick Reference

### URLs to Test
- Campaign CRUD: `/admin/campaigns`
- Zone Manager: `/campaigns/{id}/zones/manage`
- Satellite Viewer: `/maps/satellite`
- JSON Export: `/campaigns/{id}/export/json`
- CSV Export: `/campaigns/{id}/export/csv`

### Campaign IDs (Seeded Data)
- **1** - Fælledparken Green Space Study
- **2** - Noise Pollution Study

### Color Code Legend
- 🟢 Green = 0-3 days (Excellent temporal alignment)
- 🟡 Yellow = 4-7 days (Good)
- 🟠 Orange = 8-14 days (Acceptable)
- 🔴 Red = 15+ days (Poor)

---

## Success Criteria

After completing this cookbook, you should have:

✅ Created and managed campaigns via Filament admin  
✅ Created at least one survey zone visually  
✅ Seen zones displayed on satellite map  
✅ Verified temporal correlation color-coding  
✅ Downloaded JSON and CSV exports  
✅ Confirmed all 7 satellite indices in exports  
✅ Tested zone metadata editing  
✅ Verified zone deletion works  

**Total Time:** ~18 minutes  
**Features Tested:** 7 major Phase 4 features

---

**Questions or Issues?** Check the troubleshooting section or consult the user guides:
- `docs/06-user-guide/Survey-Zone-Manager-Guide.md`
- `docs/06-user-guide/Satellite-Viewer-Guide.md`

