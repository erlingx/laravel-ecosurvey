# Satellite Viewer - User Guide

**Last Updated:** January 13, 2026  
**Feature Version:** Phase 4 with UX Enhancements  
**Target Audience:** Researchers, Campaign Managers, Data Analysts

---

## Overview

The Satellite Viewer integrates NASA satellite imagery (Sentinel-2) with your field measurements, allowing you to:
- Compare ground observations with satellite data
- Visualize vegetation health (NDVI) and soil moisture
- Validate field measurements across large areas
- Analyze temporal patterns and environmental changes

**Access:** Navigate to **Maps → Satellite Viewer** or visit `/maps/satellite`

---

## Quick Start (30 seconds)

1. **Select a Campaign** from the dropdown (e.g., "Fælledparken Green Space Study")
2. **Choose a Date** when satellite imagery is available
3. **Toggle "Show Field Data"** to overlay your manual measurements
4. **Click any data point marker** to jump to that location and see details

---

## Interface Overview

### Controls (Top Section)

| Control | Purpose |
|---------|---------|
| **Campaign Location** | Filter to specific research campaign (centers map on campaign area) |
| **Data Overlay** | Choose visualization: NDVI (vegetation), Moisture, or True Color |
| **Imagery Date** | Select satellite observation date (cloud-free images may be limited) |
| **Display Options** | Toggle field data visibility and sync mode |

### Map Display (Center Section)

- **Base Map:** Satellite imagery (ESRI World Imagery)
- **Satellite Overlay:** Color-coded NDVI/Moisture visualization
- **Data Point Markers:** Your field measurements (color-coded by temporal alignment)
- **Legend:** Temporal alignment color scale (top-right corner)

---

## Understanding Data Point Colors 🎨

Data point markers are **color-coded** based on how close their collection date is to the selected satellite imagery date. This helps you assess data quality at a glance.

### Color Scale

| Color | Meaning | Days Difference | Quality |
|-------|---------|-----------------|---------|
| 🟢 **Green** | Excellent alignment | 0-3 days | Highest confidence for correlation |
| 🟡 **Yellow** | Good alignment | 4-7 days | Good correlation reliability |
| 🟠 **Orange** | Acceptable alignment | 8-14 days | Moderate correlation reliability |
| 🔴 **Red** | Poor alignment | 15+ days | Low correlation reliability |

**Why does this matter?**  
Environmental conditions change over time. A field measurement taken on August 10 is more reliable for validating satellite data from August 12 (2 days = green) than from August 1 (9 days = orange).

**Example:**  
If you select satellite date **August 15, 2025** and see:
- Green markers = Data collected Aug 12-18
- Yellow markers = Data collected Aug 8-11 or Aug 19-22
- Orange markers = Data collected Aug 1-7 or Aug 23-29
- Red markers = Data collected before Aug 1 or after Aug 30

---

## Feature 1: Field Data Overlay

### How to Use

1. **Check "Show Field Data"** checkbox
2. Colored markers appear on the map
3. Click any marker to see:
   - Metric name (PM2.5, Temperature, etc.)
   - Measured value with unit
   - Collection date/time
   - GPS accuracy
   - **Temporal alignment** (days from satellite image)

### What You See

**Popup Example:**
```
PM2.5
Value: 25.5 µg/m³
Collected: 2025-08-12 14:30
Accuracy: ±5m

Temporal Alignment: Excellent
2 day(s) from satellite image

🔍 Click to analyze satellite data
```

### Tips

- **Hide data points** by unchecking the checkbox to see satellite imagery clearly
- **Color clustering** indicates temporal data quality patterns
- **Zoom in** for dense data point areas
- **Check the legend** if you forget what colors mean

---

## Feature 2: Sync Mode (Advanced Users)

### What It Does

**Sync Mode** automatically updates the satellite date to match a data point's collection date when you click it.

### When to Use

| Scenario | Sync Mode |
|----------|-----------|
| **Validation/Ground-truthing** | ✅ ON - Compare each field measurement with satellite data from the same day |
| **Exploration** | ❌ OFF - Browse multiple field points while keeping satellite date fixed |
| **Temporal analysis** | ❌ OFF - See how conditions changed over time vs. one satellite snapshot |

### How to Use

**Default Behavior (Sync OFF):**
1. Uncheck "Sync Mode" (default)
2. Click data point from August 10
3. → Map centers on point
4. → Satellite date **stays at August 15** (manual control)

**Advanced Behavior (Sync ON):**
1. Check "Sync Mode" checkbox
2. Click data point from August 10
3. → Map centers on point
4. → Satellite date **changes to August 10** (auto-sync)
5. → Satellite overlay refreshes for new date

### Tips

- **Leave OFF for beginners** - Easier to understand
- **Turn ON for rapid validation** - Quickly compare 20+ field points with their matching satellite dates
- **Sync mode disabled** when "Show Field Data" is unchecked (no points to sync with)

---

## Feature 3: Satellite Data Overlays

### NDVI (Vegetation Index) 🌿

**What It Shows:** Plant health and density

**Color Scale:**
- **Dark Green:** Dense, healthy vegetation (NDVI > 0.6)
- **Light Green:** Moderate vegetation (NDVI 0.3-0.6)
- **Yellow/Brown:** Sparse vegetation (NDVI 0.1-0.3)
- **Gray:** Barren land (NDVI 0-0.1)
- **Blue:** Water (NDVI < 0)

**Use Cases:**
- Forest health monitoring
- Crop assessment
- Urban green space analysis
- Seasonal vegetation changes

**Example:**  
NDVI Value: **0.67** → Dense vegetation (healthy park)

---

### Moisture Index 💧

**What It Shows:** Soil and vegetation water content

**Color Scale:**
- **Dark Blue:** Very wet / Water bodies (NDMI > 0.4)
- **Light Blue:** Wet soil (NDMI 0.2-0.4)
- **Green:** Moderate moisture (NDMI -0.2 to 0.2)
- **Yellow:** Dry soil (NDMI -0.4 to -0.2)
- **Brown:** Very dry (NDMI < -0.4)

**Use Cases:**
- Irrigation planning
- Drought monitoring
- Wetland mapping
- Flood risk assessment

**Example:**  
Moisture Index: **0.15** → Moderate wet conditions

---

### True Color 🌍

**What It Shows:** Natural color satellite photograph

**Use Cases:**
- Visual reference for NDVI/Moisture overlays
- Feature identification (roads, buildings, water)
- Change detection (before/after events)
- General orientation

**Note:** No analysis panel shown for True Color (it's just a photo)

---

## Tooltips & Help Icons ⓘ

Hover over any **ⓘ icon** for contextual help:

| Location | Tooltip |
|----------|---------|
| Campaign Location | "Filter view to specific research campaign" |
| Data Overlay | "Choose satellite visualization type: vegetation health, soil moisture, or natural color" |
| Imagery Date | "Select satellite image acquisition date (cloud-free images may be limited)" |
| Show Field Data | "Overlay manual measurements on satellite imagery" |
| Sync Mode | "Automatically match satellite date to field data collection date when clicking markers" |
| Temporal Alignment | "Shows how close satellite observation is to field measurement (closer = better correlation)" |

---

## Workflow Examples

### Workflow 1: Validate Field Measurements

**Goal:** Check if your field temperature readings match satellite thermal data

1. Select **your campaign** with temperature data points
2. Choose **overlay type:** Moisture (uses thermal bands)
3. Pick **satellite date** close to your field collection dates
4. **Enable "Show Field Data"**
5. **Enable "Sync Mode"** (for auto-date matching)
6. **Click each green marker** (excellent temporal alignment)
7. Compare field value vs. satellite analysis panel
8. Document any significant differences

---

### Workflow 2: Explore Vegetation Changes Over Time

**Goal:** See how park vegetation changed across summer

1. Select **Fælledparken Green Space Study** campaign
2. Choose **overlay type:** NDVI
3. Start with **June 1, 2025**
4. **Disable "Show Field Data"** (focus on satellite only)
5. Change date to **July 1** → **August 1** → **September 1**
6. Observe NDVI color changes (green intensity indicates vegetation growth)
7. Enable field data to see ground-truth measurements

---

### Workflow 3: Assess Data Point Spatial Coverage

**Goal:** Identify areas lacking field measurements

1. Select **your campaign**
2. **Enable "Show Field Data"**
3. Look for **gaps** in marker distribution
4. Note areas with only **red markers** (poor temporal alignment)
5. Plan additional field visits to:
   - Fill spatial gaps
   - Collect data with better temporal alignment

---

## Tips & Best Practices

### Choosing Satellite Dates

✅ **Do:**
- Pick dates with **cloud-free imagery** (system shows available dates)
- Match dates to **field campaign periods**
- Check for **seasonal consistency** (compare summer to summer, not summer to winter)

❌ **Avoid:**
- Dates during heavy cloud cover (no imagery available)
- Comparing wet season field data with dry season satellite data

---

### Interpreting Color Patterns

**Green Cluster** = High-confidence dataset for that area/date  
**Red Cluster** = Consider re-surveying or accepting lower confidence  
**Mixed Colors** = Normal - campaigns span multiple days/weeks  

---

### When Satellite Data Isn't Available

**No overlay appears?** 
- Try nearby dates (Sentinel-2 revisits every 5 days)
- Check cloud coverage (system prefers cloud-free images)
- Some remote areas may have limited coverage

**Fallback:**
- Use **True Color** overlay (more widely available)
- Check NASA/Copernicus websites for manual download

---

## Troubleshooting

### Issue: No Data Points Showing

**Solutions:**
1. Check "Show Field Data" is enabled
2. Verify campaign has data points (select different campaign)
3. Zoom out (points may be outside view)
4. Check browser console (F12) for errors

---

### Issue: Satellite Overlay Not Loading

**Solutions:**
1. Wait 3-5 seconds (loading satellite imagery)
2. Check date has satellite coverage (try ±5 days)
3. Verify Copernicus API credentials in system settings
4. Check "Loading satellite data..." indicator

---

### Issue: Colors Don't Make Sense

**Check:**
1. Is satellite date **much later/earlier** than field data? (expect more red/orange)
2. Did you select the **correct campaign**?
3. Are you in **dark mode**? (colors still visible but different background)

---

## Keyboard Shortcuts

| Key | Action |
|-----|--------|
| **+** / **-** | Zoom in/out |
| **Arrow keys** | Pan map |
| **Esc** | Close popup |
| **F12** | Open browser console (developers) |

---

## Performance & Limitations

### Expected Performance

- **Loading time:** 2-5 seconds for satellite imagery
- **Data points:** Renders 100+ markers smoothly
- **Browser:** Works best in Chrome, Firefox, Edge, Safari

### Known Limitations

1. **Temporal color calculation uses UTC** - May show ±1 day difference for different timezones
2. **Satellite imagery:** Sentinel-2 revisits every 5 days (not daily)
3. **Cloud coverage:** Some dates unavailable due to clouds
4. **Sync mode state:** Resets to OFF on page reload (not saved)

---

## Data Sources

**Satellite Imagery:**
- **Provider:** ESA Copernicus Data Space
- **Satellites:** Sentinel-2A & Sentinel-2B
- **Resolution:** 10 meters per pixel
- **Revisit:** Every 5 days
- **Free:** Unlimited access

**Field Measurements:**
- **Source:** Your campaign data points
- **Collection:** Manual measurements via mobile app or web form
- **Quality:** Validated and approved by campaign managers

---

## Getting Help

**Technical Issues:**
- Check browser console (F12) for error messages
- Screenshot the issue and contact system administrator
- Include: Campaign name, date, browser type

**Feature Requests:**
- Suggest improvements via feedback form
- Review roadmap for upcoming features

**Training:**
- Request live demo from campaign coordinator
- Watch tutorial videos (if available)
- Practice with "Fælledparken Green Space Study" (demo campaign)

---

## Glossary

**NDVI:** Normalized Difference Vegetation Index - measures plant health  
**NDMI:** Normalized Difference Moisture Index - measures water content  
**Temporal Alignment:** How close in time satellite and field data were collected  
**Sync Mode:** Auto-update satellite date when clicking field data markers  
**Centroid:** Geographic center point of a survey zone  
**Ground-truthing:** Validating satellite data with field measurements  

---

## Changelog

**January 13, 2026:**
- Added temporal proximity color-coding
- Added optional sync mode
- Added educational tooltips
- Added temporal alignment legend

**January 12, 2026:**
- Initial release with NDVI and Moisture overlays
- Field data overlay functionality
- Survey zone centering

---

**Questions?** Contact your campaign coordinator or system administrator.

