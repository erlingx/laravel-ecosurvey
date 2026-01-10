# QA/QC System Explained

**Last Updated:** January 9, 2026  
**Related Files:**
- `app/Models/DataPoint.php`
- `resources/views/livewire/data-collection/reading-form.blade.php`
- `database/migrations/2025_12_18_125349_create_data_points_table.php`

---

## What is QA/QC?

**QA/QC** stands for **Quality Assurance / Quality Control** - a systematic approach to ensure environmental data collected through the EcoSurvey platform is:
- **Accurate** (measurements reflect true conditions)
- **Reliable** (consistent across time and collectors)
- **Traceable** (can be verified and audited)
- **Scientifically valid** (suitable for research and analysis)

---

## Why QA/QC Matters for Environmental Monitoring

### Scientific Credibility
Environmental data is used for:
- Research publications
- Policy decisions
- Environmental impact assessments
- Long-term trend analysis
- Citizen science contributions

Poor quality data can lead to:
- ❌ Incorrect conclusions
- ❌ Failed peer review
- ❌ Wasted research effort
- ❌ Poor policy decisions

### Real-World Examples

**Example 1: Air Quality Monitoring**
- **Without QA/QC:** Sensor shows 45 µg/m³ PM2.5, but was never calibrated → actual value might be 30 or 60
- **With QA/QC:** Sensor calibrated 2 weeks ago, GPS accuracy ±8m, notes mention nearby traffic → data is reliable for analysis

**Example 2: Noise Pollution Study**
- **Without QA/QC:** GPS accuracy ±150m, device unknown → "noise at highway" could actually be 150m away in a park
- **With QA/QC:** GPS accuracy ±5m, professional sound meter, calibrated 1 month ago → scientifically valid measurement

---

## QA/QC Fields in EcoSurvey

### Automatic Fields (Captured by System)

| Field | Description | Auto-flagged? | How Captured |
|-------|-------------|---------------|--------------|
| **accuracy** | GPS accuracy in meters | ✅ Yes, if >80m | Browser Geolocation API |
| **collected_at** | Timestamp of data collection | No | System timestamp |
| **status** | Approval status (pending/approved/rejected) | No | Manual review workflow |
| **qa_flags** | Array of quality issues | ✅ Yes, automatic | System calculations |

### User-Entered Fields (Optional but Recommended)

| Field | Description | Why It Matters |
|-------|-------------|----------------|
| **device_model** | Device/sensor model used | Different devices have different accuracy levels |
| **sensor_type** | Type of measurement device | Professional vs. mobile vs. manual entry |
| **calibration_at** | Last calibration date | Uncalibrated sensors drift over time |
| **notes** | Observer notes | Context (weather, nearby activity, anomalies) |
| **photo** | Photo of measurement location | Visual verification of conditions |

---

## QA Flags: Automatic Quality Checks

When data is submitted, the system **automatically checks** for quality issues and assigns **QA flags**.

### Available QA Flags

| Flag Type | When Triggered | Visual Impact | Meaning |
|-----------|----------------|---------------|---------|
| **location_uncertainty** | GPS accuracy >80m | 🔴 Red marker | Location may be unreliable |
| **calibration_overdue** | Last calibration >90 days ago | 🔴 Red marker | Sensor may have drifted |
| **outlier** | Value >3 standard deviations from campaign mean | 🔴 Red marker | Unusually high/low reading |
| **manual_review** | Manually flagged by reviewer | 🔴 Red marker | Needs expert review |

### How Flags Are Set

**Automatic on Submission:**
```php
// From reading-form.blade.php
$qaFlags = [];

// Flag 1: Location uncertainty
if ($this->accuracy && $this->accuracy > 80) {
    $qaFlags[] = 'location_uncertainty';
}

// Flag 2: Calibration overdue
if ($validated['calibrationDate']) {
    $calibrationDate = \Carbon\Carbon::parse($validated['calibrationDate']);
    if ($calibrationDate->diffInDays(now()) > 90) {
        $qaFlags[] = 'calibration_overdue';
    }
}

// Save to database
$dataPoint->qa_flags = $qaFlags;
```

**Automatic by Background Analysis:**
```php
// From DataPoint model
public function flagAsOutlier(string $reason): void
{
    $flags = $this->qa_flags ?? [];
    $flags[] = [
        'type' => 'outlier',
        'reason' => $reason,
        'flagged_at' => now()->toISOString(),
    ];
    $this->qa_flags = $flags;
    $this->save();
}
```

**Manual by Reviewer:**
- Admin users can manually add flags in review interface
- Used for complex quality issues requiring expert judgment

---

## Data Quality Workflow

### 1. Data Collection (Field)
```
User submits reading
↓
System captures GPS accuracy automatically
↓
User enters calibration date (optional)
↓
User adds device model, notes, photo (optional)
```

### 2. Automatic QA Checks (Immediate)
```
System checks GPS accuracy
↓
If accuracy >80m → Add 'location_uncertainty' flag
↓
System checks calibration date
↓
If >90 days old → Add 'calibration_overdue' flag
↓
Data saved with status='pending'
```

### 3. Background Analysis (Within seconds)
```
Queue job: EnrichDataPointWithSatelliteData
↓
Fetch satellite data (NDVI, moisture)
↓
Compare reading to campaign statistics
↓
If outlier detected → Add 'outlier' flag
```

### 4. Manual Review (Admin)
```
Reviewer views flagged data on map (red markers)
↓
Reviews: accuracy, calibration, satellite data, notes, photo
↓
Decision:
  - Approve → status='approved', marker turns green
  - Reject → status='rejected', hidden from public map
  - Flag for expert review → Add 'manual_review' flag
```

---

## Visual Indicators on Survey Map

### Marker Colors Based on Quality

| Marker | Conditions | Meaning |
|--------|-----------|---------|
| 🟢 **Green solid** | `status=approved` AND `accuracy ≤50m` AND no flags | High quality, scientifically valid |
| 🔵 **Blue solid** | `status=pending` AND no flags | Normal quality, awaiting review |
| 🟡 **Yellow dashed** | `accuracy >50m` AND no flags | Low confidence location |
| 🔴 **Red dashed** | Has any `qa_flags` | Quality issue detected, review needed |

### Popup Information

When you click a marker, the popup shows QA/QC metadata:

```
⚠️ QA Flags (if any):
  - Location Uncertainty (±150m GPS accuracy)
  - Calibration Overdue (Last calibrated 120 days ago)

Value: 69.90 dB
Accuracy: ±150m
Location: 55.676098, 12.568337
Campaign: Urban Noise Pollution Study
Submitted by: admin
Date: 2026-01-06 02:20
Status: pending
Device: iPhone 14
Calibration: 2025-08-15 (120 days ago)
Notes: Measurement taken near busy intersection during rush hour
Photo: [thumbnail]
```

---

## GPS Accuracy: The Most Important QA Field

### Why GPS Accuracy Matters Most

GPS accuracy determines the **spatial reliability** of environmental measurements. If you measure air quality at a location with ±150m uncertainty, the reading could be from:
- A busy highway (high pollution)
- A nearby park (low pollution)
- An industrial area (very high pollution)

**Scientific Standard:**
- ✅ **<10m:** Excellent for scientific analysis
- ✅ **10-50m:** Good for most environmental monitoring
- ⚠️ **50-80m:** Acceptable with caveats
- ❌ **>80m:** Too uncertain for reliable spatial analysis (flagged)

### How GPS Accuracy is Determined

GPS accuracy is **NOT a user input** - it's automatically provided by:

1. **GPS Satellites** (primary source)
2. **WiFi Network Triangulation** (improves accuracy)
3. **Cell Tower Triangulation** (fallback when GPS unavailable)
4. **Device Quality** (professional GPS vs. smartphone)

**Typical Values:**
- Professional GPS device outdoors: **1-5m**
- Smartphone outdoors with clear sky: **5-20m**
- Smartphone in urban area: **20-50m**
- Smartphone indoors: **50-200m**

**Manual Entry Exception:**
When users manually enter coordinates (e.g., from surveyed locations), accuracy is set to **0m** (scientific best practice for exact/surveyed locations).

---

## Calibration: Why It Matters

Environmental sensors **drift over time** - their readings become less accurate even if the device appears to work normally.

### Calibration Drift Examples

**Air Quality Sensor (90 days without calibration):**
- True value: 35 µg/m³ PM2.5
- Sensor shows: 42 µg/m³ (20% error)
- Result: False alarm about air quality

**Noise Level Meter (180 days without calibration):**
- True value: 65 dB
- Sensor shows: 58 dB (7 dB error)
- Result: Noise violation goes undetected

### Calibration Standards

| Sensor Type | Recommended Calibration Frequency |
|-------------|----------------------------------|
| Professional environmental sensors | Every 30 days |
| Consumer air quality monitors | Every 90 days |
| Noise level meters | Every 90 days |
| Temperature/humidity sensors | Every 180 days |
| Mobile phone sensors | Not calibratable (use for trends only) |

**EcoSurvey Threshold:**
- ✅ **<90 days:** Acceptable
- ⚠️ **>90 days:** Triggers `calibration_overdue` flag

---

## Best Practices for Data Collectors

### To Ensure High Quality Data:

1. **Use GPS outdoors when possible**
   - Move to open area with clear sky view
   - Wait for GPS accuracy <20m before submitting

2. **Enter device information**
   - Record device model and sensor type
   - Helps researchers assess data reliability

3. **Track calibration dates**
   - Enter last calibration date if known
   - Calibrate professional sensors regularly

4. **Add contextual notes**
   - Weather conditions
   - Nearby activity (traffic, construction, events)
   - Any anomalies or concerns

5. **Take photos**
   - Visual record of measurement conditions
   - Helps reviewers verify data quality

6. **Review before submitting**
   - Check GPS accuracy shown on form
   - Verify measurement value is reasonable
   - Add notes if anything seems unusual

---

## Best Practices for Data Reviewers

### When Reviewing Flagged Data:

1. **Check GPS accuracy**
   - <50m: Generally reliable
   - 50-80m: Use with caution, check notes/photo
   - >80m: Consider rejecting unless context justifies

2. **Check calibration status**
   - <90 days: Good
   - 90-180 days: Acceptable for trends
   - >180 days: Likely unreliable, reject unless professional equipment

3. **Check for outliers**
   - Compare to nearby measurements
   - Check satellite data correlation
   - Look for notes explaining unusual value

4. **Check device type**
   - Professional equipment: High confidence
   - Consumer sensors: Medium confidence
   - Mobile phone sensors: Low confidence (trends only)

5. **Look at photo and notes**
   - Does photo match reported conditions?
   - Do notes explain any anomalies?
   - Are there environmental factors affecting measurement?

---

## QA/QC Data Schema

### Database Structure

```sql
-- data_points table
qa_flags          JSON         -- Array of flag objects
accuracy          FLOAT        -- GPS accuracy in meters
device_model      VARCHAR      -- Device/sensor model
sensor_type       VARCHAR      -- Type of sensor
calibration_at    TIMESTAMP    -- Last calibration date
notes             TEXT         -- User observations
photo_path        VARCHAR      -- Photo storage path
status            ENUM         -- pending/approved/rejected
```

### QA Flags JSON Format

```json
[
  "location_uncertainty",
  "calibration_overdue"
]

// OR with detailed info (for outliers)
[
  {
    "type": "outlier",
    "reason": "Value 95.3 is 3.2 std deviations above campaign mean of 68.5",
    "flagged_at": "2026-01-09T14:23:45.000Z"
  }
]
```

---

## Testing QA/QC Features

### Test Scenario 1: Location Uncertainty Flag

```php
// Submit reading with poor GPS accuracy
DataPoint::create([
    'accuracy' => 150,  // >80m threshold
    // ... other fields
]);

// Result: qa_flags = ['location_uncertainty']
// Map shows: 🔴 Red dashed marker
```

### Test Scenario 2: Calibration Overdue Flag

```php
// Submit reading with old calibration
DataPoint::create([
    'calibration_at' => now()->subDays(120),  // >90 days ago
    // ... other fields
]);

// Result: qa_flags = ['calibration_overdue']
// Map shows: 🔴 Red dashed marker
```

### Test Scenario 3: High Quality Data

```php
// Submit reading with excellent QA/QC
DataPoint::create([
    'accuracy' => 8,  // <50m
    'calibration_at' => now()->subDays(15),  // Recent
    'device_model' => 'Professional Air Quality Monitor AQ500',
    'status' => 'approved',
    // ... other fields
]);

// Result: qa_flags = []
// Map shows: 🟢 Green solid marker (when approved)
```

---

## Reporting and Analytics

### QA/QC Metrics Available

1. **Campaign Quality Dashboard** (future feature)
   - % of data points with flags
   - Average GPS accuracy
   - Calibration compliance rate
   - Approval/rejection rates

2. **Spatial Quality Heatmap** (future feature)
   - Areas with high/low quality data
   - GPS accuracy distribution map

3. **Temporal Quality Trends** (future feature)
   - Quality metrics over time
   - Calibration drift patterns

---

## Compliance and Standards

EcoSurvey QA/QC follows these standards:

- **GPS Accuracy:** EPA guidance for spatial data quality
- **Calibration:** ISO environmental sensor standards
- **Outlier Detection:** Statistical methods (>3 σ from mean)
- **Data Review:** Citizen science quality assurance protocols

---

## Future QA/QC Enhancements

1. **Multi-sample averaging:** Take 3-5 GPS readings, average for better accuracy
2. **Real-time validation:** Warn user before submission if accuracy poor
3. **Inter-sensor comparison:** Flag data if nearby sensors show very different values
4. **Machine learning QA:** Auto-detect patterns indicating sensor malfunction
5. **Chain of custody:** Track who collected, reviewed, approved each data point

---

## Summary

**QA/QC in EcoSurvey ensures:**
- ✅ GPS accuracy tracked and flagged automatically
- ✅ Calibration status monitored
- ✅ Outliers detected by statistical analysis
- ✅ Manual review workflow for flagged data
- ✅ Visual indicators (colored markers) show quality at a glance
- ✅ Complete metadata for audit trail

**Result:** Scientifically valid environmental data suitable for research and policy decisions.

---

## Quick Reference

| If you want to... | Look at... |
|------------------|------------|
| Understand GPS accuracy | `docs/04-guides/GPS-Accuracy-How-It-Works.md` |
| See QA flag types | This document, "QA Flags" section |
| Test QA/QC workflow | `docs/05-testing/UX-Testing-Priority-0-1.md`, Test Suite 2 |
| Review data quality | Survey Map → Click red markers → Check QA flags |
| Submit high quality data | Submit Reading form → Fill all QA/QC fields |

---

**Last Updated:** January 9, 2026

