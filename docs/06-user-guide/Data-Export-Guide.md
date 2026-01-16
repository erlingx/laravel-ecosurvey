# Data Export - User Guide

**Feature:** Export Campaign Data (Phase 4)  
**Access:** `/admin/campaigns` → Actions → Export

---

## Export Formats

**JSON:** Structured data with all fields  
**CSV:** Spreadsheet-compatible format

---

## JSON Export

**URL:** `/campaigns/{id}/export/json`

**Includes:**
- Campaign metadata
- All data points with GPS
- Satellite analyses (NDVI, NDMI, etc.)
- Survey zone geometries
- Timestamps and user info

**Use for:** API integration, backup, GIS software

---

## CSV Export

**URL:** `/campaigns/{id}/export/csv`

**Columns:**
- ID, Campaign, Metric
- Value, Unit
- Latitude, Longitude, Accuracy
- Collection Date
- User, Status
- Photo URL
- Notes

**Use for:** Excel, R, Python pandas, statistical analysis

---

## Download Steps

1. Go to **Manage Campaigns**
2. Click **Actions** dropdown on campaign row
3. Select **Export as JSON** or **Export as CSV**
4. File downloads automatically

---

## Tips

- JSON preserves all relationships
- CSV is human-readable
- Both include complete dataset
- Exports respect data permissions
