# Satellite Overlay & Manual Metrics Analysis

**Date:** January 13, 2026  
**Purpose:** Evaluate alignment between satellite overlays and manual field measurements

---

## Executive Summary

**Current State:**
- **Satellite overlays:** 3 (NDVI, NDMI/Moisture, True Color RGB)
- **Manual metrics:** 20+ environmental measurements
- **Gap:** Limited satellite indices vs. extensive manual data collection capabilities

**Key Findings:**
1. ✅ Recent migration added 12 satellite-correlated metrics (excellent!)
2. ❌ Only 2 satellite indices implemented vs. 15+ available from Sentinel-2
3. ⚠️ Atmospheric metrics (NO₂, O₃, SO₂) added but require Sentinel-5P integration
4. 💡 Missing key vegetation indices (EVI, SAVI, NDRE) that would improve correlation

---

## Current Implementation

### Satellite Overlays (Sentinel-2 L2A)

| Overlay | Bands Used | Formula | Use Case |
|---------|------------|---------|----------|
| **NDVI** | B04 (Red), B08 (NIR) | `(NIR - Red) / (NIR + Red)` | Vegetation health, biomass |
| **NDMI** | B8A (NIR narrow), B11 (SWIR1) | `(NIR - SWIR) / (NIR + SWIR)` | Water content, drought stress |
| **True Color RGB** | B02 (Blue), B03 (Green), B04 (Red) | RGB composite | Visual interpretation |

**Available Sentinel-2 Bands (not yet used):**
- B01 (Coastal Aerosol, 443nm)
- B05, B06, B07 (Red Edge, 705-783nm) ← **High value for chlorophyll!**
- B09 (Water Vapor, 945nm)
- B12 (SWIR2, 2190nm)

---

### Manual Survey Metrics

#### Original Metrics (Pre-existing)
1. Air Quality Index
2. Temperature (°C)
3. Humidity (%)
4. Noise Level (dB)
5. PM2.5 (µg/m³)
6. PM10 (µg/m³)
7. CO2 (ppm)

#### New Satellite-Correlated Metrics (Added Jan 13, 2026)

**TIER 1: High Priority - Direct Satellite Correlation**
8. ✅ Land Surface Temperature (°C) - validates Landsat-8/9 LST
9. ✅ Nitrogen Dioxide NO₂ (µg/m³) - validates Sentinel-5P
10. ✅ Chlorophyll Content (µg/cm²) - validates Sentinel-2 Red Edge bands

**TIER 2: Medium Priority - Satellite Products Available**
11. ✅ Leaf Area Index LAI (m²/m²) - validates Copernicus LAI product
12. ✅ Soil Moisture (% VWC) - validates Sentinel-1 SAR + NDMI
13. ✅ Aerosol Optical Depth AOD (dimensionless) - validates Sentinel-5P + PM algorithms

**TIER 3: Additional Atmospheric**
14. ✅ Ozone O₃ (ppb) - validates Sentinel-5P
15. ✅ Sulfur Dioxide SO₂ (µg/m³) - validates Sentinel-5P

**Water Quality**
16. ✅ Water Turbidity (NTU) - validates Sentinel-2 water quality indices
17. ✅ Chlorophyll-a Aquatic (mg/m³) - validates Sentinel-2/3 ocean color

**Vegetation Biophysical**
18. ✅ FAPAR (dimensionless) - validates Copernicus FAPAR
19. ✅ Canopy Chlorophyll Content (g/m²) - validates Sentinel-2 CCC product

---

## Gap Analysis

### 1. Missing Satellite Overlays (High Impact)

These indices use **already-available Sentinel-2 bands** and would significantly improve correlation with manual data:

#### Priority 1: Enhanced Vegetation Indices

**EVI (Enhanced Vegetation Index)**
- **Formula:** `2.5 * ((NIR - Red) / (NIR + 6*Red - 7.5*Blue + 1))`
- **Bands:** B02 (Blue), B04 (Red), B08 (NIR)
- **Advantage over NDVI:** Better in dense canopy, less atmospheric interference
- **Correlates with:** LAI, FAPAR, biomass

**SAVI (Soil-Adjusted Vegetation Index)**
- **Formula:** `((NIR - Red) / (NIR + Red + L)) * (1 + L)` where L=0.5
- **Bands:** B04 (Red), B08 (NIR)
- **Advantage:** Corrects for soil brightness in sparse vegetation
- **Correlates with:** LAI in agricultural/semi-arid areas

**NDRE (Normalized Difference Red Edge)**
- **Formula:** `(NIR - RedEdge) / (NIR + RedEdge)`
- **Bands:** B05/B06/B07 (Red Edge), B08 (NIR)
- **Advantage:** **Highly sensitive to chlorophyll content** - directly validates metric #10
- **Correlates with:** Chlorophyll Content, Canopy Chlorophyll Content, nitrogen status

#### Priority 2: Water & Stress Indices

**NDWI (Normalized Difference Water Index)**
- **Formula:** `(Green - NIR) / (Green + NIR)`
- **Bands:** B03 (Green), B08 (NIR)
- **Use:** Water body detection, flood mapping
- **Correlates with:** Water Turbidity

**MSI (Moisture Stress Index)**
- **Formula:** `SWIR1 / NIR`
- **Bands:** B08 (NIR), B11 (SWIR1)
- **Use:** Plant water stress detection
- **Correlates with:** Soil Moisture (complements NDMI)

#### Priority 3: Advanced Indices

**GNDVI (Green Normalized Difference Vegetation Index)**
- **Formula:** `(NIR - Green) / (NIR + Green)`
- **Bands:** B03 (Green), B08 (NIR)
- **Advantage:** More sensitive to chlorophyll concentration than NDVI
- **Correlates with:** Chlorophyll Content

**CIgreen / CIrededge (Chlorophyll Indices)**
- **Formula:** `(NIR / Green) - 1` or `(NIR / RedEdge) - 1`
- **Bands:** B03 (Green) or B05 (Red Edge), B08 (NIR)
- **Use:** Direct chlorophyll estimation
- **Correlates with:** Chlorophyll Content, Canopy Chlorophyll Content

**NBR (Normalized Burn Ratio)**
- **Formula:** `(NIR - SWIR2) / (NIR + SWIR2)`
- **Bands:** B08 (NIR), B12 (SWIR2)
- **Use:** Fire damage assessment, post-fire recovery
- **Correlates with:** Ecosystem health after disturbance

---

### 2. Missing Manual Metrics (Ground-Truth for Satellite)

These field measurements would provide validation for satellite products:

#### Vegetation Structure
20. **Canopy Cover (%)** - visual estimation or densiometer
   - Validates NDVI, EVI, LAI
   - Low cost, high value
   
21. **Vegetation Height (m)** - measuring pole or laser rangefinder
   - Validates biomass, canopy structure
   - Important for forest monitoring

22. **Plant Species Richness (count)** - biodiversity assessment
   - Satellite cannot measure directly
   - Validates ecosystem health from spectral diversity

#### Environmental Context
23. **Leaf Wetness (boolean/%)** - wetness sensor or visual
   - Validates moisture indices (NDMI, MSI)
   - Important for disease risk
   
24. **Soil Temperature (°C)** - soil probe
   - Complements Land Surface Temperature
   - Ground-truth for thermal products

25. **Snow/Ice Cover (%)** - visual estimation
   - Validates NDSI (Normalized Difference Snow Index)
   - Seasonal monitoring

#### Water Quality
26. **Secchi Depth (m)** - Secchi disk
   - Direct water clarity measurement
   - Validates Water Turbidity + satellite water quality indices
   - Low cost, scientifically robust

27. **Water Surface Temperature (°C)** - IR thermometer
   - Validates thermal band LST over water bodies
   - Important for aquatic ecosystem health

---

## Recommendations

### Immediate Actions (Week 1-2)

#### 1. Implement Missing Sentinel-2 Indices ✅ HIGH PRIORITY

Add these methods to `CopernicusDataSpaceService.php`:

```php
// NDRE - Best for chlorophyll correlation
public function getNDREData(float $lat, float $lon, ?string $date = null): ?array
{
    // Formula: (B08 - B05) / (B08 + B05)
    // Directly validates "Chlorophyll Content" metric
}

// EVI - Better than NDVI for dense vegetation
public function getEVIData(float $lat, float $lon, ?string $date = null): ?array
{
    // Formula: 2.5 * ((B08 - B04) / (B08 + 6*B04 - 7.5*B02 + 1))
    // Validates LAI, FAPAR
}

// SAVI - Soil-corrected vegetation index
public function getSAVIData(float $lat, float $lon, ?string $date = null): ?array
{
    // Formula: ((B08 - B04) / (B08 + B04 + 0.5)) * 1.5
    // Better for sparse vegetation
}

// MSI - Moisture stress (complements NDMI)
public function getMSIData(float $lat, float $lon, ?string $date = null): ?array
{
    // Formula: B11 / B08
    // Validates "Soil Moisture" metric
}
```

**Add to satellite viewer as new overlay options:**
- NDRE Overlay (chlorophyll-specific)
- EVI Overlay (enhanced vegetation)
- MSI Overlay (moisture stress)

#### 2. Update SatelliteAnalysis Model

Add new fields for additional indices:

```php
// Migration: add_advanced_satellite_indices
$table->decimal('evi_value', 5, 3)->nullable();
$table->decimal('savi_value', 5, 3)->nullable();
$table->decimal('ndre_value', 5, 3)->nullable();
$table->decimal('msi_value', 5, 3)->nullable();
$table->decimal('gndvi_value', 5, 3)->nullable();
```

#### 3. Enhance DataPoint Enrichment Job

Update `EnrichDataPointWithSatelliteData.php` to fetch all indices:

```php
// Fetch multiple indices in parallel
$ndvi = $service->getNDVIData($lat, $lon, $date);
$ndmi = $service->getMoistureData($lat, $lon, $date);
$ndre = $service->getNDREData($lat, $lon, $date);  // NEW
$evi = $service->getEVIData($lat, $lon, $date);    // NEW
$msi = $service->getMSIData($lat, $lon, $date);    // NEW

// Store comprehensive analysis
SatelliteAnalysis::create([
    'data_point_id' => $dataPoint->id,
    'ndvi_value' => $ndvi['value'] ?? null,
    'ndmi_value' => $ndmi['value'] ?? null,
    'ndre_value' => $ndre['value'] ?? null,  // NEW
    'evi_value' => $evi['value'] ?? null,    // NEW
    'msi_value' => $msi['value'] ?? null,    // NEW
    // ...
]);
```

---

### Medium-Term Actions (Week 3-4)

#### 4. Add Low-Cost Manual Metrics

Expand field survey form to include:

```php
// Easy-to-collect metrics that validate satellite data
'canopy_cover_percent' => 'nullable|numeric|min:0|max:100',
'vegetation_height_m' => 'nullable|numeric|min:0|max:100',
'leaf_wetness' => 'nullable|boolean',
'secchi_depth_m' => 'nullable|numeric|min:0|max:50', // for water bodies
```

**Benefits:**
- Low cost (visual estimation or simple tools)
- High scientific value (ground-truth)
- Improves satellite validation

#### 5. Create Metric-to-Satellite Correlation Matrix

Add to `GeospatialService.php`:

```php
public function getMetricSatelliteCorrelation(int $metricId): array
{
    return [
        'Chlorophyll Content' => ['primary' => 'NDRE', 'secondary' => ['GNDVI', 'CIgreen']],
        'Soil Moisture' => ['primary' => 'NDMI', 'secondary' => ['MSI']],
        'LAI' => ['primary' => 'EVI', 'secondary' => ['NDVI', 'SAVI']],
        'FAPAR' => ['primary' => 'EVI', 'secondary' => ['NDVI']],
        // ...
    ];
}
```

**Use in UI:** Show recommended satellite indices when selecting metric for analysis

---

### Long-Term Considerations

#### 6. Sentinel-5P Integration (Atmospheric Gases)

**Current Issue:** Metrics NO₂, O₃, SO₂, AOD added but require **Sentinel-5P** (not Sentinel-2)

**CRITICAL INSIGHT:** Sentinel-5P also validates **original metrics** PM2.5, PM10, and AQI!

**Dual Benefits:**
1. **New Metrics (direct):** NO₂, O₃, SO₂, AOD (exact match)
2. **Original Metrics (correlation):** 
   - PM2.5 ↔ AOD (R² = 0.70-0.85)
   - PM10 ↔ AOD (R² = 0.65-0.80)
   - AQI ↔ NO₂ + O₃ composite (urban air quality validation)

**Options:**
1. **Implement Sentinel-5P service** (recommended for completeness)
   - Resolution: 5.5km (coarse but useful for regional air quality)
   - API: Same Copernicus Data Space
   - Products: NO₂, O₃, SO₂, CO, CH₄, HCHO, AOD
   - **Validates 7 metrics total:** NO₂, O₃, SO₂, AOD + PM2.5, PM10, AQI
   
2. **Mark metrics as "satellite-unavailable"** in schema
   - Add field: `satellite_validation_available: boolean`
   - Keep for manual surveys even without satellite correlation

**Recommendation:** Implement Sentinel-5P for atmospheric metrics (separate PR)

**Scientific Value:**
- AOD-to-PM2.5 conversion algorithms widely published (van Donkelaar et al. 2016)
- Regional air quality monitoring from space
- Validates manual sensors against satellite observations

#### 7. Landsat-8/9 Integration (Thermal)

**For Land Surface Temperature:**
- Current: Added to metrics but no satellite implementation
- Sentinel-2 lacks thermal bands
- **Landsat-8/9** has thermal bands (TIRS 10.9µm, 12.0µm)
- Resolution: 100m (lower than Sentinel-2 but sufficient for LST)

**CRITICAL INSIGHT:** LST also validates **original metric** Temperature (°C)!

**Dual Benefits:**
1. **New Metric (direct):** Land Surface Temperature (exact match)
2. **Original Metric (strong correlation):**
   - Air Temperature ↔ LST (R² = 0.85-0.95 in open areas)
   - Validates field thermometer readings
   - Shows urban heat island effects

**Implementation:**
- Add `LandsatService.php` for LST retrieval
- Or use NASA LANCE NRT for near-real-time LST
- Algorithm: Split-window atmospheric correction (Jiménez-Muñoz & Sobrino 2003)

**Scientific Value:**
- Strong correlation between LST and air temperature
- Temporal validation: satellite overpass time vs. manual survey time
- Spatial context: point measurement vs. area average

---

## Scientific Impact

### Improved Correlations

#### New Metrics (Added Jan 13, 2026)

| Manual Metric | Current Best Satellite | Recommended Addition | Improvement |
|---------------|------------------------|----------------------|-------------|
| Chlorophyll Content | None | **NDRE** | ✅ Direct correlation (R² > 0.8) |
| LAI | NDVI | **EVI** | ✅ Less saturation in dense canopy |
| Soil Moisture | NDMI | **MSI** (complement) | ✅ Multiple stress indicators |
| Canopy Chlorophyll | None | **NDRE + CIrededge** | ✅ Canopy-scale estimation |
| Water Turbidity | None (planned) | **NDWI + B04/B03 ratio** | ✅ Suspended sediment detection |

#### Original Metrics (Pre-existing - Also Need Satellite Coverage!)

| Manual Metric | Current Best Satellite | Recommended Addition | Improvement |
|---------------|------------------------|----------------------|-------------|
| **Temperature (°C)** | None | **Landsat-8/9 LST** | ✅ Strong correlation (R² = 0.85-0.95) - validates field measurements |
| **PM2.5 (µg/m³)** | None | **Sentinel-5P AOD** | ✅ Moderate correlation (R² = 0.70-0.85) - regional air quality |
| **PM10 (µg/m³)** | None | **Sentinel-5P AOD** | ✅ Moderate correlation (R² = 0.65-0.80) - particulate matter |
| **AQI** | None | **Sentinel-5P NO₂ + O₃ + AOD** | ✅ Multi-pollutant validation - urban air quality |
| Humidity (%) | None | **NDMI / MSI** (indirect) | 🟡 Weak correlation - vegetation water content proxy |
| Noise Level (dB) | None | None available | ❌ No satellite equivalent - manual only |
| CO₂ (ppm) | None | **Sentinel-5P CO** (not CO₂) | 🟡 Different gas - weak correlation |

**Key Insight:** Your **original 7 metrics** also benefit from LST and Sentinel-5P integration! This wasn't obvious until the new metrics were added.

### Publication-Ready Exports

With comprehensive satellite coverage, exported data will include:

```json
{
  "data_point_id": 123,
  "location": {"lat": 55.7, "lon": 12.5},
  "manual_measurements": {
    "chlorophyll_content": 42.5,
    "soil_moisture": 28.3
  },
  "satellite_validation": {
    "date": "2026-01-10",
    "temporal_difference_hours": 6,
    "indices": {
      "ndvi": 0.72,
      "evi": 0.58,
      "ndre": 0.35,  // NEW - correlates with chlorophyll
      "ndmi": 0.18,
      "msi": 1.45    // NEW - correlates with soil moisture
    },
    "correlation_quality": "excellent"
  }
}
```

**Scientific Value:**
- Multi-index validation reduces uncertainty
- Temporal alignment ensures environmental consistency
- Full provenance for peer review

---

## Implementation Priority Matrix

| Task | Impact | Effort | Priority | Timeline | Metrics Validated |
|------|--------|--------|----------|----------|-------------------|
| Add NDRE overlay | 🔴 High | 🟢 Low | **P0** | Week 1 | 2 (Chlorophyll, Canopy Chlorophyll) |
| Add EVI overlay | 🔴 High | 🟢 Low | **P0** | Week 1 | 2 (LAI, FAPAR) |
| Add MSI overlay | 🟡 Medium | 🟢 Low | **P1** | Week 1 | 1 (Soil Moisture) |
| Update enrichment job | 🔴 High | 🟡 Medium | **P0** | Week 2 | All new indices |
| Add SatelliteAnalysis fields | 🔴 High | 🟢 Low | **P0** | Week 2 | Database schema |
| Add manual metrics (canopy cover, height) | 🟡 Medium | 🟢 Low | **P1** | Week 3 | 2-3 ground-truth |
| **Sentinel-5P integration** | **🔴 High** | 🔴 High | **P2** | Phase 5 | **7 (NO₂, O₃, SO₂, AOD, PM2.5, PM10, AQI)** |
| **Landsat LST integration** | **🔴 High** | 🔴 High | **P2** | Phase 5 | **2 (LST, Temperature)** |
| NDWI, GNDVI overlays | 🟢 Low | 🟢 Low | **P3** | Phase 6 | 1-2 (Water, Chlorophyll) |
| SAVI overlay | 🟡 Medium | 🟢 Low | **P1** | Week 2 | 1 (LAI in sparse vegetation) |

**Legend:**
- 🔴 High / 🟡 Medium / 🟢 Low
- P0 = Critical (this phase), P1 = Important (this phase), P2 = Future phase, P3 = Optional

**Impact Re-assessment:**
- **Sentinel-5P upgraded to HIGH impact** - validates 7 metrics (not just 4 new ones!)
- **Landsat LST upgraded to HIGH impact** - validates 2 metrics including original Temperature
- These are **Phase 5 priorities** due to high effort, but scientific value is critical

---

## Code Changes Required

### 1. Service Layer (5 new methods)

```php
// app/Services/CopernicusDataSpaceService.php

public function getNDREData(...): ?array { }
public function getEVIData(...): ?array { }
public function getSAVIData(...): ?array { }
public function getMSIData(...): ?array { }
public function getGNDVIData(...): ?array { }

// Plus corresponding evalscripts (5 new methods)
private function getNDREScript(): string { }
private function getEVIScript(): string { }
// ...
```

### 2. Database (1 migration)

```php
// database/migrations/xxxx_add_advanced_satellite_indices.php

$table->decimal('evi_value', 5, 3)->nullable();
$table->decimal('savi_value', 5, 3)->nullable();
$table->decimal('ndre_value', 5, 3)->nullable();
$table->decimal('msi_value', 5, 3)->nullable();
$table->decimal('gndvi_value', 5, 3)->nullable();
```

### 3. Job Update (1 file)

```php
// app/Jobs/EnrichDataPointWithSatelliteData.php

// Fetch all 7 indices (current 2 + new 5)
// Store in SatelliteAnalysis
```

### 4. UI Updates (2 files)

```blade
<!-- resources/views/livewire/maps/satellite-viewer.blade.php -->
<!-- Add overlay options: NDRE, EVI, MSI -->
```

```javascript
// resources/js/maps/satellite-map.js
// Handle new overlay types
```

---

## Testing Requirements

```php
// tests/Feature/CopernicusDataSpaceServiceTest.php

test('fetches NDRE data successfully');
test('fetches EVI data successfully');
test('fetches MSI data successfully');
test('all indices stored in SatelliteAnalysis');

// tests/Feature/Jobs/EnrichDataPointWithSatelliteDataTest.php

test('enrichment fetches all 7 indices');
test('handles partial failures gracefully');
```

**Estimated test count:** +8 tests

---

## Conclusion

**Current Strengths:**
- ✅ Recent migration added excellent manual metrics
- ✅ Database schema ready for satellite validation
- ✅ Auto-enrichment infrastructure in place

**Critical Gaps:**
- ❌ Only 2 satellite indices vs. 15+ available
- ❌ Missing **NDRE** - the best index for chlorophyll validation
- ❌ Missing **EVI** - better than NDVI for LAI/FAPAR

**Recommended Action:**
1. **Week 1:** Add NDRE + EVI overlays (highest impact, low effort)
2. **Week 2:** Update enrichment job to fetch all new indices
3. **Week 3:** Add simple manual metrics (canopy cover, vegetation height)
4. **Phase 5:** Implement Sentinel-5P + Landsat LST (validates **9 total metrics**: 4 new + 5 original)

**Expected Outcome:**
- 🎯 Direct satellite validation for 80% of manual metrics (vs. current ~30%)
- 🎯 Publication-ready multi-index correlation analysis
- 🎯 Portfolio-worthy remote sensing integration
- 🎯 **Hidden benefit:** Original air quality metrics (PM2.5, PM10, AQI) and Temperature get satellite validation too!

**Critical Discovery:**
- **Sentinel-5P isn't just for new atmospheric metrics** - it validates your original PM2.5, PM10, and AQI data via AOD correlation (R² = 0.65-0.85)
- **Landsat LST isn't just for new LST metric** - it validates your original Temperature measurements (R² = 0.85-0.95)
- This transforms your app from "ground sensors only" to "satellite-validated environmental monitoring platform"

---

**Next Steps:**
1. Review this analysis with stakeholders
2. Prioritize P0 tasks for immediate implementation
3. Update Phase 4 roadmap with satellite index additions
4. Begin implementation of NDRE + EVI overlays


