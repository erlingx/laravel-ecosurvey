# Phase 5 Features - Browser Testing Cookbook ✅

**Last Updated:** January 16, 2026  
**Estimated Time:** 12-15 minutes  
**Prerequisites:** Logged in as authenticated user, database seeded with sample data

**Testing Status:** ✅ TESTED & APPROVED (January 16, 2026)

---

## Testing Notes

**Phase 5 Features to Test:**
1. Heatmap visualization with intensity gradients
2. Trend charts with 95% confidence intervals
3. Distribution histograms with optimal binning
4. Campaign and metric filtering
5. Statistical calculations
6. Interactive chart features (zoom, pan, toggles)

**Prerequisites:**
- Run `ddev artisan ecosurvey:populate` to ensure sufficient data (n ≥ 3 per day)
- Phase 4 testing should be complete (campaigns and data points exist)

**Important UX Changes (January 16, 2026):**
- ✅ Metric selector now starts **empty** instead of auto-selecting first metric
- ✅ User must explicitly select a metric to view any data
- ✅ Prevents accidental mixing of incompatible metrics (scientific rigor)
- ✅ Clear empty state messages guide user to select a metric

---

## Quick Test Checklist

- [x] **Heatmap Visualization** ✅ TESTED & APPROVED (4 min)
- [x] **Trend Analysis & Confidence Intervals** ✅ TESTED & APPROVED (5 min)
- [x] **Distribution Histogram** ✅ TESTED & APPROVED (2 min)
- [x] **Statistics Panel Accuracy** ✅ TESTED & APPROVED (2 min)
- [x] **Filter Interactions** ✅ TESTED & APPROVED (2 min)

---

## 1. Heatmap Visualization (4 minutes)

### Test: Access Heatmap Page

**URL:** `/analytics/heatmap`

**Expected Results:**
✅ Page loads without errors  
✅ Sidebar shows "Heatmap" link highlighted  
✅ Map displays with Copenhagen center (no data)  
✅ Filter dropdowns visible (Campaign, Metric, Map Type)  
✅ **Metric dropdown shows "Select a metric..." (empty by default)**  
✅ Campaign dropdown shows "All Campaigns"  
✅ Empty state message: "No metric selected"  
✅ Instruction text: "Select a metric from the dropdown above to view heatmap data."  
✅ No statistics panel visible (hidden until metric selected)  
✅ No heatmap displayed

---

### Test: Metric Selection

**Steps:**
1. Select a metric from the dropdown (e.g., "Temperature (°C)")
2. Observe the page updates

**Expected Results:**
✅ Heatmap layer appears with color gradient (blue → green → red)  
✅ Map auto-fits to data points  
✅ Statistics panel appears with values (count, min, max, avg, median, std dev)  
✅ Page title updates: "Heatmap - Temperature (°C)"  
✅ Intensity legend appears showing "Low → High" gradient

---

### Test: Change Metric

**Steps:**
1. Select different metric from dropdown (e.g., "Humidity (%)")
2. Wait for heatmap to update

**Expected Results:**
✅ Heatmap updates immediately  
✅ Color intensity changes based on new data  
✅ Statistics panel updates with new values  
✅ Page title updates: "Heatmap - Humidity (%)"  
✅ Map re-centers to new data bounds (if different)

---

### Test: Campaign Filter

**Steps:**
1. Select a specific campaign from "Campaign" dropdown
2. Observe heatmap updates

**Expected Results:**
✅ Heatmap shows only data points from selected campaign  
✅ Map zooms to campaign boundaries  
✅ Statistics recalculate for filtered data  
✅ Point count decreases in statistics panel

---

### Test: Map Type Toggle

**Steps:**
1. Click "Map Type" dropdown
2. Select **Satellite**
3. Wait for basemap to change
4. Select **Street** again

**Expected Results:**
✅ Basemap switches to satellite imagery (Esri World Imagery)  
✅ Heatmap layer remains visible on top  
✅ Switching back to street shows OpenStreetMap  
✅ Heatmap data persists during basemap changes

---

### Test: Empty State Handling

**Steps:**
1. **Initial load:** Page starts with no metric selected
2. **Select metric with no data:** Select a campaign with no data for current metric
3. **Clear metric selection:** Select "Select a metric..." to return to empty state

**Expected Results:**

**When no metric selected:**
✅ Empty state shows "No metric selected"  
✅ Message: "Select a metric from the dropdown above to view heatmap data."  
✅ Map shows default Copenhagen view  
✅ No statistics panel  
✅ No JavaScript errors in console

**When metric selected but no data:**
✅ Empty state shows "No data available"  
✅ Contextual message: "No [metric name] measurements found for this campaign."  
✅ Map shows default Copenhagen view  
✅ Statistics panel shows zero count  
✅ No JavaScript errors in console

---

### Test: Heatmap Intensity & Visibility

**Steps:**
1. Select "Temperature (°C)" metric
2. Zoom in to see individual heat points
3. Look for color gradient: blue (low) → green (medium) → red (high)

**Expected Results:**
✅ Heatmap radius ~30px (visible, not too small)  
✅ Blur effect ~20px (smooth gradient)  
✅ Minimum opacity 0.3 (visible even at low values)  
✅ Color gradient clearly visible  
✅ Intensity legend shows "Low → High" interpretation  
✅ Higher values show redder colors

---

## 2. Trend Analysis & Confidence Intervals (5 minutes)

### Test: Access Trend Chart Page

**URL:** `/analytics/trends`

**Expected Results:**
✅ Page loads without errors  
✅ Sidebar shows "Trends" link highlighted  
✅ **Metric dropdown shows "Select a metric..." (empty by default)**  
✅ Campaign dropdown shows "All Campaigns"  
✅ Interval dropdown shows "Daily"  
✅ Empty state message visible  
✅ No trend chart displayed (until metric selected)  
✅ No distribution histogram displayed (until metric selected)  
✅ No statistics panel visible (hidden until metric selected)

---

### Test: Select Metric to Load Charts

**Steps:**
1. Select a metric from dropdown (e.g., "Temperature (°C)")
2. Observe page updates

**Expected Results:**
✅ Trend chart appears with data  
✅ Distribution histogram appears below  
✅ Statistics panel appears with values  
✅ Page title updates: "Trend Analysis - Temperature (°C)"

---

### Test: 95% Confidence Interval Visualization

**Steps:**
1. Ensure metric with sufficient data is selected (e.g., "Temperature (°C)")
2. Observe the trend chart
3. Look for **blue shaded band** around the average line

**Expected Results:**
✅ Blue line shows average values over time  
✅ **Light blue shaded area** shows 95% CI band  
✅ CI band only appears where n ≥ 3 (sample size sufficient)  
✅ Visual badge explains "95% CI" meaning  
✅ Overall average line (dashed horizontal) visible  
✅ Min/Max lines **hidden by default** (to focus on CI)

**Scientific Validation:**
- Hover over any data point in tooltip
- ✅ Tooltip shows: n (sample size), σ (std dev), 95% CI range [lower, upper]
- ✅ When n < 3, CI shows same as average (not calculated)

---

### Test: Interactive Zoom & Pan

**Steps:**
1. **Mouse wheel** scroll up/down on the trend chart
2. **Ctrl + drag** left/right to pan
3. Click **Reset Zoom** button

**Expected Results:**
✅ Mouse wheel zooms X-axis (time periods)  
✅ Ctrl+drag pans timeline left/right  
✅ Zoom preserves Y-axis scale  
✅ Reset Zoom button restores original view  
✅ Chart remains responsive during interactions  
✅ No JavaScript errors

---

### Test: Toggle Min/Max Lines

**Steps:**
1. Click **Toggle Min/Max** button below chart
2. Click again to hide

**Expected Results:**
✅ Min line (green dashed) appears  
✅ Max line (red dashed) appears  
✅ Button text changes: "Hide Min/Max" ↔ "Show Min/Max"  
✅ Lines toggle properly on multiple clicks  
✅ CI band remains visible when toggling

---

### Test: Interval Selection

**Steps:**
1. Select **Weekly** from interval dropdown
2. Observe chart updates
3. Select **Monthly**
4. Select **Daily** again

**Expected Results:**
✅ Chart aggregates data by selected interval  
✅ X-axis labels update (daily dates → weekly ranges → monthly periods)  
✅ Fewer data points with weekly/monthly (aggregation working)  
✅ Statistics panel updates  
✅ CI band recalculates for new interval  
✅ Smooth transitions, no flickering

---

### Test: Metric Change on Trends

**Steps:**
1. Select "Noise Level (dB)" from metric dropdown
2. Wait for chart to update

**Expected Results:**
✅ Chart displays new metric data  
✅ Y-axis label updates to "Noise Level (dB)"  
✅ Statistics panel shows new values  
✅ Page title updates: "Trend Analysis - Noise Level (dB)"  
✅ Distribution histogram updates below

---

### Test: Campaign Filter on Trends

**Steps:**
1. Select specific campaign from dropdown
2. Observe chart updates

**Expected Results:**
✅ Trend chart filters to campaign data only  
✅ X-axis range may change (different date ranges)  
✅ Statistics recalculate  
✅ Distribution histogram updates  
✅ CI band recalculates if sample sizes change

---

## 3. Distribution Histogram (2 minutes)

### Test: Histogram Display

**Steps:**
1. On `/analytics/trends` page
2. Scroll down to "Distribution Histogram" section
3. Select "Temperature (°C)" metric if not already selected

**Expected Results:**
✅ Bar chart displays frequency distribution  
✅ X-axis shows value ranges (e.g., "10.0 - 12.5", "12.5 - 15.0")  
✅ Y-axis shows frequency count (n)  
✅ Bars colored consistently  
✅ Y-axis label: "Frequency (n)"

---

### Test: Freedman-Diaconis Optimal Binning

**Steps:**
1. Compare bin widths across different metrics
2. Observe how bin count changes with data distribution

**Expected Results:**
✅ Bin count automatically calculated (1-50 range)  
✅ Wider distribution → more bins  
✅ Narrow distribution → fewer bins  
✅ Bin width = 2 × IQR / n^(1/3) (automatic)  
✅ No manual bin selection (scientifically optimal)

**Note:** Bin count may vary per metric based on data spread

---

### Test: Histogram Updates with Filters

**Steps:**
1. Change campaign filter
2. Observe histogram updates
3. Change metric
4. Observe histogram updates

**Expected Results:**
✅ Histogram recalculates bins for new data  
✅ Bar heights change based on filtered data  
✅ Bin ranges may shift based on new min/max  
✅ Smooth updates, no errors

---

## 4. Statistics Panel Accuracy (2 minutes)

### Test: Statistics Display - Heatmap

**Steps:**
1. Go to `/analytics/heatmap`
2. Select "Temperature (°C)" metric
3. Read statistics panel

**Expected Results:**
✅ **Count** (number of data points)  
✅ **Minimum** value with unit (°C)  
✅ **Maximum** value with unit (°C)  
✅ **Average** with proper precision (1-2 decimals)  
✅ **Median** calculated correctly  
✅ **Std Dev** (standard deviation) shown  
✅ All values have proper units labeled

---

### Test: Statistics Display - Trends

**Steps:**
1. Go to `/analytics/trends`
2. Select "Humidity (%)" metric
3. Read statistics panel

**Expected Results:**
✅ Same statistics as heatmap (count, min, max, avg, median, std dev)  
✅ Units show "%" for humidity  
✅ Values match across both pages for same metric/campaign  
✅ Calculations accurate (manually verify a few if possible)

---

### Test: Statistics Update with Filters

**Steps:**
1. Note current statistics values
2. Apply campaign filter
3. Verify statistics recalculate

**Expected Results:**
✅ Count decreases when filtering  
✅ Min/Max may change based on filtered subset  
✅ Average recalculates  
✅ Median updates  
✅ Std dev recalculates  
✅ No stale data displayed

---

## 5. Filter Interactions (2 minutes)

### Test: Filter Reactivity - Heatmap

**Steps:**
1. Go to `/analytics/heatmap`
2. Change **Metric** → observe immediate update
3. Change **Campaign** → observe immediate update
4. Change **Map Type** → observe immediate update
5. Clear campaign filter (select "All Campaigns")

**Expected Results:**
✅ All changes trigger immediate updates (no manual refresh button)  
✅ Loading states visible during updates  
✅ Map, statistics, and title all update together  
✅ No race conditions or duplicate requests  
✅ Filters persist when navigating away and back (Livewire navigation)

---

### Test: Filter Reactivity - Trends

**Steps:**
1. Go to `/analytics/trends`
2. Change **Metric** → observe updates
3. Change **Campaign** → observe updates
4. Change **Interval** → observe updates

**Expected Results:**
✅ Trend chart updates immediately  
✅ Distribution histogram updates  
✅ Statistics panel updates  
✅ All three synchronized (no partial updates)  
✅ Smooth transitions, no flickering

---

### Test: No Mixing of Incompatible Metrics

**Steps:**
1. Verify **no "All Metrics" option** in dropdown
2. Try to view multiple metrics simultaneously

**Expected Results:**
✅ Metric dropdown only shows individual metrics  
✅ No option to combine Temperature (°C) + Noise (dB)  
✅ This prevents scientifically invalid comparisons  
✅ User must select one metric at a time

---

## 6. Edge Cases & Error Handling

### Test: Empty Data Scenario

**Steps:**
1. Select a campaign with minimal/no data for a specific metric
2. Observe page behavior

**Expected Results:**
✅ Graceful empty state message  
✅ No JavaScript errors in console  
✅ Map shows default view (not broken)  
✅ Statistics show zeros or "No data available"  
✅ Charts display empty state (not broken axes)

---

### Test: Browser Console - No Errors

**Steps:**
1. Open browser DevTools (F12)
2. Navigate through both analytics pages
3. Change filters multiple times
4. Check console for errors

**Expected Results:**
✅ No JavaScript errors  
✅ No 500/404 network errors  
✅ Only expected Livewire requests (200 OK)  
✅ Chart.js and Leaflet load successfully  
✅ No memory leaks (charts properly destroyed/recreated)

---

### Test: Dark Mode Support

**Steps:**
1. Toggle dark mode in settings (if available)
2. Visit `/analytics/heatmap` and `/analytics/trends`

**Expected Results:**
✅ Page backgrounds dark  
✅ Text colors readable (white/light gray)  
✅ Chart backgrounds dark  
✅ Heatmap visible on dark basemap  
✅ Statistics panels dark themed  
✅ No contrast issues

---

## 7. Scientific Rigor Validation

### Test: Sample Size Transparency

**Steps:**
1. Go to `/analytics/trends`
2. Hover over any data point on trend chart
3. Read tooltip

**Expected Results:**
✅ Tooltip shows **n** (sample size)  
✅ Tooltip shows **σ** (standard deviation)  
✅ Tooltip shows **95% CI range** [lower, upper]  
✅ All three primary metrics visible (min, avg, max)  
✅ Values formatted with proper precision

---

### Test: CI Validity Check (n ≥ 3)

**Steps:**
1. Find a time period with n < 3 (may need to use weekly/monthly interval)
2. Observe CI band behavior

**Expected Results:**
✅ When n < 3, CI band collapses to average line (not shown)  
✅ Tooltip indicates insufficient sample size  
✅ CI only calculated when statistically valid (n ≥ 3)  
✅ No misleading confidence intervals displayed

---

### Test: Unit Labeling Throughout

**Steps:**
1. Check all pages for proper unit labels
2. Verify metric names include units

**Expected Results:**
✅ Heatmap title: "Heatmap - Temperature (°C)"  
✅ Trend title: "Trend Analysis - Noise Level (dB)"  
✅ Statistics panel shows units: "Min: 10.5 °C"  
✅ Y-axis labeled: "Temperature (°C)"  
✅ Distribution histogram: "Value (dB)" or similar  
✅ No unlabeled numbers

---

## Automated Test Verification

### Run Automated Tests

**Steps:**
```powershell
# Run all Phase 5 tests
ddev artisan test --filter=AnalyticsServiceTest
ddev artisan test --filter=HeatmapGeneratorTest
ddev artisan test --filter=TrendChartTest

# Or run all analytics tests
ddev artisan test tests/Feature/Services/AnalyticsServiceTest.php
ddev artisan test tests/Feature/Livewire/Analytics/
```

**Expected Results:**
✅ AnalyticsServiceTest: 12 tests, 41 assertions passing  
✅ HeatmapGeneratorTest: 1 test, 1 assertion passing  
✅ TrendChartTest: 1 test, 1 assertion passing  
✅ **Total: 14 tests passing, 43 assertions**

---

## Testing Completion Checklist ✅

After completing all tests, verify:

- [x] **Pages start with empty metric selector (no auto-load)**
- [x] **Empty state message shows when no metric selected**
- [x] Heatmap displays with proper intensity gradient (after metric selected)
- [x] Trend charts show 95% confidence intervals (when n ≥ 3)
- [x] Distribution histograms use optimal binning
- [x] Statistics calculate correctly (min, max, avg, median, std dev)
- [x] Campaign and metric filters work reactively
- [x] Map type toggle works (street/satellite)
- [x] Interval selection updates charts (daily/weekly/monthly)
- [x] Zoom/pan interactions work smoothly
- [x] Toggle min/max lines work
- [x] Empty states handled gracefully (both "no metric" and "no data")
- [x] No JavaScript errors in console
- [x] Dark mode supported
- [x] All 14 automated tests passing
- [x] Sample sizes (n) visible in tooltips
- [x] Units labeled throughout
- [x] No mixing of incompatible metrics (must select one metric at a time)

---

## Known Issues / Limitations

**Current Limitations (Not Bugs):**
- CI band only shown when n ≥ 3 (by design for statistical validity)
- Histogram bin count auto-calculated (not user-configurable)
- No "All Metrics" option (prevents mixing incompatible units)
- Min/Max lines hidden by default (to emphasize CI band)

**Future Enhancements (Not in Phase 5 scope):**
- Export charts as PNG/SVG
- Download filtered data as CSV from analytics pages
- Side-by-side metric comparison
- Anomaly detection visualization
- Forecast trend lines

---

## Notes for Developers

**If Issues Found During Testing:**

1. **Check browser console** for JavaScript errors
2. **Clear browser cache** (Ctrl+Shift+R) if UI seems stale
3. **Verify data exists:** Run `ddev artisan ecosurvey:populate`
4. **Check Vite is running:** `ddev exec bash -c "ps aux | grep vite | grep -v grep"`
5. **Rebuild assets if needed:** `ddev npm run build`
6. **Check queue worker:** `ddev exec bash -c "ps aux | grep queue | grep -v grep"`

**Database Seeding Requirements:**
- Phase 5 analytics require **n ≥ 3** data points per day for valid CI
- Run seeder if testing with empty database
- Fælledparken campaign should have 3-5 readings/day (31 days)
- Urban Noise campaign should have 3-4 readings/day (14 days)

---

**Testing Complete?** Mark this phase as tested in `Development-Roadmap.md`

**Estimated Total Time:** 12-15 minutes (excluding automated tests)

**Last Updated:** January 16, 2026
