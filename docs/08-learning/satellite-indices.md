# Sentinel-2 Satellite Indices

## Band Reference (L2A)
| Band | Wavelength | Name | Use |
|------|-----------|------|-----|
| B02 | 490nm | Blue | Atmospheric, water |
| B03 | 560nm | Green | Vegetation peak |
| B04 | 665nm | Red | Chlorophyll absorption |
| B05 | 705nm | Red Edge | Vegetation stress |
| B08 | 842nm | NIR | Plant structure |
| B11 | 1610nm | SWIR1 | Moisture |
| B12 | 2190nm | SWIR2 | Moisture/geology |

Resolution: 10m (B02-B04, B08), 20m (B05, B11-B12)

---

## Vegetation Indices

### NDVI - Normalized Difference Vegetation Index
```
(NIR - Red) / (NIR + Red)
(B08 - B04) / (B08 + B04)
```
**Range:** -1 (water) to +1 (dense vegetation)

**Interpretation:**
- `< 0` → Water
- `0 - 0.1` → Barren rock/sand
- `0.1 - 0.2` → Shrub/grassland
- `0.2 - 0.3` → Sparse vegetation
- `0.3 - 0.6` → Moderate vegetation
- `> 0.6` → Dense vegetation

**Measures:** Photosynthetic activity, biomass

---

### NDMI - Normalized Difference Moisture Index
```
(NIR - SWIR) / (NIR + SWIR)
(B08 - B11) / (B08 + B11)
```
**Range:** -1 to +1

**Interpretation:**
- `< -0.4` → Water stress
- `-0.4 - 0.0` → Low moisture
- `0.0 - 0.4` → Moderate moisture
- `> 0.4` → High moisture

**Measures:** Canopy water content, soil moisture

---

### NDRE - Normalized Difference Red Edge
```
(NIR - RedEdge) / (NIR + RedEdge)
(B08 - B05) / (B08 + B05)
```
**Range:** -1 to +1

**Interpretation:**
- Higher values = more chlorophyll
- More sensitive than NDVI for dense vegetation

**Measures:** Chlorophyll content, nitrogen status

**Correlation:** R² = 0.80-0.90 with leaf chlorophyll

---

### EVI - Enhanced Vegetation Index
```
2.5 * ((NIR - Red) / (NIR + 6*Red - 7.5*Blue + 1))
2.5 * ((B08 - B04) / (B08 + 6*B04 - 7.5*B02 + 1))
```
**Range:** -1 to +1

**Advantages over NDVI:**
- Reduces atmospheric noise
- Less saturation in dense vegetation
- Better sensitivity in high biomass areas

**Measures:** Vegetation health with atmospheric correction

---

### MSI - Moisture Stress Index
```
SWIR1 / NIR
B11 / B08
```
**Range:** 0 to 3+

**Interpretation:**
- `< 0.4` → No water stress
- `0.4 - 0.8` → Moderate stress
- `> 0.8` → High water stress

**Measures:** Plant water stress, irrigation needs

---

### SAVI - Soil-Adjusted Vegetation Index
```
((NIR - Red) / (NIR + Red + L)) * (1 + L)
L = 0.5 (soil brightness correction factor)
```
**Range:** -1 to +1

**Use cases:**
- Low vegetation cover
- Arid/semi-arid regions
- Early growth stages
- Reduces soil background noise

**Measures:** Vegetation with soil adjustment

---

### GNDVI - Green NDVI
```
(NIR - Green) / (NIR + Green)
(B08 - B03) / (B08 + B03)
```
**Range:** -1 to +1

**Differences from NDVI:**
- More sensitive to chlorophyll concentration
- Better for dense canopies
- Alternative validation metric

**Correlation:** R² = 0.75-0.85 with chlorophyll

---

## Evalscript Structure

```javascript
//VERSION=3
function setup() {
    return {
        input: ["B04", "B08"],  // Required bands
        output: { 
            bands: 3,           // RGB
            sampleType: "UINT8" // 0-255
        }
    };
}

function evaluatePixel(sample) {
    let ndvi = (sample.B08 - sample.B04) / (sample.B08 + sample.B04);
    
    // Map [-1, 1] to [0, 255]
    let value = (ndvi + 1) * 127.5;
    
    return [value, value, value];  // Grayscale
}
```

---

## API Response Processing

### PNG Decode → Average
```php
$image = imagecreatefromstring($response->body());
$width = imagesx($image);
$height = imagesy($image);

$sum = 0;
$count = 0;

for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        $rgb = imagecolorat($image, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        
        // Map [0, 255] back to [-1, 1]
        $value = ($r / 127.5) - 1;
        
        if ($value > -0.9) {  // Exclude no-data
            $sum += $value;
            $count++;
        }
    }
}

$average = $count > 0 ? $sum / $count : null;
```

---

## Index Selection Guide

| Goal | Primary Index | Secondary |
|------|--------------|-----------|
| Overall vegetation health | NDVI | EVI |
| Water stress detection | MSI | NDMI |
| Chlorophyll/nitrogen | NDRE | GNDVI |
| Sparse vegetation | SAVI | NDVI |
| Dense canopy | EVI | GNDVI |
| Soil moisture | NDMI | MSI |

---

## Data Quality Checks

### Cloud Coverage
- Check acquisition metadata
- Filter acquisitions with >20% clouds
- Use temporal averaging

### No-Data Pixels
- Exclude values < -0.9
- Check pixel count after filtering
- Require minimum valid pixels

### Temporal Consistency
- Compare ±7 days from collection date
- Use median of multiple acquisitions
- Flag rapid changes

---

## Correlation with Ground Data

### Expected R² Values
- NDVI vs biomass: 0.70-0.85
- NDRE vs chlorophyll: 0.80-0.90
- NDMI vs soil moisture: 0.65-0.80
- MSI vs water stress: 0.70-0.85

### Validation Strategy
```
User measurement → Statistical outlier check
                 ↓
                 Satellite index correlation
                 ↓
                 Official station comparison
                 ↓
                 QA flag assignment
```

---

## Pitfalls

### Formula Errors
- Division by zero: `(B08 + B04)` can be ~0 for water
- Band order: NIR first in numerator
- SAVI L factor: must match setup() calculation

### Range Mapping
- PNG pixel values: 0-255
- Index range varies by type
- MSI doesn't normalize to [-1, 1]

### Temporal Alignment
- Satellite pass ≠ ground collection time
- Use ±7 day window
- Cloud coverage invalidates data

### Atmospheric Effects
- Use L2A (atmospherically corrected)
- EVI better than NDVI for haze
- SWIR bands less affected

### Spatial Resolution
- 10m pixels average ground features
- Single point may not represent area
- Use 0.025° bbox (~2.5km) for averaging
