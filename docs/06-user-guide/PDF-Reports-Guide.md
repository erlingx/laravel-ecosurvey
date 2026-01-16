# PDF Reports - User Guide

**Feature:** Campaign PDF Reports (Phase 7)  
**Access:** `/admin/campaigns` → Actions → Export as PDF

---

## Quick Start

1. Go to **Manage Campaigns** (`/admin/campaigns`)
2. Find your campaign in the list
3. Click **Actions** dropdown
4. Select **Export as PDF**
5. PDF downloads automatically

**Filename:** `ecosurvey-report-{campaign-name}-{date}.pdf`

---

## Report Contents

### 1. Campaign Overview
- Campaign name and status
- Owner information
- Creation date
- Total data points

### 2. Data Quality Statistics
- Approved count (green)
- Pending count (yellow)
- Draft count (gray)
- Rejected count (red)
- Average GPS accuracy (meters)
- Satellite enriched count

### 3. Survey Zones
- Zone names and descriptions
- Area calculations (km²)
- Table format

### 4. Statistical Summary
Per metric type:
- Sample count (n)
- Minimum value
- Maximum value
- Average (mean)
- Median
- Standard deviation (σ)
- Units displayed

### 5. Satellite Index Coverage
All 7 indices listed:
- NDVI - General vegetation health
- NDMI - Soil moisture
- NDRE - Chlorophyll content (R²=0.80-0.90)
- EVI - Dense canopy LAI (R²=0.75-0.85)
- MSI - Drought stress (R²=0.70-0.80)
- SAVI - Sparse vegetation (R²=0.70-0.80)
- GNDVI - Chlorophyll sensitive (R²=0.75-0.85)

### 6. Methodology
- Coordinate system: WGS84 (EPSG:4326)
- GPS accuracy standards
- Satellite data source
- Statistical methods

---

## Use Cases

**Research Publication:**
- Professional formatting
- Scientific methodology documented
- Statistical rigor demonstrated
- Satellite validation coverage shown

**Project Documentation:**
- Campaign summary for stakeholders
- Data quality transparency
- Comprehensive metrics overview

**Archival:**
- Complete campaign snapshot
- Exportable format
- Self-contained documentation

---

## PDF Features

**Professional Layout:**
- Blue color scheme
- Clear section headers
- Responsive tables
- Statistical grids

**Color-Coded Stats:**
- Approved: Green
- Pending: Yellow
- Rejected: Red
- Draft: Gray

**Metadata:**
- Generation date/time
- Page numbers
- EcoSurvey branding

---

## Tips

**Best Practices:**
- Generate reports after campaign completion
- Include approved data points only
- Verify statistics before export
- Archive PDFs for long-term records

**Quality Checks:**
- Review data quality stats
- Confirm GPS accuracy levels
- Check satellite enrichment count
- Validate statistical summaries

---

## Other Export Formats

**JSON Export:**
- `/campaigns/{id}/export/json`
- Full data structure
- Satellite analyses included
- API integration ready

**CSV Export:**
- `/campaigns/{id}/export/json`
- Spreadsheet compatible
- Column-based format
- Excel/R/Python ready

---

## Technical Details

**PDF Engine:** DomPDF v3.1  
**Font:** DejaVu Sans (PDF-safe)  
**Page Size:** A4  
**Max File Size:** ~5MB (typical)

**Generation Time:**
- Small campaigns (<100 points): <2s
- Medium campaigns (100-1000 points): 2-5s
- Large campaigns (>1000 points): 5-10s
