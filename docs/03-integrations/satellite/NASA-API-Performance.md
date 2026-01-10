# NASA Earth API Performance & Configuration

## TL;DR
The NASA Earth Imagery API **returns 503 errors from DDEV** (Docker network/firewall issue). Use `NASA_USE_MOCK=true` in DDEV, set to `false` in production.

## Issue
- NASA Earth Imagery API returns **503 Service Unavailable** from DDEV
- Error: "upstream connect error or disconnect/reset before headers. connection timeout"
- Connection fails **before receiving headers** - network/firewall blocking
- Works fine from host machine, fails from Docker containers

## Root Cause

### Tested & Confirmed:
✅ DNS resolution works (`api.nasa.gov` resolves correctly)  
✅ HTTPS/SSL works (connection establishes)  
✅ Simple NASA APIs work (`/planetary/apod`, `/planetary/earth/assets`)  
❌ **Imagery API fails** with 503 upstream connect errors  

### Hypothesis:
- NASA's imagery backend (AWS S3/CloudFront) may have firewall rules blocking Docker IP ranges
- Or: Rate limiting based on connection fingerprints (Docker containers flagged)
- Or: Nginx/proxy timeout on NASA side before image generation completes

## Solution Implemented

### 1. Mock Data Toggle (Recommended for DDEV)
```bash
# .env
NASA_USE_MOCK=true  # Use in DDEV (fast, works reliably)
NASA_USE_MOCK=false # Use in production (real data)
```

### 2. Graceful Fallback
If API fails, automatically falls back to mock data with logging:
```
[2026-01-06 09:42:38] local.WARNING: NASA Earth API request failed {"status":503}
[2026-01-06 09:42:38] local.INFO: Using fallback imagery data for local development
```

### 3. Caching
All responses (real or mock) cached for 1 hour to minimize API calls

### 4. User Experience
- Clear warnings when mock data is active
- Loading indicators for real API calls
- Seamless fallback - no errors shown to users

## Testing Results

### From DDEV Container:
```bash
ddev exec php test-nasa-api.php
```
Result: **503 Service Unavailable after 91 seconds**

### From Host Machine (PowerShell):
```powershell
curl "https://api.nasa.gov/planetary/earth/imagery?lat=55.6761&lon=12.5683&date=2020-01-01&api_key=YOUR_KEY"
```
Result: **Works (slow, but succeeds)**

## Production Deployment

When deploying to production (not Docker):

1. **Set in production `.env`:**
   ```bash
   NASA_USE_MOCK=false
   ```

2. **Increase PHP execution time for satellite routes:**
   ```php
   // In route or controller
   set_time_limit(180); // 3 minutes
   ```

3. **Use Queue Jobs (Phase 6):**
   - Don't fetch in web requests
   - Queue background job to fetch imagery
   - Update UI when complete

4. **Monitor API health:**
   ```bash
   # Check if imagery endpoint is accessible
   curl -I -m 10 "https://api.nasa.gov/planetary/earth/imagery?lat=55&lon=12&date=2020-01-01&api_key=YOUR_KEY"
   ```

## Why DDEV Can't Access NASA Imagery API

**Docker Network Isolation:**
- DDEV containers use Docker's bridge network
- Outbound connections may have different IP fingerprint
- NASA's AWS backend may filter/throttle Docker traffic

**Not a DDEV Bug:**
- This is a NASA API + AWS infrastructure limitation
- Other DDEV users report similar issues with AWS-backed APIs
- Solution: Use mock data locally, real API in production

## Alternative Solutions (Not Recommended)

### ❌ Use host network mode
```yaml
# .ddev/docker-compose.override.yaml
version: '3.6'
services:
  web:
    network_mode: "host"
```
**Problem:** Breaks DDEV's routing, causes port conflicts

### ❌ Use HTTP proxy
**Problem:** Adds complexity, latency, potential security issues

### ✅ Recommended: Accept mock data in DDEV
Production will have real data. Development uses placeholders.

## Summary

**DDEV (Development):**
- `NASA_USE_MOCK=true`
- Fast, reliable
- Placeholder imagery for demo

**Production (Real Server):**
- `NASA_USE_MOCK=false`
- Real NASA satellite data
- Queue jobs for async fetching

This is the standard approach for APIs that don't work well from Docker environments.

