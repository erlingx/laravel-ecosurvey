# Data Export Guide - JSON & CSV

**Feature:** Campaign Data Export (Phase 4 & Phase 7)  
**Access:** `/admin/campaigns` → Export dropdown  
**Formats:** JSON, CSV, PDF

---

## Quick Start

1. Navigate to **Manage Campaigns** (`/admin/campaigns`)
2. Click **Export** button (blue, download icon)
3. Select format:
   - **Export as JSON** - Full data structure
   - **Export as CSV** - Spreadsheet format
   - **Export as PDF** - Professional report (see [PDF Reports Guide](PDF-Reports-Guide.md))

**Filename:** `ecosurvey-{campaign-name}-{date}.{format}`

---

## JSON Export

### Overview

**URL:** `/campaigns/{id}/export/json`  
**Content-Type:** `application/json`  
**Use Cases:**
- API integration
- Backup and archival
- GIS software import
- Custom data processing
- Database migration

### Data Structure

```json
{
  "metadata": {
    "export_date": "2026-01-16T10:30:00Z",
    "campaign_id": 1,
    "campaign_name": "Fælledparken Green Space Study",
    "campaign_start": "2025-08-01",
    "campaign_end": "2025-08-31",
    "coordinate_system": "WGS84 (EPSG:4326)",
    "data_point_count": 130,
    "qa_statistics": {
      "approved_count": 115,
      "pending_count": 10,
      "draft_count": 3,
      "rejected_count": 2,
      "avg_accuracy_meters": 7.3,
      "satellite_enriched_count": 98
    },
    "satellite_indices": {
      "NDVI": "Normalized Difference Vegetation Index",
      "NDMI": "Normalized Difference Moisture Index",
      "NDRE": "Normalized Difference Red Edge (Chlorophyll)",
      "EVI": "Enhanced Vegetation Index",
      "MSI": "Moisture Stress Index",
      "SAVI": "Soil-Adjusted Vegetation Index",
      "GNDVI": "Green Normalized Difference Vegetation Index"
    },
    "temporal_correlation_note": "temporal_offset_days indicates days between field measurement and satellite observation"
  },
  "data_points": [
    {
      "id": 123,
      "collected_at": "2025-08-15T14:30:00Z",
      "location": {
        "latitude": 55.7072,
        "longitude": 12.5704,
        "accuracy_meters": 6.5
      },
      "measurement": {
        "value": 22.5,
        "metric_name": "Temperature",
        "metric_unit": "°C"
      },
      "quality_control": {
        "status": "approved",
        "device_model": "iPhone 14",
        "sensor_type": "built-in",
        "calibration_at": "2025-08-01T00:00:00Z",
        "protocol_version": "1.0"
      },
      "satellite_context": {
        "ndvi_value": 0.756,
        "ndmi_value": 0.234,
        "ndre_value": 0.812,
        "evi_value": 0.689,
        "msi_value": 0.456,
        "savi_value": 0.701,
        "gndvi_value": 0.778,
        "temperature_kelvin": 295.3,
        "satellite_date": "2025-08-14",
        "cloud_coverage_percent": 5,
        "satellite_source": "Sentinel-2 (Copernicus Data Space)",
        "temporal_offset_days": 1,
        "temporal_quality": "excellent"
      },
      "metadata": {
        "notes": "Clear sunny day, measured in shade",
        "photo_url": "https://example.com/photos/123.jpg",
        "user_name": "Jane Researcher"
      }
    }
  ]
}
```

### What's Included

**Metadata Section:**
- Campaign details (name, dates, status)
- QA statistics (approved/pending/rejected counts)
- GPS accuracy metrics
- Satellite index definitions
- Export timestamp

**Data Points (Approved Only):**
- GPS coordinates (WGS84)
- GPS accuracy in meters
- Measurement value and unit
- Collection timestamp
- Quality control data
- Device and sensor info
- Calibration status
- Satellite analyses (all 7 indices)
- Temporal correlation quality
- User notes and photos

### Benefits

✅ **Complete Data Structure** - Nested relationships preserved  
✅ **Satellite Integration** - All 7 indices included  
✅ **Quality Assurance** - QA statistics and metadata  
✅ **API Ready** - Direct integration with applications  
✅ **Backup Safe** - Full data for restoration  

---

## CSV Export

### Overview

**URL:** `/campaigns/{id}/export/csv`  
**Content-Type:** `text/csv`  
**Use Cases:**
- Excel/LibreOffice analysis
- R statistical software
- Python pandas DataFrames
- SPSS, Stata, SAS
- Database imports

### Column Structure

**Headers:**
```
ID,Campaign,Metric,Value,Unit,Latitude,Longitude,Accuracy,Collection_Date,User,Status,Device,Sensor,Calibration_Date,NDVI,NDMI,NDRE,EVI,MSI,SAVI,GNDVI,Satellite_Date,Cloud_Coverage,Temporal_Offset_Days,Photo_URL,Notes
```

**Example Row:**
```csv
123,Fælledparken Study,Temperature,22.5,°C,55.7072,12.5704,6.5,2025-08-15 14:30:00,Jane Researcher,approved,iPhone 14,built-in,2025-08-01,0.756,0.234,0.812,0.689,0.456,0.701,0.778,2025-08-14,5,1,https://example.com/photos/123.jpg,Clear sunny day
```

### Column Descriptions

| Column | Type | Description |
|--------|------|-------------|
| ID | Integer | Data point unique ID |
| Campaign | String | Campaign name |
| Metric | String | Environmental metric name |
| Value | Decimal | Measurement value |
| Unit | String | Measurement unit (°C, dB, ppm, etc.) |
| Latitude | Decimal | GPS latitude (WGS84) |
| Longitude | Decimal | GPS longitude (WGS84) |
| Accuracy | Decimal | GPS accuracy in meters |
| Collection_Date | DateTime | ISO 8601 timestamp |
| User | String | User who collected data |
| Status | String | approved/pending/draft/rejected |
| Device | String | Device model used |
| Sensor | String | Sensor type |
| Calibration_Date | Date | Last calibration date |
| NDVI | Decimal | Vegetation index (-1 to +1) |
| NDMI | Decimal | Moisture index (-1 to +1) |
| NDRE | Decimal | Chlorophyll index (-1 to +1) |
| EVI | Decimal | Enhanced vegetation (-1 to +1) |
| MSI | Decimal | Moisture stress (0 to 3+) |
| SAVI | Decimal | Soil-adjusted veg (-1 to +1) |
| GNDVI | Decimal | Green vegetation (-1 to +1) |
| Satellite_Date | Date | Satellite observation date |
| Cloud_Coverage | Integer | Cloud coverage % |
| Temporal_Offset_Days | Integer | Days between field/satellite |
| Photo_URL | String | URL to photo (if exists) |
| Notes | String | User notes |

### Benefits

✅ **Spreadsheet Ready** - Opens directly in Excel  
✅ **Flat Structure** - Easy to analyze  
✅ **Statistical Software** - Import into R/Python/SPSS  
✅ **Human Readable** - Easy to review and verify  
✅ **Database Import** - Standard CSV format  

---

## Using Exported Data

### Excel/LibreOffice

**Steps:**
1. Export as CSV
2. Open in Excel/LibreOffice Calc
3. Data automatically formatted in columns

**Tips:**
- Use filters to analyze subsets
- Create pivot tables for summaries
- Generate charts for visualization

### R Statistical Analysis

**Load Data:**
```r
# Load CSV
data <- read.csv("ecosurvey-campaign-2026-01-16.csv")

# Summary statistics
summary(data$Value)
summary(data$NDVI)

# Filter by metric
temp_data <- data[data$Metric == "Temperature", ]

# Correlation analysis
cor(temp_data$Value, temp_data$NDVI, use = "complete.obs")

# Plot
plot(temp_data$NDVI, temp_data$Value, 
     xlab = "NDVI", ylab = "Temperature (°C)",
     main = "Temperature vs NDVI")
```

**Load JSON:**
```r
library(jsonlite)
data <- fromJSON("ecosurvey-campaign-2026-01-16.json")

# Access nested data
metadata <- data$metadata
points <- data$data_points
```

### Python Analysis

**Load CSV:**
```python
import pandas as pd

# Load data
df = pd.read_csv('ecosurvey-campaign-2026-01-16.csv')

# Summary statistics
print(df['Value'].describe())
print(df['NDVI'].describe())

# Group by metric
grouped = df.groupby('Metric')['Value'].agg(['mean', 'std', 'min', 'max'])
print(grouped)

# Correlation
correlation = df[['Value', 'NDVI']].corr()
print(correlation)

# Visualization
import matplotlib.pyplot as plt
df.plot.scatter(x='NDVI', y='Value')
plt.show()
```

**Load JSON:**
```python
import json
import pandas as pd

with open('ecosurvey-campaign-2026-01-16.json') as f:
    data = json.load(f)

# Convert to DataFrame
df = pd.json_normalize(data['data_points'])
metadata = data['metadata']
```

### GIS Software (QGIS, ArcGIS)

**JSON Import:**
1. Use "Add Vector Layer" → GeoJSON
2. Specify coordinate reference system: EPSG:4326 (WGS84)
3. Data points appear on map

**CSV Import:**
1. Use "Add Delimited Text Layer"
2. X field: Longitude
3. Y field: Latitude
4. CRS: EPSG:4326

---

## Export Comparison

| Feature | JSON | CSV | PDF |
|---------|------|-----|-----|
| **Structure** | Nested | Flat | Formatted |
| **Size** | Medium | Small | Large |
| **Human Readable** | ✓ | ✓✓ | ✓✓✓ |
| **Machine Readable** | ✓✓✓ | ✓✓ | ✗ |
| **Relationships** | Preserved | Lost | N/A |
| **API Integration** | ✓✓✓ | ✓ | ✗ |
| **Spreadsheet** | ✗ | ✓✓✓ | ✗ |
| **Publication** | ✗ | ✗ | ✓✓✓ |
| **Backup** | ✓✓✓ | ✓✓ | ✗ |

**Recommendations:**
- **Analysis:** CSV (R, Python, Excel)
- **Integration:** JSON (APIs, databases)
- **Publication:** PDF (reports, presentations)
- **Backup:** JSON (complete structure)

---

## Data Quality Notes

**Only Approved Data:**
- Exports include only `status = 'approved'` data points
- Pending/draft/rejected points excluded
- Ensures data quality

**Satellite Enrichment:**
- Not all data points have satellite data
- Check `satellite_enriched_count` in metadata
- NULL values possible for satellite indices

**GPS Accuracy:**
- `avg_accuracy_meters` shows average GPS precision
- Individual accuracy in each data point
- Filter by accuracy if needed (<10m recommended)

**Temporal Correlation:**
- `temporal_offset_days` shows field-to-satellite gap
- 0-3 days: Excellent correlation
- 4-7 days: Good correlation
- 8-14 days: Acceptable
- 15+ days: Poor correlation

---

## Tips & Best Practices

**Before Exporting:**
- Ensure data points are approved
- Review campaign metadata
- Check GPS accuracy levels
- Verify satellite enrichment status

**File Naming:**
- Files auto-named with campaign and date
- Rename for clarity if needed
- Keep original filename format for tracking

**Data Validation:**
- Check row/point counts match expectations
- Verify coordinate ranges (latitude/longitude valid)
- Review satellite index ranges (-1 to +1, or 0 to 3 for MSI)
- Confirm timestamps are correct

**Storage:**
- Archive exports with campaign documentation
- Include metadata in file organization
- Version control for different export dates

---

## Troubleshooting

**Empty Export:**
- Check campaign has approved data points
- Verify permissions
- Review campaign status

**Missing Satellite Data:**
- Some points may not be enriched yet
- Check enrichment job status
- Satellite data depends on cloud-free imagery

**CSV Encoding Issues:**
- Use UTF-8 encoding when opening
- Excel: Use "Data" → "From Text/CSV" for proper encoding
- Special characters may need encoding settings

**Large File Sizes:**
- Campaigns with >1000 points produce large files
- Consider filtering by date range (future feature)
- Use CSV for smaller file sizes

---

## Related Guides

- **[PDF Reports Guide](PDF-Reports-Guide.md)** - Generate professional reports
- **[Campaign Management](Campaign-Management-Guide.md)** - Manage campaigns
- **[Satellite Viewer](Satellite-Viewer-Guide.md)** - View satellite indices
- **[Satellite Indices Reference](Satellite-Indices-Reference.md)** - Index formulas and meanings
