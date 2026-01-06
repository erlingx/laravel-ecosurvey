# Satellite Viewer Debugging Guide

## Testing in Browser

### 1. Open Browser DevTools
- URL: https://laravel-ecosurvey.ddev.site/maps/satellite
- Press F12 to open DevTools
- Go to Console tab

### 2. Initial Load (Default: Fælledparken)
Expected console output:
```
📊 DOM Attributes: {
  revision: "0",
  data-lat: 55.7072,
  data-lon: 12.5704,
  data-overlay-type: "ndvi",
  ...
}
🖼️ Parsed imagery: {
  DOM coords: "55.7072, 12.5704",
  Imagery coords: "55.7072, 12.5704",
  Match: "✓"
}
```

### 3. Select Campaign: "Copenhagen Air Quality 2026"
Expected behavior:
- Badge updates immediately
- Console shows:
  ```
  💾 Satellite viewer message processed (DOM updated)
  🛰️ Updating satellite imagery...
  📊 DOM Attributes: {
    revision: "1" (incremented!),
    data-lat: 55.6478,
    data-lon: 12.5185,
    ...
  }
  🖼️ Parsed imagery: {
    DOM coords: "55.6478, 12.5185",
    Imagery coords: "55.6478, 12.5185",
    Match: "✓"
  }
  ```
- Marker moves to new location
- Overlay updates for new location
- Analysis box shows data for new location

### 4. Change Overlay Type: "Moisture Index"
Expected behavior:
- Console shows:
  ```
  revision: "2" (incremented again)
  data-overlay-type: "moisture"
  ```
- Coordinates stay the same
- Overlay changes to blue/moisture visualization

## Common Issues & Fixes

### ISSUE: DOM shows old revision/coordinates after selection
```
revision: "0" (should be "1")
data-lat: 55.7072 (should be 55.6478)
```
**Cause**: JavaScript reading DOM before Livewire finishes rendering
**Fix**: Changed hook from `commit` to `message.processed` (fires AFTER DOM updates)
**Verify**: Laravel logs show correct coordinates, but browser console shows old ones = timing issue

### ISSUE: Coordinates mismatch warning
```
⚠️ COORDINATE MISMATCH - Imagery is for different location!
  Expected: 55.7072, 12.5704
  Got: 55.6478, 12.5185
```
**Cause**: Cached data from previous location
**Fix**: Check Laravel logs for "Computing satelliteData" - ensure lat/lon match

### ISSUE: Revision not incrementing
**Cause**: updatedXXX hooks not firing
**Fix**: Check that wire:model.live is present on select/input elements

### ISSUE: Map shows old overlay
**Cause**: JavaScript timing - reading stale JSON from data-imagery
**Fix**: Check that revision number matches between DOM and logs

## Laravel Logs to Check

Run: `ddev exec bash -c "tail -50 storage/logs/laravel.log"`

Look for sequence:
```
🎯 Campaign changed {"id":"1"}
✅ Coordinates updated {"lat":55.6478,"lon":12.5185}
🛰️ Computing satelliteData {"lat":55.6478,"lon":12.5185,"updateRevision":1}
✅ Copernicus data loaded {"returned_lat":55.6478,"returned_lon":12.5185}
```

All coordinates should match throughout the sequence!

## Expected Campaign Coordinates

Based on database:
- **Fælledparken Green Space Study** (ID: 4): 55.7072°N, 12.5704°E
- **Copenhagen Air Quality 2026** (ID: 1): 55.6478°N, 12.5185°E  
- **Urban Noise Pollution Study** (ID: 2): 55.6516°N, 12.5236°E

## Test Sequence

1. ✅ Load page → Default Fælledparken (55.7072, 12.5704)
2. ✅ Select Air Quality → Moves to (55.6478, 12.5185)
3. ✅ Select Fælledparken → Moves back to (55.7072, 12.5704)
4. ✅ Change to Moisture → Stays at Fælledparken, changes overlay
5. ✅ Select Noise Study → Moves to (55.6516, 12.5236)
6. ✅ Change to True Color → Stays at Noise Study, changes overlay

If ALL coordinates match between:
- Badge text
- Marker position  
- Overlay location
- Analysis box data
- Console logs
- Laravel logs

Then synchronization is FIXED! ✅

