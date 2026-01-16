# Phase 6 Features - Browser Testing Cookbook ✅

**Last Updated:** January 16, 2026  
**Estimated Time:** 8-10 minutes  
**Prerequisites:** Logged in as authenticated user, database seeded with campaigns and data points

**Testing Status:** ✅ TESTED & APPROVED (January 16, 2026)

---

## Testing Notes

**Phase 6 Features to Test:**
1. 5 new satellite indices (NDRE, EVI, MSI, SAVI, GNDVI)
2. Satellite overlay switching for all 7 indices total
3. Automatic satellite data enrichment job
4. Analysis panel showing all index values
5. Proper error handling for API failures

**Prerequisites:**
- Phase 4 should be complete (satellite viewer working)
- Database has data points with GPS coordinates
- Copernicus Data Space credentials configured in `.env`

**Key Changes in Phase 6:**
- ✅ 5 new satellite indices added to overlay selector
- ✅ Enrichment job now fetches all 7 indices in parallel
- ✅ Single unified `SatelliteAnalysis` record per data point
- ✅ Proper handling of partial API failures

---

## Quick Test Checklist

- [x] **Satellite Overlay Selector** ✅ TESTED & APPROVED (2 min)
- [x] **New Satellite Indices Display** ✅ TESTED & APPROVED (3 min)
- [x] **Analysis Panel Shows All Indices** ✅ TESTED & APPROVED (2 min)
- [x] **Automated Enrichment Job** ✅ TESTED & APPROVED (3 min)

---

## 1. Satellite Overlay Selector (2 minutes)

### Test: Access Satellite Viewer Page

**URL:** `/maps/satellite`

**Expected Results:**
✅ Page loads without errors  
✅ Sidebar shows "Satellite Viewer" link highlighted  
✅ Map displays with Copenhagen center  
✅ Overlay type dropdown visible

---

### Test: New Overlay Options Available

**Steps:**
1. Locate the "Overlay Type" dropdown
2. Click to open dropdown
3. Review all available options

**Expected Results:**
✅ Dropdown shows 7 overlay options total:
- ✅ True Color (RGB)
- ✅ NDVI - Vegetation Health
- ✅ NDMI - Moisture Index
- ✅ **🌱 NDRE - Chlorophyll Content (R²=0.85)** ← NEW
- ✅ **🌳 EVI - Enhanced Vegetation (Dense Canopy)** ← NEW
- ✅ **🏜️ MSI - Moisture Stress** ← NEW
- ✅ **🌾 SAVI - Soil-Adjusted Vegetation** ← NEW
- ✅ **💚 GNDVI - Green Vegetation** ← NEW

✅ Each option shows correlation coefficient (R²) value  
✅ Options have descriptive labels

---

## 2. New Satellite Indices Display (3 minutes)

### Test: NDRE Overlay

**Steps:**
1. Select a campaign location from dropdown (e.g., "Fælledparken Green Space Study")
2. Select overlay type: **"NDRE - Chlorophyll Content (R²=0.85)"**
3. Click "Fetch Satellite Data" (if button exists) or wait for auto-load
4. Observe the map overlay

**Expected Results:**
✅ Loading indicator appears  
✅ Satellite overlay appears on map (may take 5-10 seconds)  
✅ Overlay shows chlorophyll content visualization  
✅ No JavaScript errors in console  
✅ Analysis panel updates with NDRE value

**Scientific Validation:**
- NDRE range: -1.0 to +1.0
- Higher values = more chlorophyll (healthy vegetation)
- Formula: `(B08 - B05) / (B08 + B05)`

---

### Test: EVI Overlay

**Steps:**
1. Keep same location selected
2. Change overlay type to: **"EVI - Enhanced Vegetation (Dense Canopy)"**
3. Observe overlay update

**Expected Results:**
✅ Previous overlay removed  
✅ New EVI overlay appears  
✅ Overlay shows enhanced vegetation index  
✅ Analysis panel shows EVI value  
✅ Better for dense canopy than NDVI

**Scientific Validation:**
- EVI range: -1.0 to +1.0
- Better sensitivity in high biomass areas
- Formula: `2.5 * ((B08 - B04) / (B08 + 6*B04 - 7.5*B02 + 1))`

---

### Test: MSI Overlay

**Steps:**
1. Change overlay type to: **"MSI - Moisture Stress"**
2. Observe overlay

**Expected Results:**
✅ MSI overlay appears  
✅ Shows moisture stress levels  
✅ Analysis panel shows MSI value  
✅ Inverse of NDMI (higher MSI = more stress)

**Scientific Validation:**
- MSI range: 0.0 to 3.0+
- Lower values = less water stress
- Formula: `B11 / B08` (SWIR1 / NIR)

---

### Test: SAVI Overlay

**Steps:**
1. Change overlay type to: **"SAVI - Soil-Adjusted Vegetation"**
2. Observe overlay

**Expected Results:**
✅ SAVI overlay appears  
✅ Shows soil-adjusted vegetation index  
✅ Analysis panel shows SAVI value  
✅ Useful for sparse vegetation areas

**Scientific Validation:**
- SAVI range: -1.0 to +1.0
- Corrects for soil brightness
- Formula: `((B08 - B04) / (B08 + B04 + 0.5)) * 1.5`

---

### Test: GNDVI Overlay

**Steps:**
1. Change overlay type to: **"GNDVI - Green Vegetation"**
2. Observe overlay

**Expected Results:**
✅ GNDVI overlay appears  
✅ Shows green vegetation index  
✅ Analysis panel shows GNDVI value  
✅ More sensitive to chlorophyll than NDVI

**Scientific Validation:**
- GNDVI range: -1.0 to +1.0
- Uses green band instead of red
- Formula: `(B08 - B03) / (B08 + B03)`

---

## 3. Analysis Panel Shows Index Data (2 minutes)

### Understanding the Analysis Panel

**Location:** Right side of satellite viewer page, below the map  
**Appearance:** Colored card with icon, heading, value, and interpretation

**Panel Structure:**
- **Header:** Icon + Title (e.g., "🌿 NDVI Analysis - Vegetation Index")
- **Value Display:** Index value in monospace font (e.g., "NDVI Value: 0.456")
- **Interpretation:** Human-readable meaning (e.g., "Moderate vegetation")
- **Scale Reference:** Bullet list showing value ranges and meanings
- **Formula:** Small italic text showing calculation (optional)

**Colors by Index Type:**
- **NDVI:** Green background (bg-green-50)
- **NDMI (Moisture):** Blue background (bg-blue-50)
- **NDRE:** Green background (chlorophyll)
- **EVI:** Green background (vegetation)
- **MSI:** Orange/yellow background (stress indicator)
- **SAVI:** Green background (soil-adjusted)
- **GNDVI:** Green background (green vegetation)

---

### Test: NDVI Analysis Panel

**Steps:**
1. Navigate to `/maps/satellite`
2. Select campaign: "Fælledparken Green Space Study"
3. Select overlay: "NDVI - Vegetation Health"
4. Wait for satellite data to load
5. Scroll down to see analysis panel

**Expected Results:**
✅ **Green panel appears** with heading "🌿 NDVI Analysis - Vegetation Index"  
✅ **NDVI Value displayed** (e.g., "0.456") in monospace font  
✅ **Interpretation shown** (e.g., "Moderate vegetation")  
✅ **Scale reference visible** with 6 ranges (water to dense vegetation)  
✅ No JavaScript errors

**Example Panel:**
```
🌿 NDVI Analysis - Vegetation Index

NDVI Value: 0.456
Interpretation: Moderate vegetation

NDVI Scale Reference:
• < 0: Water
• 0 - 0.1: Barren rock, sand, or snow
• 0.1 - 0.2: Shrub and grassland
• 0.2 - 0.3: Sparse vegetation
• 0.3 - 0.6: Moderate vegetation
• > 0.6: Dense vegetation
```

---

### Test: NDMI (Moisture) Analysis Panel

**Steps:**
1. Change overlay to: "NDMI - Moisture Index"
2. Wait for data to load
3. Review analysis panel

**Expected Results:**
✅ **Blue panel appears** with heading "💧 Soil Moisture Analysis (NDMI)"  
✅ **Moisture Index value** displayed (e.g., "0.123")  
✅ **Interpretation** (e.g., "Moderate wet")  
✅ **Scale reference** with 6 moisture ranges  
✅ Formula shown: "NDMI = (NIR - SWIR) / (NIR + SWIR)"

---

### Test: New Index Analysis Panels

**Steps:**
1. Switch to each new overlay type (NDRE, EVI, MSI, SAVI, GNDVI)
2. Check if analysis panel appears for each

**Expected Results:**

**All Panels Now Implemented:** ✅

✅ **NDRE Panel** (green background, bg-green-50)
- Header: "🌱 NDRE Analysis - Chlorophyll Content"
- Shows NDRE value in monospace font
- R² correlation: 0.80-0.90
- Scale reference with 4 ranges
- Formula: `(NIR - RedEdge) / (NIR + RedEdge)`

✅ **EVI Panel** (green background, bg-green-50)
- Header: "🌳 EVI Analysis - Enhanced Vegetation Index"
- Shows EVI value
- R² correlation: 0.75-0.85 (LAI, FAPAR)
- Scale reference with 4 ranges
- Formula: `2.5 × ((NIR - Red) / (NIR + 6×Red - 7.5×Blue + 1))`

✅ **MSI Panel** (orange background, bg-orange-50)
- Header: "🏜️ MSI Analysis - Moisture Stress Index"
- Shows MSI value
- R² correlation: 0.70-0.80 (Soil Moisture)
- Scale reference with 4 stress levels
- Formula: `SWIR1 / NIR` (higher = more stress)

✅ **SAVI Panel** (amber background, bg-amber-50)
- Header: "🌾 SAVI Analysis - Soil-Adjusted Vegetation"
- Shows SAVI value
- R² correlation: 0.70-0.80 (Sparse LAI)
- Scale reference with 4 ranges
- Formula: `((NIR - Red) / (NIR + Red + 0.5)) × 1.5`
- Note: "Corrects for soil brightness in sparse canopy"

✅ **GNDVI Panel** (emerald background, bg-emerald-50)
- Header: "💚 GNDVI Analysis - Green Vegetation Index"
- Shows GNDVI value
- R² correlation: 0.75-0.85 (Chlorophyll)
- Scale reference with 4 ranges
- Formula: `(NIR - Green) / (NIR + Green)`
- Note: "More sensitive to chlorophyll than NDVI"

**All 7 Analysis Panels Complete:**
- ✅ NDVI (green) - Vegetation health index
- ✅ NDMI (blue) - Soil moisture index
- ✅ NDRE (green) ← NEW - Chlorophyll content
- ✅ EVI (green) ← NEW - Enhanced vegetation
- ✅ MSI (orange) ← NEW - Moisture stress
- ✅ SAVI (amber) ← NEW - Soil-adjusted vegetation
- ✅ GNDVI (emerald) ← NEW - Green vegetation
- ✅ True Color (gray info panel only - no numerical index)

**Why True Color has no analysis values:**
- True Color is a visual RGB composite (natural color image)
- No quantitative index to calculate (just shows what eyes would see)
- Info panel explains: "Displaying natural color satellite imagery from Sentinel-2 (Bands B04, B03, B02)"
- Used as visual reference, not for scientific measurements

---

### Test: True Color Info Panel

**Steps:**
1. Select overlay: "True Color (RGB)"
2. Wait for satellite imagery to load
3. Scroll down to see info panel

**Expected Results:**
✅ **Gray panel appears** with heading "🌍 True Color RGB"  
✅ **Description text:** "Displaying natural color satellite imagery from Sentinel-2 (Bands B04, B03, B02)."  
✅ No analysis value (this is correct - True Color is visual only)  
✅ Map shows natural color satellite imagery  
✅ Panel appears even without analysis data  
✅ **Source field displays:** "Sentinel-2 (Copernicus Data Space)" above the map

**Note:** This panel was moved outside the `analysisData` condition to ensure it displays properly since True Color has no numerical index. The source field now checks both `analysisData` and `satelliteData` so it shows for True Color too.

---

### Test: Database Verification (All 7 Indices Stored)

**Steps:**
1. After viewing any overlay, verify data is stored in database
2. Run this query in database:

```sql
SELECT 
    id,
    ndvi_value,
    ndmi_value,
    ndre_value,
    evi_value,
    msi_value,
    savi_value,
    gndvi_value,
    analyzed_at
FROM satellite_analyses 
ORDER BY analyzed_at DESC 
LIMIT 5;
```

**Expected Results:**
✅ All 7 index columns have values (or NULL if fetch failed)  
✅ `analyzed_at` timestamp is recent  
✅ Values in scientifically valid ranges:
- NDVI, NDMI, NDRE, EVI, SAVI, GNDVI: -1.0 to +1.0
- MSI: 0.0 to 3.0+

---

## 4. Automated Enrichment Job (3 minutes)

### Test: Create New Data Point and Verify Enrichment

**Steps:**
1. Go to `/data-points/submit`
2. Create a new data point:
   - Allow GPS capture (ensure valid coordinates)
   - Select a campaign
   - Select an environmental metric
   - Enter a value
   - Click Submit
3. Note the data point ID
4. Wait 10-30 seconds for background job to process
5. Navigate to `/maps/satellite`
6. Select the same location as the new data point
7. Check if satellite analysis data appears

**Expected Results:**

**After Job Completes:**
✅ `SatelliteAnalysis` record created in database  
✅ Analysis panel shows all 7 satellite indices (or nulls for failures)  
✅ Only ONE analysis record per data point (not 7 separate records)  
✅ All indices fetched in parallel (not sequential)  
✅ Log shows which indices were successfully fetched

**Check Queue Status:**
```powershell
# Verify queue worker is running
ddev exec bash -c "ps aux | grep queue | grep -v grep"

# Check recent jobs
ddev artisan queue:monitor database
```

✅ Queue worker running  
✅ Job processed successfully  
✅ No failed jobs in queue

---

### Test: Enrichment Job with Invalid Coordinates

**Steps:**
1. Create a data point with GPS disabled (if possible) or outside Copernicus coverage
2. Submit the data point
3. Wait for job to process
4. Check satellite viewer

**Expected Results:**
✅ Job doesn't crash  
✅ Log shows "No valid coordinates" or similar message  
✅ No satellite analysis created  
✅ Data point still exists in database  
✅ No errors in queue

---

## 5. Edge Cases & Error Handling

### Test: API Rate Limiting

**Steps:**
1. Rapidly switch between all 7 overlay types
2. Observe behavior

**Expected Results:**
✅ Caching prevents excessive API calls (1 hour TTL)  
✅ Cached data loads instantly on subsequent requests  
✅ No "too many requests" errors  
✅ Smooth user experience

---

### Test: Missing Satellite Data

**Steps:**
1. Select a location with no satellite coverage (e.g., ocean coordinates)
2. Try to fetch satellite data

**Expected Results:**
✅ Graceful error message  
✅ No JavaScript errors  
✅ User can try different location  
✅ Analysis panel shows empty state

---

### Test: Browser Console - No Errors

**Steps:**
1. Open browser DevTools (F12)
2. Navigate through satellite viewer
3. Switch overlays multiple times
4. Check console for errors

**Expected Results:**
✅ No JavaScript errors  
✅ No 500/404 network errors  
✅ Only expected API requests (200 OK or cached)  
✅ Leaflet loads successfully  
✅ No memory leaks

---

## Testing Completion Checklist ✅

After completing all tests, verify:

- [x] All 7 overlay options visible in dropdown (True Color, NDVI, NDMI, NDRE, EVI, MSI, SAVI, GNDVI)
- [x] Each new overlay displays properly on map
- [x] Analysis panel shows all 7 index values (or N/A for nulls)
- [x] Enrichment job runs automatically for new data points
- [x] Job fetches all 7 indices in parallel (single record created)
- [x] Partial API failures handled gracefully
- [x] Caching works (1 hour TTL per index)
- [x] No JavaScript errors in console
- [x] Queue worker running properly
- [x] Invalid coordinates handled without crashes
- [x] Scientific formulas and R² values documented
- [x] All 28 automated tests passing
- [x] True Color info panel displays correctly
- [x] Source field displays for all overlay types

---

## Automated Test Verification

### Run Automated Tests

**Steps:**
```powershell
# Run all Phase 6 tests
ddev artisan test tests/Feature/Services/CopernicusDataSpaceServiceTest.php
ddev artisan test tests/Feature/Jobs/EnrichDataPointWithSatelliteDataTest.php

# Or run specific test groups
ddev artisan test --filter=NDRE
ddev artisan test --filter=EVI
ddev artisan test --filter=EnrichDataPoint
```

**Expected Results:**
✅ CopernicusDataSpaceServiceTest: 23 tests, 96 assertions passing  
✅ EnrichDataPointWithSatelliteDataTest: 5 tests, 12 assertions passing  
✅ **Total: 28 tests passing, 108 assertions**

---

## Known Limitations (Not Bugs)

**Current Limitations:**
- Copernicus API may have temporary outages (handled gracefully)
- Sentinel-2 imagery has 5-10 day revisit time (not real-time)
- Cloud cover may affect data quality (handled by API)
- Some indices may not be available for all locations

**Future Enhancements (Not in Phase 6 scope):**
- Multi-date comparison (temporal analysis)
- Cloud masking visualization
- Index combination formulas
- Export satellite analysis data
- Batch enrichment for existing data points

---

## Scientific Reference

### Satellite Index Formulas

**NDVI (Original):**
- Formula: `(NIR - Red) / (NIR + Red)`
- Bands: B04 (Red), B08 (NIR)
- R² = 0.75-0.85

**NDMI (Original):**
- Formula: `(NIR - SWIR1) / (NIR + SWIR1)`
- Bands: B08 (NIR), B11 (SWIR1)
- R² = 0.70-0.80

**NDRE (NEW):**
- Formula: `(NIR - RedEdge) / (NIR + RedEdge)`
- Bands: B05 (Red Edge 705nm), B08 (NIR)
- R² = 0.80-0.90
- Best for: Chlorophyll content

**EVI (NEW):**
- Formula: `2.5 * ((NIR - Red) / (NIR + 6*Red - 7.5*Blue + 1))`
- Bands: B02 (Blue), B04 (Red), B08 (NIR)
- R² = 0.75-0.85
- Best for: Dense canopy LAI

**MSI (NEW):**
- Formula: `SWIR1 / NIR`
- Bands: B08 (NIR), B11 (SWIR1)
- R² = 0.70-0.80
- Best for: Water stress (inverse of NDMI)

**SAVI (NEW):**
- Formula: `((NIR - Red) / (NIR + Red + 0.5)) * 1.5`
- Bands: B04 (Red), B08 (NIR)
- R² = 0.70-0.80
- Best for: Sparse vegetation LAI

**GNDVI (NEW):**
- Formula: `(NIR - Green) / (NIR + Green)`
- Bands: B03 (Green 560nm), B08 (NIR)
- R² = 0.75-0.85
- Best for: Chlorophyll (more sensitive than NDVI)

---

## Notes for Developers

**If Issues Found During Testing:**

1. **Check Copernicus credentials:** Verify `.env` has valid `COPERNICUS_CLIENT_ID` and `COPERNICUS_CLIENT_SECRET`
2. **Check queue worker:** Ensure `ddev artisan queue:work` is running (or auto-started by DDEV)
3. **Clear cache if needed:** `ddev artisan cache:clear`
4. **Check logs:** `ddev logs` or `storage/logs/laravel.log`
5. **Verify database migration:** Ensure `satellite_analyses` table has new columns (evi_value, savi_value, etc.)

**Database Check:**
```sql
-- Verify new columns exist
SELECT column_name 
FROM information_schema.columns 
WHERE table_name = 'satellite_analyses';

-- Check for enriched data points
SELECT id, ndvi_value, ndmi_value, ndre_value, evi_value, msi_value, savi_value, gndvi_value 
FROM satellite_analyses 
LIMIT 5;
```

---

**Testing Complete?** Mark this phase as tested in `Development-Roadmap.md`

**Estimated Total Time:** 8-10 minutes (excluding automated tests)

**Last Updated:** January 16, 2026
