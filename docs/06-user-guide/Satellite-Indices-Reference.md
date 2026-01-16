# Satellite Indices - Scientific Reference

**Last Updated:** January 16, 2026
**Data Source:** Sentinel-2 (Copernicus Data Space)
**Spatial Resolution:** 10m (visible/NIR), 20m (SWIR)

---

## NDVI - Normalized Difference Vegetation Index

**Formula:** `(NIR - Red) / (NIR + Red)`
**Bands:** B04 (Red 665nm), B08 (NIR 842nm)
**Range:** -1.0 to +1.0
**R² Correlation:** 0.75-0.85 (vegetation health)

**Validates:**
- Vegetation density and health
- Biomass estimation
- Photosynthetic activity

**Reference:** Rouse et al. (1974). Monitoring vegetation systems in the Great Plains with ERTS. *NASA SP-351*, 309-317.

---

## NDMI - Normalized Difference Moisture Index

**Formula:** `(NIR - SWIR1) / (NIR + SWIR1)`
**Bands:** B8A (NIR 865nm), B11 (SWIR1 1610nm)
**Range:** -1.0 to +1.0
**R² Correlation:** 0.70-0.80 (soil moisture)

**Validates:**
- Soil moisture content
- Vegetation water stress
- Drought monitoring

**Reference:** Gao (1996). NDWI - A normalized difference water index for remote sensing of vegetation liquid water from space. *Remote Sensing of Environment*, 58(3), 257-266.

---

## NDRE - Normalized Difference Red Edge

**Formula:** `(NIR - RedEdge) / (NIR + RedEdge)`
**Bands:** B05 (Red Edge 705nm), B08 (NIR 842nm)
**Range:** -1.0 to +1.0
**R² Correlation:** 0.80-0.90 (chlorophyll)

**Validates:**
- Chlorophyll content (µg/cm²)
- Canopy chlorophyll concentration (g/m²)
- Nitrogen status in plants

**Reference:** Gitelson & Merzlyak (1994). Spectral reflectance changes associated with autumn senescence of Aesculus hippocastanum L. and Acer platanoides L. leaves. *Journal of Plant Physiology*, 143(3), 286-292.

---

## EVI - Enhanced Vegetation Index

**Formula:** `2.5 × ((NIR - Red) / (NIR + 6×Red - 7.5×Blue + 1))`
**Bands:** B02 (Blue 490nm), B04 (Red 665nm), B08 (NIR 842nm)
**Range:** -1.0 to +1.0
**R² Correlation:** 0.75-0.85 (LAI, FAPAR)

**Validates:**
- Leaf Area Index (LAI m²/m²)
- FAPAR (Fraction of Absorbed PAR)
- Dense canopy vegetation (better than NDVI)

**Reference:** Huete et al. (2002). Overview of the radiometric and biophysical performance of the MODIS vegetation indices. *Remote Sensing of Environment*, 83(1-2), 195-213.

---

## MSI - Moisture Stress Index

**Formula:** `SWIR1 / NIR`
**Bands:** B08 (NIR 842nm), B11 (SWIR1 1610nm)
**Range:** 0.0 to 3.0+ (higher = more stress)
**R² Correlation:** 0.70-0.80 (soil moisture)

**Validates:**
- Moisture stress levels
- Soil moisture (% VWC - inverse)
- Drought severity

**Reference:** Hunt & Rock (1989). Detection of changes in leaf water content using Near- and Middle-Infrared reflectances. *Remote Sensing of Environment*, 30(1), 43-54.

---

## SAVI - Soil-Adjusted Vegetation Index

**Formula:** `((NIR - Red) / (NIR + Red + 0.5)) × 1.5`
**Bands:** B04 (Red 665nm), B08 (NIR 842nm)
**Range:** -1.0 to +1.0
**R² Correlation:** 0.70-0.80 (sparse LAI)

**Validates:**
- LAI in sparse vegetation
- Agricultural areas with visible soil
- Corrects for soil brightness effects

**Reference:** Huete (1988). A soil-adjusted vegetation index (SAVI). *Remote Sensing of Environment*, 25(3), 295-309.

---

## GNDVI - Green Normalized Difference Vegetation Index

**Formula:** `(NIR - Green) / (NIR + Green)`
**Bands:** B03 (Green 560nm), B08 (NIR 842nm)
**Range:** -1.0 to +1.0
**R² Correlation:** 0.75-0.85 (chlorophyll)

**Validates:**
- Chlorophyll content (µg/cm²)
- Photosynthetic rate
- More sensitive to chlorophyll than NDVI

**Reference:** Gitelson et al. (1996). Use of a green channel in remote sensing of global vegetation from EOS-MODIS. *Remote Sensing of Environment*, 58(3), 289-298.

---

## Sentinel-2 Band Specifications

| Band | Name | Wavelength | Resolution | Use |
|------|------|------------|------------|-----|
| B02 | Blue | 490 nm | 10m | EVI |
| B03 | Green | 560 nm | 10m | GNDVI |
| B04 | Red | 665 nm | 10m | NDVI, EVI, SAVI |
| B05 | Red Edge | 705 nm | 20m | NDRE |
| B08 | NIR | 842 nm | 10m | All indices |
| B8A | NIR (narrow) | 865 nm | 20m | NDMI |
| B11 | SWIR1 | 1610 nm | 20m | NDMI, MSI |

**Source:** ESA Sentinel-2 User Handbook (2015)

---

## Statistical Methods

**Standard Error:** `SE = σ / √n`  
**95% Confidence Interval:** `CI = μ ± (1.96 × SE)`
**Histogram Binning:** Freedman-Diaconis rule: `bin width = 2 × IQR / n^(1/3)`

**Reference:** Freedman & Diaconis (1981). On the histogram as a density estimator: L₂ theory. *Zeitschrift für Wahrscheinlichkeitstheorie und verwandte Gebiete*, 57(4), 453-476.

---

## Data Quality

**Temporal Resolution:** Sentinel-2 revisit time = 5-10 days
**Cloud Cover:** Affects data availability (handled by API)
**Atmospheric Correction:** Level-2A products (surface reflectance)
**Geometric Accuracy:** <10m RMSE

**Reference:** ESA Copernicus Data Space Ecosystem (2024). https://dataspace.copernicus.eu/

---

## Validation Methodology

**Field Measurements:**
- GPS accuracy: ±5-10m (consumer devices)
- Temporal alignment: 0-3 days (excellent), 15+ days (poor)
- Sample size requirement: n ≥ 3 for valid statistics

**Cross-Validation:**
- Multi-index approach (NDRE + GNDVI for chlorophyll)
- Inverse validation (NDMI + MSI for moisture)
- Dense vs sparse canopy (EVI + SAVI for LAI)

---

## Implementation

**Caching:** 1 hour TTL per index/location/date
**Parallel Processing:** All 7 indices fetched simultaneously
**Error Handling:** Partial failures stored as NULL
**Database Storage:** Decimal(5,3) precision

**API:** Sentinel Hub Processing API v1.0
**Authentication:** OAuth2 with token caching

---

## Limitations

**Not Real-Time:** 5-10 day revisit interval
**Cloud Dependency:** Requires cloud-free observations
**Spatial Coverage:** Not all locations have full coverage
**Index Ranges:** Values outside typical ranges indicate anomalies

---

## Further Reading

**Sentinel-2 Documentation:**
- ESA Sentinel Online: https://sentinels.copernicus.eu/web/sentinel/missions/sentinel-2
- Sentinel-2 MSI User Guide: https://sentinel.esa.int/web/sentinel/user-guides/sentinel-2-msi

**Vegetation Indices:**
- Bannari et al. (1995). A review of vegetation indices. *Remote Sensing Reviews*, 13(1-2), 95-120.
- Xue & Su (2017). Significant remote sensing vegetation indices: A review of developments and applications. *Journal of Sensors*, 2017, 1353691.

**Statistical Methods:**
- Zar (2010). *Biostatistical Analysis* (5th ed.). Pearson.
- Wilks (2011). *Statistical Methods in the Atmospheric Sciences* (3rd ed.). Academic Press.
