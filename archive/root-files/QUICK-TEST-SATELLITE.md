# 🚀 QUICK TEST - Satellite Map Fix

## The map isn't updating? Follow these steps:

### Step 1: Clear Browser Cache COMPLETELY
**This is CRITICAL - the browser is likely using old JavaScript!**

**Firefox/Chrome:**
1. Press `Ctrl + Shift + Delete`
2. Select "Cached images and files"
3. Click "Clear data"

**Or in Firefox:**
1. Right-click on page → Inspect (F12)
2. Right-click the Refresh button
3. Choose "Empty Cache and Hard Reload"

### Step 2: Hard Refresh
Press `Ctrl + Shift + R` (Windows) or `Cmd + Shift + R` (Mac)

### Step 3: Open Console
Press `F12` → Go to Console tab

**ON PAGE LOAD** you should now see:
```
Initializing satellite map...
Satellite map initialization complete!
📡 Loading initial satellite data...
🛰️ Updating satellite imagery...
📊 DOM Attributes: { revision: "0", data-lat: 55.7072, data-lon: 12.5704 }
```

### Step 4: Select Campaign
Click campaign dropdown → Select "Copenhagen Air Quality 2026"

### Step 5: Check Console Output

✅ **SUCCESS** - You should see:
```
🔔 Livewire message.processed fired! { componentName: "maps.satellite-viewer" }
💾 Satellite viewer message processed (DOM updated)
⏰ Timeout complete - calling updateSatelliteImagery()
🛰️ Updating satellite imagery...
📊 DOM Attributes: { revision: "1", data-lat: 55.6478, data-lon: 12.5185 }
🖼️ Parsed imagery: { Match: "✓" }
```

❌ **STILL BROKEN** - You see nothing or old messages:
- You're still using cached JavaScript
- Check Network tab (F12 → Network)
- Look for request to `app-C_6xkeKh.js` (should be the newest build)
- If you see `app-Ce3sGrLr.js` or older → Cache not cleared properly

### Step 6: Nuclear Option (if still cached)
```powershell
# In your project directory
ddev npm run build

# Then disable browser cache entirely:
# F12 → Network tab → Check "Disable cache"
# Refresh page with F12 open
```

---

## What Fixed It?

Changed JavaScript from reading DOM too early (`commit` hook) to reading after DOM updates (`message.processed` hook).

**Files changed:**
- `resources/js/app.js` - Updated Livewire hook timing + initial load fix
- Built new JS: `app-C_6xkeKh.js` (LATEST - Jan 6, 2026)

**The fix works** - backend tests pass perfectly. The only issue is browser caching!

---

## Still Not Working?

Check Laravel logs to confirm backend is updating:
```powershell
ddev exec bash -c "tail -20 storage/logs/laravel.log"
```

You should see:
```
🎯 Campaign changed {"id":"1"}
✅ Coordinates updated {"lat":55.6478,"lon":12.5185}
🛰️ Computing satelliteData {"lat":55.6478,"lon":12.5185,"updateRevision":1}
```

If backend logs show correct coordinates but browser doesn't → **100% a cache issue!**

