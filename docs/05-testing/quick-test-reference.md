# Quick Test Reference Guide

## Running Tests Incrementally

### By Test Suite (Fastest)
```bash
# Services tests (~50-130 seconds)
ddev artisan test tests/Feature/Services --compact

# Maps tests (~48 seconds)
ddev artisan test tests/Feature/Maps --compact

# Model tests (~45 seconds)
ddev artisan test tests/Feature/Models --compact

# Jobs tests (~13 seconds)
ddev artisan test tests/Feature/Jobs --compact

# Data Collection tests (~25 seconds)
ddev artisan test tests/Feature/DataCollection --compact

# Integration tests (~5 seconds)
ddev artisan test tests/Feature/Integration --compact

# Campaign tests (~3 seconds)
ddev artisan test tests/Feature/Campaigns --compact
```

### By Specific Test File (Very Fast)
```bash
# Analytics Service (~10 seconds)
ddev artisan test tests/Feature/Services/AnalyticsServiceTest.php --compact

# Geospatial Service (~25 seconds)
ddev artisan test tests/Feature/Services/GeospatialServiceTest.php --compact

# Quality Check Service (~130 seconds)
ddev artisan test tests/Feature/Services/QualityCheckServiceTest.php --compact

# Satellite Viewer (~48 seconds)
ddev artisan test tests/Feature/Maps/SatelliteViewerTest.php --compact

# Reading Form (~25 seconds)
ddev artisan test tests/Feature/DataCollection/ReadingFormTest.php --compact
```

### By Filter (Targeted)
```bash
# Run specific test by name
ddev artisan test --filter="heatmap data" --compact

# Run all tests containing "satellite"
ddev artisan test --filter=satellite --compact

# Run all tests containing "quality"
ddev artisan test --filter=quality --compact
```

### Full Test Suite
```bash
# Run all tests (slow - several minutes)
ddev artisan test --compact

# Run all tests with detailed output
ddev artisan test

# Run specific test and stop on first failure
ddev artisan test tests/Feature/Services --stop-on-failure
```

## Test Categories

### ✅ Fast Tests (< 15 seconds)
- Jobs tests
- Integration tests
- Campaign tests
- Individual service tests

### ⏱️ Medium Tests (15-60 seconds)
- Maps tests
- Model tests
- Data Collection tests
- Most Services tests

### 🐌 Slow Tests (> 60 seconds)
- QualityCheckServiceTest (~130s due to statistical calculations)
- Full Services suite (~300s total)
- Complete test suite (~500s+ total)

## Recommended Workflow

### During Development
```bash
# 1. Run tests for the specific file you're working on
ddev artisan test tests/Feature/Services/AnalyticsServiceTest.php --compact

# 2. If passing, run related tests
ddev artisan test tests/Feature/Services --compact

# 3. Before committing, run full suite
ddev artisan test --compact
```

### After Pull/Merge
```bash
# Quick smoke test (fast categories only)
ddev artisan test tests/Feature/Jobs --compact
ddev artisan test tests/Feature/Integration --compact

# If passing, run full suite
ddev artisan test --compact
```

### Before Deploy
```bash
# Always run full test suite
ddev artisan test --compact

# Check for no failures
# ✅ Expected: "Tests: X passed, Duration: XXs"
```

## Test Performance Tips

### ✅ Tests No Longer Hang
All DataPoint-related tests now use `Queue::fake()` to prevent automatic satellite enrichment jobs from running. Tests complete reliably without API timeouts.

### Files Protected (17 total)
- All Services tests
- All Maps tests  
- All Model tests
- All Jobs tests
- All Data Collection tests
- All Integration tests

### What Changed
Before fix:
- Tests hung on DataPoint creation (waiting for satellite API calls)
- Unreliable test execution
- Timeouts and failures

After fix:
- `Queue::fake()` prevents job execution during tests
- Fast, reliable test execution
- No API calls during testing

## Troubleshooting

### Test Hangs
If a test hangs, check if it creates DataPoints and has `Queue::fake()` in beforeEach.

### Test Fails on CI
Ensure `QUEUE_CONNECTION=sync` is set in phpunit.xml (already configured).

### Slow Tests
Use `--filter` to run only relevant tests during development. Run full suite before committing.

### Database Issues
Tests use `RefreshDatabase` trait - database is automatically reset between tests.

---
Last Updated: January 19, 2026
See also: `test-hang-fix-summary.md` for detailed fix information
