# GPS Accuracy - How It Works

**Last Updated:** January 9, 2026  
**Related Files:**
- `resources/views/livewire/data-collection/reading-form.blade.php`
- `app/Models/DataPoint.php`
- `database/migrations/2025_12_18_125349_create_data_points_table.php`

---

## Overview

GPS accuracy in the EcoSurvey application is **automatically captured** from the device's GPS system when users submit environmental readings. It is **not a manual input field**.

---

## How GPS Accuracy is Captured

### 1. User Clicks "Capture GPS" Button

In the reading submission form, users click the **"📍 Capture GPS"** button to get their current location.

### 2. Browser Geolocation API

The application uses the browser's native **Geolocation API**:

```javascript
navigator.geolocation.getCurrentPosition(
    (position) => {
        @this.set('latitude', position.coords.latitude);
        @this.set('longitude', position.coords.longitude);
        @this.set('accuracy', position.coords.accuracy); // ← GPS accuracy in meters
        @this.set('gpsStatus', 'success');
    },
    (error) => {
        // Handle errors (permission denied, timeout, etc.)
    },
    {
        enableHighAccuracy: true,  // Request best possible accuracy
        timeout: 10000,            // 10 second timeout
        maximumAge: 0              // Don't use cached position
    }
);
```

### 3. What is `position.coords.accuracy`?

The `accuracy` property is provided by the device's operating system and represents the **radius of uncertainty** around the GPS coordinates, measured in **meters**.

**Example:**
- If `accuracy = 25`, the device is confident the true location is within a 25-meter radius of the reported coordinates.

---

## Typical GPS Accuracy Values

GPS accuracy varies based on:
- **Device type** (phone, professional GPS unit, smartwatch)
- **Environment** (indoors, outdoors, urban canyon, open field)
- **GPS signal quality** (number of satellites visible)
- **Assisted GPS** (WiFi, cell tower triangulation)

### Common Scenarios

| Scenario | Typical Accuracy | Marker Color |
|----------|------------------|--------------|
| **Professional GPS device outdoors** | 1-5m | 🔵 Blue (high quality) |
| **Smartphone outdoors, clear sky** | 5-20m | 🔵 Blue or 🟡 Yellow |
| **Smartphone outdoors, urban area** | 10-50m | 🟡 Yellow (low confidence) |
| **Smartphone indoors** | 50-200m | 🟡 Yellow (low confidence) |
| **Poor GPS signal** | 100-500m | 🟡 Yellow (low confidence) |

---

## How Accuracy Affects Data Quality

### Marker Color Classification

The survey map uses GPS accuracy to color-code data points:

| Condition | Marker Color | Meaning |
|-----------|--------------|---------|
| `accuracy ≤ 50m` AND `status = approved` | 🟢 Green solid | High quality, approved data |
| `accuracy > 50m` | 🟡 Yellow dashed | Low confidence location |
| `qa_flags` present | 🔴 Red dashed | Data flagged for review |
| `status = pending` (default) | 🔵 Blue solid | Normal, awaiting review |

### Automatic QA Flags

When a data point is submitted, the system automatically checks accuracy:

```php
// Auto-flag based on data quality
$qaFlags = [];

// Flag location uncertainty if GPS accuracy is poor (>80m)
if ($this->accuracy && $this->accuracy > 80) {
    $qaFlags[] = 'location_uncertainty';
}
```

**QA Flag Rules:**
- **`location_uncertainty`** flag is added if `accuracy > 80m`
- This flag causes the marker to display as **🔴 red dashed**
- The popup shows: "⚠️ QA Flags: Location Uncertainty"

---

## Database Storage

GPS accuracy is stored in the `data_points` table:

```php
// Migration field
$table->float('accuracy')->nullable()->comment('GPS accuracy in meters');

// Example data
DataPoint::create([
    'latitude' => 55.6761,
    'longitude' => 12.5683,
    'accuracy' => 25.5,  // ← Captured from device GPS
    // ...
]);
```

---

## User Experience Flow

1. **User navigates to Submit Reading form** (`/data-collection/submit`)
2. **User clicks "📍 Capture GPS"**
3. **Browser requests location permission** (if not already granted)
4. **Device calculates position:**
   - Uses GPS satellites
   - Uses WiFi network triangulation (if available)
   - Uses cell tower triangulation (if available)
5. **Browser returns:**
   - `latitude`: e.g., `55.676098`
   - `longitude`: e.g., `12.568337`
   - `accuracy`: e.g., `25.4` (meters)
6. **Form displays:** "📍 55.676098, 12.568337 (±25m accuracy)"
7. **User fills remaining fields and submits**
8. **Backend saves accuracy value** along with coordinates
9. **Map displays marker** with color based on accuracy

---

## Testing GPS Accuracy

### Simulating Different Accuracy Values

Since accuracy is auto-captured from the device, you can test different scenarios:

**Method 1: Physical location changes**
- **Outdoors with clear sky view:** Expect 5-20m accuracy
- **Indoors:** Expect 50-200m accuracy
- **Urban areas with tall buildings:** Expect 20-100m accuracy

**Method 2: Database seeding (for testing only)**
```php
// In database seeder
DataPoint::create([
    'accuracy' => 5,    // Excellent GPS (blue marker)
    // ...
]);

DataPoint::create([
    'accuracy' => 75,   // Poor GPS (yellow marker)
    // ...
]);

DataPoint::create([
    'accuracy' => 150,  // Very poor GPS (red marker + location_uncertainty flag)
    // ...
]);
```

**Method 3: Browser DevTools (desktop only)**
- Open Chrome DevTools → Console
- Override GPS accuracy:
```javascript
navigator.geolocation.getCurrentPosition = function(success) {
    success({
        coords: {
            latitude: 55.6761,
            longitude: 12.5683,
            accuracy: 100  // ← Set custom accuracy for testing
        }
    });
};
```

---

## Why Accuracy Matters for Environmental Monitoring

### Scientific Validity
- **Spatial analysis accuracy:** If measuring air quality at a specific location, ±5m is much better than ±100m
- **Correlation with satellite data:** Satellite pixels are typically 10-30m resolution; GPS accuracy should ideally be <50m for correlation
- **Clustering analysis:** Poor GPS accuracy can cause data points to appear in wrong locations

### Use Case Examples

**Good Use Case (Accuracy = 10m):**
- Measuring water quality at a specific stream location
- Can confidently correlate with satellite imagery (10-30m resolution)
- Suitable for scientific analysis

**Poor Use Case (Accuracy = 150m):**
- Measuring noise pollution near a highway
- True location could be 150m away (different environment entirely)
- Flagged for manual review before use in analysis

---

## Related QA/QC Fields

Besides GPS accuracy, the reading form captures other quality metadata:

| Field | Purpose | Auto-flagged? |
|-------|---------|---------------|
| `accuracy` | GPS accuracy in meters | Yes, if >80m → `location_uncertainty` |
| `calibration_at` | Last sensor calibration date | Yes, if >90 days ago → `calibration_overdue` |
| `device_model` | Device/sensor model used | No (informational only) |
| `sensor_type` | Type of measurement device | No (informational only) |
| `notes` | User observations | No (informational only) |

---

## Troubleshooting

### "GPS not available" error
- **Cause:** User denied location permission or device has no GPS
- **Solution:** Guide user to enable location services in browser settings

### Very high accuracy values (>500m)
- **Cause:** Indoor location with no GPS signal, only cell tower triangulation
- **Solution:** Ask user to move outdoors or note in form that location is approximate

### Accuracy always shows null
- **Cause:** Browser or device doesn't support `position.coords.accuracy`
- **Solution:** Default to manual coordinate entry or mark as "Unknown accuracy"

---

## Future Enhancements

Potential improvements to GPS accuracy handling:

1. **Multiple GPS readings:** Take 3-5 readings and average them for better accuracy
2. **Show accuracy warning:** Display red warning if accuracy >100m before submission
3. **Accuracy threshold setting:** Allow campaign managers to set minimum accuracy requirements per campaign
4. **Accuracy history chart:** Show GPS accuracy distribution for a campaign to identify issues
5. **Professional GPS device integration:** Support Bluetooth GPS devices with <1m accuracy

---

## References

- [MDN Web Docs: Geolocation API](https://developer.mozilla.org/en-US/docs/Web/API/Geolocation_API)
- [W3C Geolocation API Specification](https://www.w3.org/TR/geolocation/)
- GPS Accuracy Basics: Understanding positional accuracy in civilian GPS

---

**Summary:**
- GPS accuracy is **automatically captured** from device GPS
- Values typically range from **5m (professional) to 200m (mobile indoors)**
- Affects data point **marker color** on survey map
- Triggers **`location_uncertainty` flag** if >80m
- Critical for **scientific validity** of environmental measurements

