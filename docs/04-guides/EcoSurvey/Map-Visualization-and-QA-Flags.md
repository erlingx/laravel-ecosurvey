# Map Visualization and QA Flags Guide

**Last Updated:** January 9, 2026

This guide explains the Survey Map visualization system, including marker colors, cluster colors, and the Quality Assurance (QA) flags system used to identify potential data quality issues.

---

## Table of Contents

1. [Overview](#overview)
2. [Marker Color System](#marker-color-system)
3. [Cluster Color System](#cluster-color-system)
4. [QA Flags Explained](#qa-flags-explained)
5. [Map Legend](#map-legend)
6. [Data Quality Workflow](#data-quality-workflow)

---

## Overview

The Survey Map (`/maps/survey`) provides an interactive visualization of all data points collected across campaigns. The map uses a **color-coded system** to instantly communicate data quality status, helping researchers identify:

- **High-quality approved data** (green)
- **Low GPS accuracy readings** (yellow)
- **Flagged data needing review** (red)
- **Pending/normal data** (blue)

Both individual markers and marker clusters use this color system, with clusters inheriting the "worst" quality indicator from their contained markers.

---

## Marker Color System

Individual data point markers are styled based on three quality indicators (checked in priority order):

### 🔴 **Red Markers - QA Flags Present**

**Condition:** Data point has one or more QA (Quality Assurance) flags

**Visual Style:**
- Fill: Red (`#ef4444`)
- Border: Dark red (`#dc2626`)
- Border style: Dashed (5px dash, 5px gap)
- Opacity: 60%

**What it means:**
- The data has been automatically or manually flagged for potential quality issues
- Examples: outliers, suspicious values, calibration issues, duplicates
- Requires review by a data administrator before approval

**Example QA Flags:**
- `outlier` - Statistically unusual value
- `suspicious_value` - Value outside expected range
- `calibration_overdue` - Sensor needs calibration
- `duplicate_reading` - Potential duplicate data
- `location_uncertainty` - Poor GPS accuracy

---

### 🟡 **Yellow Markers - Low Accuracy**

**Condition:** GPS accuracy > 50 meters AND no QA flags

**Visual Style:**
- Fill: Yellow (`#fbbf24`)
- Border: Orange (`#f59e0b`)
- Border style: Dashed (5px dash, 5px gap)
- Opacity: 50%

**What it means:**
- The GPS location has high uncertainty (>50m radius)
- Data may be usable but location is not precise
- Consider for spatial analysis with caution

**Typical causes:**
- Poor GPS signal (urban canyons, tree cover)
- Mobile device GPS vs. professional equipment
- Indoor or partially obstructed readings

---

### 🟢 **Green Markers - Approved High Quality**

**Condition:** Status = `approved` AND accuracy ≤ 50m AND no QA flags

**Visual Style:**
- Fill: Green (`#10b981`)
- Border: Dark green (`#059669`)
- Border style: Solid
- Opacity: 70%

**What it means:**
- Data has been reviewed and approved
- GPS accuracy is good (≤50m)
- No quality concerns
- **Safe to use for analysis**

---

### 🔵 **Blue Markers - Pending/Normal**

**Condition:** Default state (pending, draft, or not yet reviewed)

**Visual Style:**
- Fill: Blue (`#3b82f6`)
- Border: Dark blue (`#1d4ed8`)
- Border style: Solid
- Opacity: 60%

**What it means:**
- Data is waiting for review
- No quality issues detected yet
- May be promoted to green after review or flagged as red

---

## Cluster Color System

When multiple markers are close together, they're grouped into **clusters** showing the total count. Cluster colors reflect the **highest priority quality concern** among all contained markers.

### Priority Order (Highest to Lowest)

1. **Red** - At least one marker has QA flags
2. **Yellow** - At least one marker has low accuracy (no flags)
3. **Green** - All markers are approved high quality
4. **Blue** - All markers are pending/normal

### Cluster Styling

**Red Cluster:**
```css
background-color: rgba(239, 68, 68, 0.6);
border: 2px solid #dc2626;
```

**Yellow Cluster:**
```css
background-color: rgba(251, 191, 36, 0.6);
border: 2px solid #f59e0b;
```

**Green Cluster:**
```css
background-color: rgba(16, 185, 129, 0.6);
border: 2px solid #059669;
```

**Blue Cluster:**
```css
background-color: rgba(59, 130, 246, 0.6);
border: 2px solid #1d4ed8;
```

### Example Scenarios

**Scenario 1: Mixed Quality Cluster**
- Contains: 5 approved, 2 pending, 1 flagged
- **Result:** Red cluster (flagged takes priority)

**Scenario 2: Low Accuracy Cluster**
- Contains: 8 pending markers, all with >50m accuracy
- **Result:** Yellow cluster (low accuracy, no flags)

**Scenario 3: High Quality Cluster**
- Contains: 10 approved markers, all with <20m accuracy
- **Result:** Green cluster (all high quality)

---

## QA Flags Explained

### What are QA Flags?

QA (Quality Assurance) flags are **warnings** attached to data points that may have quality issues requiring review. They can be:

- **Automatically generated** by the system (e.g., outlier detection)
- **Manually added** by researchers or administrators
- **Multiple per data point** (a reading can have several flags)

### Common QA Flag Types

| Flag Type | Description | Auto-Generated? | Trigger Condition |
|-----------|-------------|-----------------|-------------------|
| `outlier` | Value is statistically unusual compared to expected range | Yes | Deviation > 2.5 units from baseline |
| `suspicious_value` | Value seems unrealistic or outside valid range | Yes | Outside valid range (e.g., temp < -10°C or > 40°C) |
| `calibration_overdue` | Sensor/device hasn't been calibrated recently | Yes | Last calibration > 90 days |
| `duplicate_reading` | Potential duplicate of another data point | Yes* | Same location + time + value (production only) |
| `location_uncertainty` | GPS accuracy is very poor | Yes | GPS accuracy > 80 meters |
| `manual_review` | Flagged by administrator for review | No | Manual action |

**Note:** All QA flags are calculated based on actual data conditions. The seeder demonstrates realistic flagging scenarios based on data quality thresholds.

### How QA Flags Are Calculated

**Automatic Flagging (On Submission):**

When users submit a new reading via the reading form, the following QA flags are automatically calculated:

**Location-Based Flags:**
- `location_uncertainty`: Automatically flagged when GPS accuracy > 80 meters

**Calibration-Based Flags:**
- `calibration_overdue`: Automatically flagged when the last calibration date is >90 days ago
- Users can optionally enter their device's calibration date when submitting readings
- If no calibration date is provided, this flag won't be triggered

**Manual Flagging:**

Administrators can add these flags during data review:

**Temperature Data:**
- `outlier`: Can be flagged when temperature deviates significantly from expected values
- `suspicious_value`: Can be flagged when temperature is unrealistic for the location

**Air Quality Data:**
- `suspicious_value`: Can be flagged when AQI values are outside normal ranges

**All Data Types:**
- `manual_review`: Can be manually added by administrators for any data point requiring human review

### Fields Available in Reading Form

When submitting environmental readings, users can provide:

**Required Fields:**
- Campaign selection
- Metric type
- Reading value
- GPS location (latitude/longitude via device GPS)

**Optional Fields:**
- Notes/observations
- Photo documentation
- **Device/Sensor Model** (e.g., "iPhone 14", "AirQuality Pro 2000")
- **Sensor Type** (GPS, Mobile Device, Professional Equipment, Survey Equipment, Manual Entry)
- **Last Calibration Date** (enables automatic calibration_overdue flagging)

The form automatically:
- Captures GPS accuracy from the device
- Sets status to "pending" for review
- Calculates and applies `location_uncertainty` flag if GPS accuracy > 80m
- Calculates and applies `calibration_overdue` flag if calibration date > 90 days old
- Shows real-time warning if calibration is overdue while filling the form

### Viewing QA Flags

Click on any **red marker** to see its popup. QA flags are displayed as:

```
⚠️ QA Flags (2): Outlier, Suspicious Value
```

This shows:
- **Count:** Number of flags (2)
- **Types:** What kind of flags (Outlier, Suspicious Value)

### QA Flag Structure

In the database, QA flags are stored as JSON arrays:

```json
[
  {
    "type": "outlier",
    "reason": "Value 3.5σ above local mean",
    "flagged_at": "2026-01-09T14:23:45.000Z"
  },
  {
    "type": "suspicious_value",
    "reason": "Temperature reading of 45°C unlikely for location",
    "flagged_at": "2026-01-09T14:23:45.000Z"
  }
]
```

---

## Map Legend

The map includes a visual legend at the bottom showing all marker types:

```
🔴 QA Flags    🟡 Low Accuracy    🟢 Approved    🔵 Pending
```

Each legend item shows:
- The marker color and style
- A short label explaining the meaning

---

## Data Quality Workflow

### Step 1: Data Submission
- User submits data point via mobile app or web form
- Initial status: `pending` (blue marker)
- GPS accuracy is recorded
- No QA flags yet

### Step 2: Automatic QA Checks
- System runs outlier detection
- Checks for duplicates
- Validates sensor calibration dates
- Checks value ranges
- **If issues found:** QA flags added → red marker

### Step 3: Manual Review
Administrator reviews data points:

**For Blue Markers (Pending):**
- Review value, location, accuracy
- Approve → green marker (if accuracy ≤50m)
- Reject → remove from map
- Flag → add QA flag → red marker

**For Yellow Markers (Low Accuracy):**
- Decide if location uncertainty is acceptable
- Approve with caveat → still yellow
- Reject if too uncertain

**For Red Markers (Flagged):**
- Investigate QA flags
- If false positive: remove flags, approve → green
- If valid concern: reject or request resubmission
- If needs info: leave flagged for follow-up

### Step 4: Data Use
- **Green markers:** Safe for all analysis
- **Blue markers:** Can use with caution (pending review)
- **Yellow markers:** Spatial analysis may be affected
- **Red markers:** Do not use until reviewed

---

## Example: Campaign View

**Urban Noise Pollution Study** includes three distinct clusters:

### 1. Valby Parken (Green Cluster)
- **Location:** Southwest Copenhagen (55.6596°N, 12.5107°E)
- **Markers:** 16-24 data points
- **Status:** All approved
- **Accuracy:** 3m to 45m (all <50m)
- **QA Flags:** None
- **Values:** 45-65 dB (moderate park noise)
- **Use Case:** ✅ High-quality baseline for park environments

### 2. Fælledparken (Yellow Cluster)
- **Location:** Northeast Copenhagen (55.7072°N, 12.5704°E)
- **Markers:** 16-24 data points
- **Status:** Pending
- **Accuracy:** 51m to 120m (all >50m)
- **QA Flags:** None
- **Values:** 48-68 dB
- **Use Case:** ⚠️ Usable for general trends, not precise spatial analysis

### 3. High-Traffic Areas (Mixed Clusters)
- **Locations:** Central Station, Nørreport, Østerbro, Tivoli, Vesterbrogade
- **Markers:** ~50 data points
- **Status:** 50% approved, 25% pending, 15% draft, 10% rejected
- **Accuracy:** 3m to 80m (mixed)
- **QA Flags:** ~10% flagged (outliers, location uncertainty)
- **Values:** 40-85 dB (variable urban noise)
- **Use Case:** ⚠️ Requires filtering by quality before analysis

---

## Technical Implementation

### Marker Styling Function

Located in `resources/js/maps/survey-map.js`:

```javascript
export function getMarkerStyle(props) {
    const hasQAFlags = props.qa_flags && props.qa_flags.length > 0;
    const lowAccuracy = props.accuracy && props.accuracy > 50;
    const isApproved = props.status === 'approved';

    if (hasQAFlags) {
        return { /* Red style */ };
    } else if (lowAccuracy) {
        return { /* Yellow style */ };
    } else if (isApproved) {
        return { /* Green style */ };
    } else {
        return { /* Blue style */ };
    }
}
```

### Cluster Icon Function

```javascript
iconCreateFunction: function(cluster) {
    const markers = cluster.getAllChildMarkers();
    
    let flaggedCount = 0;
    let lowAccuracyCount = 0;
    let approvedCount = 0;
    
    markers.forEach(marker => {
        const props = marker.feature?.properties;
        // Count by quality type
    });
    
    // Determine color: flagged > low accuracy > approved > normal
    let colorClass = 'marker-cluster-blue';
    if (flaggedCount > 0) {
        colorClass = 'marker-cluster-red';
    } else if (lowAccuracyCount > 0) {
        colorClass = 'marker-cluster-yellow';
    } else if (approvedCount > 0) {
        colorClass = 'marker-cluster-green';
    }
    
    return L.divIcon({
        html: '<div><span>' + markers.length + '</span></div>',
        className: 'marker-cluster ' + colorClass,
        iconSize: L.point(40, 40)
    });
}
```

### CSS Styles

Located in `resources/css/app.css`:

```css
.marker-cluster-green div {
    background-color: rgba(16, 185, 129, 0.6) !important;
    border: 2px solid #059669 !important;
}

.marker-cluster-yellow div {
    background-color: rgba(251, 191, 36, 0.6) !important;
    border: 2px solid #f59e0b !important;
}

.marker-cluster-red div {
    background-color: rgba(239, 68, 68, 0.6) !important;
    border: 2px solid #dc2626 !important;
}

.marker-cluster-blue div {
    background-color: rgba(59, 130, 246, 0.6) !important;
    border: 2px solid #1d4ed8 !important;
}
```

---

## Best Practices

### For Data Collectors
1. ✅ Use professional GPS equipment when possible (better accuracy)
2. ✅ Wait for GPS lock before recording location
3. ✅ Calibrate sensors according to schedule
4. ✅ Add notes explaining unusual readings
5. ❌ Avoid submitting obvious duplicates

### For Data Reviewers
1. ✅ Review flagged (red) markers first
2. ✅ Check yellow markers for spatial analysis use
3. ✅ Approve high-quality data promptly
4. ✅ Add clear review notes when rejecting
5. ❌ Don't approve data with unresolved QA flags

### For Data Analysts
1. ✅ Filter to green markers for critical analysis
2. ✅ Use blue markers with caution (pending review)
3. ⚠️ Yellow markers: OK for general trends, not precise location
4. ❌ Exclude red markers unless specifically investigating flags
5. ✅ Document quality filters used in analysis

---

## Troubleshooting

### Why is my cluster green but individual markers are blue?

This happens when all markers inside are **approved** even if they show as blue individually. The cluster color prioritizes the best quality status.

**Solution:** This is working as designed. Zoom in to see individual marker colors.

### Why did my approved data point turn yellow?

If a data point has **accuracy > 50m**, it will show as yellow even when approved. High accuracy threshold overrides approval status for visual clarity.

**Solution:** This is correct - it warns that location is uncertain despite approval.

### How do I remove QA flags?

QA flags can only be removed by:
1. Data administrators via the admin panel
2. Automated re-evaluation if underlying issue is fixed

**Solution:** Contact your data administrator or investigate the flag reason.

---

## Related Documentation

- [GIS PostGIS Crash Course](./GIS-PostGIS-Crashcourse.md) - Spatial data handling
- [Data Point Model](../02-architecture/Models.md) - Database schema
- [Geospatial Service](../02-architecture/Services.md) - Backend logic
- [Survey Map Viewer](../03-integrations/Leaflet-Maps.md) - Frontend implementation

---

## Questions?

For technical questions about the map visualization system, contact the development team or review the source code:

- **Frontend:** `resources/js/maps/survey-map.js`
- **Backend:** `app/Services/GeospatialService.php`
- **Model:** `app/Models/DataPoint.php`
- **View:** `resources/views/livewire/maps/survey-map-viewer.blade.php`

