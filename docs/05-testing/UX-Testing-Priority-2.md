# UX Testing Cookbook - Priority 2: Integration

**Date:** January 12, 2026  
**Scope:** Priority 2 (Manual Data + Satellite Integration)  
**Test Duration:** ~20-30 minutes  
**Prerequisites:** 
- Application running with `ddev start`
- Logged in as authenticated user
- At least one campaign with data points exists
- At least one campaign with survey zone exists

---

## Setup Before Testing

1. **Ensure test data exists:**
   ```powershell
   ddev artisan tinker
   ```
   
   ```php
   // Check campaigns with data points
   \App\Models\Campaign::has('dataPoints')->count();
   
   // Check campaigns with survey zones
   \App\Models\Campaign::has('surveyZones')->count();
   
   exit
   ```

2. **If needed, create test data:**
   ```powershell
   ddev artisan db:seed --class=CampaignSeeder
   ```

---

## Test Suite 1: DataPoints Overlay on Satellite Viewer (Task 2.1) ✅ COMPLETED

**Goal:** Verify data points display correctly on satellite map with toggle functionality

### Test 1.1: Navigate to Satellite Viewer ✅ PASSED

1. **Navigate to:** `/maps/satellite`
2. **Expected Results:**
   - ✅ Satellite map loads successfully
   - ✅ Map displays satellite imagery
   - ✅ UI controls are visible (Campaign selector, Data Overlay, Date picker, Data Points toggle)

---

### Test 1.2: Verify Data Points Toggle Control ✅ PASSED

1. **Locate the "Data Points" control** (4th column in filter grid)
2. **Expected Results:**
   - ✅ Checkbox labeled "Show Field Data" is visible
   - ✅ Checkbox is checked by default
   - ✅ Control is clickable

---

### Test 1.3: Select Campaign with Data Points ✅ PASSED

1. **Select a campaign** from "Campaign Location" dropdown (choose "Fælledparken Green Space Study")
2. **Verify date is August 15, 2025** (default date with known satellite coverage)
3. **Expected Results:**
   - ✅ Map centers on campaign location (Fælledparken park)
   - ✅ Data point markers appear on the map (should see 100+ markers across the park)
   - ✅ Markers are visible as colored circles:
     - Green circles = approved data points
     - Gray circles = pending/draft data points
   - ✅ Markers overlay on top of satellite imagery
   - ✅ Console shows: `📍 Adding 100+ data points to map`

> **Note:** The seeder creates data for **August 1-30, 2025** to match the satellite imagery availability period.

---

### Test 1.4: Toggle Data Points Visibility ✅ PASSED

1. **Uncheck "Show Field Data" checkbox**
2. **Expected Results:**
   - ✅ All data point markers disappear from map
   - ✅ Satellite imagery remains visible
   
3. **Re-check "Show Field Data" checkbox**
4. **Expected Results:**
   - ✅ Data point markers reappear
   - ✅ Markers are in same positions as before

---

### Test 1.5: Verify Data Point Popups ✅ PASSED

1. **Click on any data point marker**
2. **Expected Results:**
   - ✅ Popup appears above marker
   - ✅ Popup shows:
     - Metric name (e.g., "PM2.5", "Temperature")
     - Value with unit (e.g., "25.5 µg/m³")
     - Collection date/time
     - Accuracy (e.g., "±5m")
   - ✅ Popup includes button: "🔍 Click to analyze satellite data"

---

## Test Suite 2: Click-to-Analyze Interaction (Task 2.2) ✅ COMPLETED

**Goal:** Verify temporal correlation analysis - comparing field measurements with satellite data from the same date

**🔬 SCIENTIFIC RATIONALE:**
The "View satellite on [DATE]" button enables **temporal correlation analysis**:
- Field measurement taken on August 10, 2025
- Clicking button jumps to satellite imagery from August 10, 2025
- Compare ground truth (field data) with remote sensing (satellite) from same day
- **This is scientific best practice:** Temporal alignment is critical for validation

**Key Features:**
- Button shows **target date** in text: "📅 View satellite on Aug 10, 2025"
- **Always syncs date** for temporal correlation (no toggle needed)
- Provides immediate visual feedback of what will happen
- Tooltip explains: "Compare field data with satellite conditions from that day"

---

### Test 2.1: Temporal Correlation Analysis ✅ PASSED

1. **Note current satellite date** (e.g., August 15, 2025)
2. **Click on a data point marker** to open popup
3. **Check button text** - should show: "📅 View satellite on [DATE]" where DATE is when the measurement was taken
4. **Click the button**
5. **Expected Results:**
   - ✅ Popup closes automatically
   - ✅ Map smoothly flies to clicked data point location (if not already there)
   - ✅ **Date picker ALWAYS updates** to data point's collection date
   - ✅ Satellite overlay refreshes for the new date
   - ✅ Location coordinates update to match data point
   - ✅ NDVI/Moisture analysis panel updates with data from the field measurement date
   - ✅ Console shows: `📅 Date synced to field data collection date for temporal analysis`

**Scientific Value:**
- ✅ You now see satellite conditions on the **same day** the measurement was taken
- ✅ You can validate: Does satellite NDVI match the field observation?
- ✅ You can assess: Was vegetation health/moisture level consistent between ground truth and satellite?

**Troubleshooting:**
- **If date doesn't update:** Check browser console for errors - this is a bug (should ALWAYS update)
- **If button text doesn't show date:** JavaScript error in popup creation - check console
- **If zoom behavior is erratic:** Try clicking individual markers instead of cluster icons

**Browser Console Check:**
```
🎯 Jumping to data point for temporal correlation: { latitude: ..., longitude: ..., date: ... }
✅ Jump event dispatched for temporal correlation analysis
📅 Date synced to field data collection date for temporal analysis { date: "2025-08-10" }
✅ Jump completed - ready for temporal correlation analysis { newDate: "2025-08-10", ... }
```

---

### Test 2.2: Verify Coordinate Updates ✅ PASSED

1. **Before clicking:** Note the displayed coordinates in "Location" info box
2. **Click a data point** in a different location
3. **After clicking:** Check coordinates again
4. **Expected Results:**
   - ✅ Coordinates change to match clicked data point
   - ✅ Latitude and longitude values are accurate (match data point location)
   - ✅ Map viewport centers on new coordinates

---

### Test 2.3: Test Multiple Sequential Clicks ✅ PASSED

1. **Click first data point marker**
2. **Wait for map to update**
3. **Click second data point marker** (different location)
4. **Wait for map to update**
5. **Click third data point marker**
6. **Expected Results:**
   - ✅ Each click updates the map correctly
   - ✅ No lag or unresponsive behavior
   - ✅ Previous marker selections don't interfere
   - ✅ Satellite overlay updates each time

---

## Test Suite 3: Survey Zone Centering (Task 2.3)

**Goal:** Verify satellite viewer uses survey zone centroid for intelligent map centering

### Test 3.1: Campaign with Survey Zone

1. **Select a campaign** that has a survey zone
2. **Expected Results:**
   - ✅ Map centers on survey zone centroid (geometric center)
   - ✅ Survey zone boundary is visible (if zone overlay exists)
   - ✅ Coordinates shown match zone's centroid
   - ✅ Map zoom level shows entire zone area

**How to verify centroid:**
```powershell
ddev artisan tinker
```

```php
$campaign = \App\Models\Campaign::has('surveyZones')->first();
$zone = $campaign->surveyZones->first();
$centroid = $zone->getCentroid();
echo "Expected centroid: " . json_encode($centroid);
exit
```

Compare displayed coordinates with expected centroid values.

---

### Test 3.2: Campaign with Only Data Points (No Zone)

1. **Select a campaign** that has data points but NO survey zone
2. **Expected Results:**
   - ✅ Map centers on first data point location
   - ✅ Data points are visible
   - ✅ Coordinates match first data point's location

---

### Test 3.3: Empty Campaign (Fallback)

1. **Select "Fælledparken (Default)" option** (no campaign selected)
2. **Expected Results:**
   - ✅ Map centers on Copenhagen default: 55.7072°N, 12.5704°E
   - ✅ Default location is displayed correctly
   - ✅ Map loads satellite imagery for Copenhagen

---

### Test 3.4: Verify Priority Logic

**Test the 3-tier priority system:**

1. **Tier 1 - Survey Zone Priority:**
   - Select campaign with survey zone → Should use zone centroid ✅

2. **Tier 2 - Data Point Fallback:**
   - Select campaign with only data points → Should use first point ✅

3. **Tier 3 - Default Fallback:**
   - Select "Default" option → Should use Copenhagen coordinates ✅

---

## Test Suite 4: Data Overlay Types

**Goal:** Verify data points work correctly with different satellite overlays

---

### 📖 How Data Overlay Works (Date Behavior Explained)

**IMPORTANT:** The satellite viewer operates with **TWO INDEPENDENT DATA LAYERS:**

#### Layer 1: Satellite Imagery (Date-Specific)
- **Source:** Copernicus Sentinel-2 satellite data
- **Date-Driven:** Uses the "Imagery Date" picker (e.g., August 15, 2025)
- **Availability:** Only available for dates when satellite passed over the location
- **What you see:** NDVI overlay, Moisture overlay, or True Color imagery **for that specific date**

#### Layer 2: Data Points (Campaign-Specific, Date-Independent)
- **Source:** Manual field measurements stored in database
- **Toggle:** "Show Field Data" checkbox
- **Behavior:** Shows **ALL data points** from selected campaign, regardless of date
- **What you see:** Colored circle markers showing all field measurements

---

### 🔍 Date Scenarios Explained

**Scenario 1: Date with ONLY satellite data (no manual data)**
- ✅ Satellite overlay displays (NDVI/Moisture/TrueColor)
- ✅ Data points layer shows (if enabled) - displays ALL campaign data points
- ⚠️ **Important:** Data points shown are NOT filtered by the satellite date
- 👉 **Example:** Select August 15, 2025 → Satellite shows that day's imagery, but data points show all measurements from entire campaign

**Scenario 2: Date with ONLY manual data (no satellite coverage)**
- ❌ Satellite overlay will NOT display (no imagery available for that date)
- ✅ Data points layer shows (if enabled) - displays ALL campaign data points
- ℹ️ Map shows base layer only (no colored overlay)
- 👉 **Example:** Select a date with no satellite pass → No NDVI/Moisture overlay, but field data markers still visible

**Scenario 3: Date with BOTH satellite AND manual data**
- ✅ Satellite overlay displays for that specific date
- ✅ Data points layer shows ALL campaign measurements (not date-filtered)
- ✅ **Best case:** You can visually compare field measurements with satellite analysis
- 👉 **Example:** August 15, 2025 at Fælledparken → See both satellite NDVI + all 100+ field data markers

**Scenario 4: Date with NEITHER (no satellite, no manual data)**
- ❌ Satellite overlay will NOT display
- ⚠️ Data points layer depends on campaign selection:
  - If campaign has data: Shows all campaign data points
  - If campaign has NO data: Shows nothing
- ℹ️ Map shows base layer only

---

### 🎯 Key Design Points

1. **Data Points Are NOT Date-Filtered**
   - The "Show Field Data" toggle shows **all data points from the campaign**
   - They do NOT change when you change the "Imagery Date"
   - This is intentional - allows you to see field measurements while exploring satellite data across different dates

2. **Satellite Overlay IS Date-Specific**
   - Changing the date **only affects satellite imagery**
   - NDVI/Moisture values update to match the selected date
   - If no satellite data exists for that date, overlay disappears

3. **Click-to-Analyze Interaction**
   - Clicking a data point marker **DOES change the satellite date**
   - It jumps to: `collected_at` date of that data point
   - This allows you to see satellite conditions on the day the measurement was taken
   - 👉 **Example:** Click a data point from August 10 → Map jumps to August 10 satellite imagery

4. **Expected Use Case**
   - User selects campaign → Sees all field data markers
   - User picks a satellite date → Sees satellite conditions for that date
   - User clicks specific data point → Jumps to that point's collection date to compare field vs satellite data

---

### 💡 Testing Implications

When testing data overlays, remember:
- **Data points don't disappear when changing dates** - this is correct behavior
- **Satellite overlay may appear/disappear** - depends on satellite coverage for that date
- **Best test dates:** Use August 1-30, 2025 (known Sentinel-2 coverage + seeded test data)
- **Click-to-analyze should work** even if current date has no satellite data (it changes the date)

### Test 4.1: NDVI Overlay + Data Points

1. **Select campaign:** "Fælledparken Green Space Study"
2. **Select date:** August 15, 2025 (known satellite coverage)
3. **Select overlay:** "🌿 NDVI - Vegetation Index"
4. **Enable "Show Field Data"**
5. **Expected Results:**
   - ✅ NDVI overlay displays (green/red colors showing vegetation health)
   - ✅ All campaign data points visible on top of NDVI (100+ markers)
   - ✅ Data points remain visible regardless of their collection date
   - ✅ Clicking any data point jumps to that point's collection date
   - ✅ NDVI overlay updates to match the clicked point's date

---

### Test 4.2: Moisture Overlay + Data Points

1. **Keep campaign:** "Fælledparken Green Space Study"
2. **Select overlay:** "💧 Moisture Index"
3. **Keep "Show Field Data" enabled**
4. **Change date to:** August 20, 2025 (different date)
5. **Expected Results:**
   - ✅ Moisture overlay updates (blue colors showing soil moisture)
   - ✅ Data points remain visible (same 100+ markers as before)
   - ✅ Data points DO NOT filter by date - you still see all campaign data
   - ✅ Click-to-analyze works - jumps to each point's collection date

---

### Test 4.3: True Color + Data Points

1. **Select overlay:** "🌍 True Color"
2. **Keep "Show Field Data" enabled**
3. **Expected Results:**
   - ✅ True color satellite imagery displays (natural RGB view)
   - ✅ Data points clearly visible (good contrast against imagery)
   - ✅ Popups and interactions work normally
   - ✅ No analysis panel shows (True Color doesn't have NDVI/Moisture values)

---

### Test 4.4: Date Changes WITHOUT Satellite Coverage

**Test the behavior when selecting a date with NO satellite imagery:**

1. **Keep campaign:** "Fælledparken Green Space Study"
2. **Change date to:** December 25, 2025 (likely no satellite data)
3. **Expected Results:**
   - ❌ Satellite overlay disappears (no imagery for this date)
   - ✅ Data points STILL VISIBLE (all 100+ campaign markers remain)
   - ✅ Map shows base OpenStreetMap layer only
   - ℹ️ No NDVI/Moisture analysis panel appears

**This confirms:** Data points are campaign-based, NOT date-filtered.

---

### Test 4.5: Click Data Point to Jump to Its Date

**Test the click-to-analyze date jump functionality:**

1. **Select date:** August 15, 2025
2. **Click a data point** collected on August 10, 2025
3. **Expected Results:**
   - ✅ Date picker updates to August 10, 2025
   - ✅ Satellite overlay refreshes for August 10
   - ✅ Map centers on clicked data point
   - ✅ All campaign data points remain visible (not just the clicked one)

**This confirms:** Clicking jumps to the data point's collection date to compare field vs satellite data.

---

## Test Suite 5: Edge Cases and Error Handling

**Goal:** Test boundary conditions and error scenarios

### Test 5.1: Campaign with No Data Points

1. **Select a campaign** that has no data points
2. **Expected Results:**
   - ✅ Toggle checkbox is visible but inactive
   - ✅ No markers displayed (expected)
   - ✅ Map still centers correctly (zone or default)
   - ✅ No JavaScript errors in console

---

### Test 5.2: Rapid Toggle Clicking

1. **Rapidly toggle "Show Field Data" on/off** (5-10 times quickly)
2. **Expected Results:**
   - ✅ Map responds correctly
   - ✅ Markers appear/disappear cleanly
   - ✅ No layer stacking issues
   - ✅ No memory leaks (markers properly removed)

---

### Test 5.3: Multiple Campaign Switches

1. **Select Campaign A**
2. **Select Campaign B**
3. **Select Campaign C**
4. **Go back to Campaign A**
5. **Expected Results:**
   - ✅ Data points update for each campaign
   - ✅ Old markers are removed when switching
   - ✅ Correct data points show for current campaign
   - ✅ No orphaned markers from previous selections

---

### Test 5.4: Browser Console Check

1. **Open browser developer tools** (F12)
2. **Navigate to Console tab**
3. **Perform all above tests**
4. **Expected Results:**
   - ✅ No JavaScript errors
   - ✅ Log messages show correct data loading:
     - "📍 Adding X data points to map"
     - "✅ Data points layer added"
     - "🎯 Jumping to data point"
   - ✅ No warnings about missing data

---

## Test Suite 6: Visual Quality Assurance

**Goal:** Ensure UI polish and user experience quality

### Test 6.1: Marker Visual Quality

1. **Zoom in close to data points**
2. **Check marker appearance:**
   - ✅ Circles are smooth (not pixelated)
   - ✅ White outline is visible
   - ✅ Fill color is clear (green/gray)
   - ✅ Markers are appropriately sized
   - ✅ Hover effect works (if implemented)

---

### Test 6.2: Popup Readability

1. **Click several data points**
2. **Check popup quality:**
   - ✅ Text is readable (good contrast)
   - ✅ Font size is appropriate
   - ✅ Layout is clean (not cluttered)
   - ✅ Button is clearly clickable
   - ✅ Popup closes when clicking elsewhere

---

### Test 6.3: Responsive Design

1. **Resize browser window** (narrower/wider)
2. **Expected Results:**
   - ✅ Toggle checkbox remains visible
   - ✅ Filter grid adapts to screen size
   - ✅ Map maintains proper aspect ratio
   - ✅ Popups don't get cut off

---

### Test 6.4: Dark Mode (if applicable)

1. **Switch to dark mode** (if application supports it)
2. **Expected Results:**
   - ✅ Data point markers remain visible
   - ✅ Popups have appropriate dark theme
   - ✅ Toggle control has correct styling

---

## Success Criteria Checklist

After completing all tests, verify:

### Task 2.1: DataPoints Overlay ✅
- [ ] Data points render as colored circle markers
- [ ] Toggle checkbox shows/hides markers
- [ ] Markers overlay correctly on satellite imagery
- [ ] Popups display metric information
- [ ] Color coding works (green=approved, gray=pending)

### Task 2.2: Click-to-Analyze ✅
- [ ] Clicking "analyze" button jumps to data point location
- [ ] Coordinates update correctly
- [ ] Date picker updates to collection date
- [ ] Satellite overlay refreshes for new location/date
- [ ] Multiple sequential clicks work correctly

### Task 2.3: Survey Zone Centering ✅
- [ ] Campaigns with zones center on zone centroid
- [ ] Campaigns without zones use first data point
- [ ] Default location works when no campaign selected
- [ ] Priority logic (zone > point > default) works correctly

### Integration Quality ✅
- [ ] No JavaScript errors in console
- [ ] Map performance is smooth (no lag)
- [ ] Layer management is correct (no orphaned markers)
- [ ] UI is polished and professional
- [ ] All interactions feel responsive

---

## Quick Test Script

For rapid verification, follow this streamlined test:

1. Navigate to `/maps/satellite`
2. Select "Fælledparken Green Space Study" campaign
3. Verify map centers on Fælledparken (55.7072°N, 12.5704°E)
4. Toggle "Show Field Data" checkbox (verify marker appears/disappears)
5. Click on the data point marker at Fælledparken center
6. **Verify satellite viewer jumps to August 15, 2025** (known good satellite data date)
7. Verify NDVI overlay loads correctly (should show green vegetation)
8. Switch between NDVI, Moisture, and True Color overlays
9. Verify data point remains visible and clickable on all overlays
10. Check browser console - should be error-free with Copernicus data loaded

**Expected time:** ~5 minutes

**Note:** August 15, 2025 is a confirmed date with good Sentinel-2 satellite coverage for Copenhagen.

---

## Known Issues / Future Enhancements

Document any issues found during testing:

- [x] **Data aligned with satellite imagery:** Seeder now creates data points for **August 1-30, 2025** to match the period with confirmed Sentinel-2 satellite coverage. Default view (August 15, 2025) shows all available test data.

**Future improvements to consider:**
- Add date range filter for data points (show only points within ±7 days of selected satellite date)
- Add cluster markers when zoomed out (many overlapping points)
- Add legend showing marker color meanings
- Add filter to show only high-quality data points
- Add mini-map showing data point distribution
- Implement marker animations on toggle

---

## Testing Notes

**Browser Compatibility:**
- Chrome: ✅ Fully tested
- Firefox: ⏳ Needs testing
- Safari: ⏳ Needs testing
- Edge: ⏳ Needs testing

**Performance:**
- Tested with campaigns up to [X] data points
- Performance acceptable up to [Y] visible markers
- Consider clustering for campaigns with 100+ points

---

**Testing Duration:** 20-30 minutes (full suite), 5 minutes (quick test)  
**Last Updated:** January 12, 2026  
**Status:** Ready for UX Testing

---

## Appendix: Test Data Verification

### Check Campaign Test Data

```powershell
ddev artisan tinker
```

```php
// List campaigns with data points
\App\Models\Campaign::has('dataPoints')
    ->withCount('dataPoints')
    ->get()
    ->map(fn($c) => [
        'id' => $c->id,
        'name' => $c->name,
        'points' => $c->data_points_count
    ]);

// List campaigns with survey zones
\App\Models\Campaign::has('surveyZones')
    ->with('surveyZones:id,campaign_id,name')
    ->get()
    ->map(fn($c) => [
        'id' => $c->id,
        'name' => $c->name,
        'zones' => $c->surveyZones->pluck('name')
    ]);

exit
```

### Create Test Campaign if Needed

```php
// Create campaign with survey zone
$campaign = \App\Models\Campaign::factory()->create(['name' => 'Test Zone Campaign']);
$zone = \App\Models\SurveyZone::factory()->create(['campaign_id' => $campaign->id]);

// Create campaign with data points only
$campaign2 = \App\Models\Campaign::factory()->create(['name' => 'Test Points Campaign']);
\App\Models\DataPoint::factory()->count(10)->create(['campaign_id' => $campaign2->id]);

echo "✅ Test campaigns created\n";
exit
```

---

## Test Suite 8: Production-Ready UX Enhancements (Task 2.6) ⏳ IN PROGRESS

**Goal:** Verify temporal proximity color-coding, sync mode, and educational tooltips

**Prerequisites:**
- Navigate to `/maps/satellite`
- Select "Fælledparken Green Space Study" campaign
- Verify date: August 15, 2025
- Enable "Show Field Data" checkbox

---

### Test 8.1: Temporal Proximity Color-Coding ⏳

**Sub-test 8.1.1: Verify Multi-Colored Markers**

1. **With satellite date set to August 15, 2025**
2. **Expected Results:**
   - ⏳ Data point markers appear in **multiple colors**:
     - 🟢 Green markers (data from Aug 12-18, within 3 days)
     - 🟡 Yellow markers (data from Aug 8-11 or Aug 19-22, 4-7 days)
     - 🟠 Orange markers (data from Aug 1-7 or Aug 23-29, 8-14 days)
     - 🔴 Red markers (data from before Aug 1 or after Aug 30, 15+ days)
   - ⏳ Markers are NOT all the same color
   - ⏳ Console shows: `📍 Adding X data points to map with temporal proximity colors`

**Sub-test 8.1.2: Verify Color Assignment Logic**

1. **Click a green marker**
2. **Expected Results:**
   - ⏳ Popup shows temporal alignment box
   - ⏳ Box background is light green
   - ⏳ Text shows: "Temporal Alignment: Excellent"
   - ⏳ Shows: "0-3 day(s) from satellite image"

3. **Click a yellow marker (if visible)**
4. **Expected Results:**
   - ⏳ Popup shows: "Temporal Alignment: Good"
   - ⏳ Shows: "4-7 day(s) from satellite image"

5. **Click an orange marker (if visible)**
6. **Expected Results:**
   - ⏳ Popup shows: "Temporal Alignment: Acceptable"
   - ⏳ Shows: "8-14 day(s) from satellite image"

**Sub-test 8.1.3: Verify Console Logs**

1. **Open browser console (F12)**
2. **Click any data point marker**
3. **Expected Results:**
   - ⏳ Console shows: `📍 Data point clicked: { lat: ..., proximity: { label: "Excellent/Good/Acceptable/Poor", days: X } }`
   - ⏳ No JavaScript errors
   - ⏳ No "undefined" warnings

---

### Test 8.2: Temporal Proximity Legend ⏳

**Sub-test 8.2.1: Verify Legend Visibility**

1. **With "Show Field Data" enabled**
2. **Expected Results:**
   - ⏳ White box appears in **top-right corner** of map
   - ⏳ Box has shadow (floats above map)
   - ⏳ Title reads: "Temporal Alignment" with ⓘ icon
   - ⏳ Box contains 4 colored circles with labels:
     - 🟢 0-3 days (Excellent)
     - 🟡 4-7 days (Good)
     - 🟠 8-14 days (Acceptable)
     - 🔴 15+ days (Poor)

**Sub-test 8.2.2: Verify Legend Tooltip**

1. **Hover over ⓘ icon in legend header**
2. **Expected Results:**
   - ⏳ Tooltip appears
   - ⏳ Text reads: "Shows how close satellite observation is to field measurement (closer = better correlation)"
   - ⏳ Tooltip disappears when cursor moves away

**Sub-test 8.2.3: Verify Legend Hides with Data Points**

1. **Uncheck "Show Field Data" checkbox**
2. **Expected Results:**
   - ⏳ Legend disappears
   - ⏳ Data points disappear

3. **Re-check "Show Field Data"**
4. **Expected Results:**
   - ⏳ Legend reappears
   - ⏳ Data points reappear

---

### Test 8.3: Educational Tooltips ⏳

**Sub-test 8.3.1: Verify All Tooltip Icons Present**

1. **Scan the interface for ⓘ icons**
2. **Expected Results:**
   - ⏳ Campaign Location label has ⓘ icon
   - ⏳ Data Overlay label has ⓘ icon
   - ⏳ Imagery Date label has ⓘ icon
   - ⏳ Show Field Data checkbox has ⓘ icon
   - ⏳ Temporal Alignment (legend) has ⓘ icon

**Sub-test 8.3.2: Verify Campaign Location Tooltip**

1. **Hover over ⓘ icon next to "Campaign Location"**
2. **Expected Results:**
   - ⏳ Tooltip appears
   - ⏳ Text reads: "Filter view to specific research campaign"

**Sub-test 8.4.3: Verify Data Overlay Tooltip**

1. **Hover over ⓘ icon next to "Data Overlay"**
2. **Expected Results:**
   - ⏳ Tooltip appears
   - ⏳ Text reads: "Choose satellite visualization type: vegetation health, soil moisture, or natural color"

**Sub-test 8.4.4: Verify Imagery Date Tooltip**

1. **Hover over ⓘ icon next to "Imagery Date"**
2. **Expected Results:**
   - ⏳ Tooltip appears
   - ⏳ Text reads: "Select satellite image acquisition date (cloud-free images may be limited)"

**Sub-test 8.4.5: Verify Show Field Data Tooltip**

1. **Hover over ⓘ icon next to "Show Field Data"**
2. **Expected Results:**
   - ⏳ Tooltip appears
   - ⏳ Text reads: "Overlay manual measurements on satellite imagery"

**Sub-test 8.4.6: Verify Tooltip Styling Consistency**

1. **Test all tooltips in both light and dark mode**
2. **Expected Results:**
   - ⏳ All ⓘ icons are gray (#zinc-400)
   - ⏳ Cursor changes to help pointer on hover
   - ⏳ Tooltips are readable in light mode
   - ⏳ Tooltips are readable in dark mode
   - ⏳ Tooltips appear above/beside the icon
   - ⏳ Tooltips disappear when cursor moves away

---

### Test 8.5: Browser Console Verification ⏳

1. **Open Developer Tools (F12) → Console tab**
2. **Perform various interactions** (click markers, toggle sync mode, change dates)
3. **Expected Results:**
   - ⏳ See: `✅ Data points layer added with temporal proximity colors`
   - ⏳ See: `📍 Data point clicked: { proximity: { label: ..., days: ... } }`
   - ⏳ See: `📅 Sync mode: Updated satellite date to match datapoint` (when ON)
   - ⏳ See: `📅 Sync mode disabled: Keeping current satellite date` (when OFF)
   - ⏳ **NO JavaScript errors**
   - ⏳ **NO "undefined" warnings**
   - ⏳ **NO failed color calculations**

---

### Test 8.6: Visual Quality Check ⏳

**Sub-test 8.6.1: Markers Visual Quality**

1. **Zoom in on data point markers**
2. **Expected Results:**
   - ⏳ Markers are smooth circles (not pixelated)
   - ⏳ Clear color borders (2px border)
   - ⏳ Appropriate size (6px radius)
   - ⏳ Good contrast against satellite imagery
   - ⏳ Colors match legend (green/yellow/orange/red)

**Sub-test 8.6.2: Legend Visual Quality**

1. **Inspect the temporal proximity legend**
2. **Expected Results:**
   - ⏳ Positioned in top-right corner
   - ⏳ Doesn't cover important map data
   - ⏳ Has shadow (makes it stand out)
   - ⏳ Text is readable
   - ⏳ Color circles align with labels
   - ⏳ Professional appearance

**Sub-test 8.6.3: Popup Visual Quality**

1. **Click several data points and inspect popups**
2. **Expected Results:**
   - ⏳ Temporal alignment box is visible
   - ⏳ Color-coded background matches marker
   - ⏳ Text is readable
   - ⏳ "Excellent/Good/Acceptable/Poor" label is clear
   - ⏳ Days count is accurate

---

### Test 8.7: Cross-Browser Compatibility ⏳

**Test in Chrome:**
- ⏳ All features work
- ⏳ Tooltips appear
- ⏳ Colors display correctly
- ⏳ No console errors

**Test in Firefox:**
- ⏳ All features work
- ⏳ Tooltips appear
- ⏳ Colors display correctly
- ⏳ No console errors

**Test in Edge:**
- ⏳ All features work
- ⏳ Tooltips appear
- ⏳ Colors display correctly
- ⏳ No console errors

**Test in Safari (if available):**
- ⏳ All features work
- ⏳ Tooltips appear
- ⏳ Colors display correctly
- ⏳ No console errors

---

### Test 8.8: Dark Mode Verification ⏳

1. **Switch to dark mode** (if supported by application)
2. **Expected Results:**
   - ⏳ Legend background is dark (#zinc-800)
   - ⏳ Legend text is readable in dark mode
   - ⏳ Tooltips are readable in dark mode
   - ⏳ Marker colors remain visible
   - ⏳ Popup text is readable in dark mode
   - ⏳ ⓘ icons are visible in dark mode

---

## Test Suite 8 Summary

**Total Sub-tests:** 30+

**Test Categories:**
- ⏳ Color-coding functionality (3 tests)
- ⏳ Legend display and behavior (3 tests)
- ⏳ Sync mode toggle and functionality (5 tests)
- ⏳ Educational tooltips (6 tests)
- ⏳ Console verification (1 test)
- ⏳ Visual quality (3 tests)
- ⏳ Cross-browser compatibility (4 tests)
- ⏳ Dark mode (1 test)

**Estimated Time:** 20-30 minutes

**Priority:** High (production-ready UX features)

