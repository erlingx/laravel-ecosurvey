# Phase 9 Features - Browser Testing Cookbook

**Last Updated:** January 19, 2026  
**Estimated Time:** 10-12 minutes  
**Prerequisites:** Logged in as admin user, data points with various quality levels exist

**Testing Status:** ⏳ PENDING TESTING

**Features to Test:**
- ⏳ Quality Dashboard page
- ⏳ QA Statistics widgets
- ⏳ User Contribution Leaderboard widget
- ⏳ API Usage Tracker widget
- ⏳ QA Flags column in data points table
- ⏳ QA Status filter
- ⏳ Bulk clear flags action
- ⏳ Automated quality check command
- ⏳ Expected ranges on environmental metrics

---

## Testing Notes

**Phase 9 Features to Test:**
1. Quality Dashboard with all widgets
2. QA Statistics Widget (already existed from Phase 8)
3. User Contribution Leaderboard
4. API Usage Tracker
5. QA Flags display on data points
6. Quality check automation
7. Expected ranges validation

**Prerequisites:**
- Admin access to `/admin`
- Multiple users with data submissions
- Data points with varying GPS accuracy
- Some data points with satellite analyses
- Environmental metrics configured

**Key Features in Phase 9:**
- ✅ Automated quality checks (GPS threshold, outliers, range validation)
- ✅ User contribution leaderboard with medals
- ✅ API usage tracking for satellite calls
- ✅ QA flags on data points
- ✅ Bulk operations for QA flags
- ✅ Quality Dashboard page

---

## Quick Test Checklist

- [ ] **Quality Dashboard Page** (3 min)
- [ ] **QA Statistics Widget** (2 min)
- [ ] **User Contribution Leaderboard** (2 min)
- [ ] **API Usage Tracker** (2 min)
- [ ] **QA Flags Display** (2 min)
- [ ] **Automated Quality Checks** (3 min)

---

## 1. Quality Dashboard Page (3 minutes)

### Test: Access Quality Dashboard

**URL:** `/admin/quality-dashboard`

**Steps:**
1. Navigate to admin panel
2. Click "Quality Dashboard" in navigation (Data Quality group)
3. Review page layout

**Expected Results:**
✅ Quality Dashboard page loads successfully  
✅ Located in "Data Quality" navigation group  
✅ Shield with check icon (heroicon-o-shield-check)  
✅ Navigation sort order: 2 (after "Review Data Points")  
✅ Page heading: "Quality Dashboard"  
✅ Three widget sections visible  
✅ No JavaScript errors

---

### Test: Dashboard Layout

**Expected Sections:**

**Header Section:**
✅ Page title: "Quality Assurance Dashboard"  
✅ Description: "Monitor data quality metrics, user contributions, and API usage in real-time."

**Widget Section 1 - QA Statistics:**
✅ 6 statistics cards displayed (from Phase 8)
✅ Grid layout, responsive
✅ Real-time data

**Widget Section 2 - User Contribution Leaderboard:**
✅ Top 5 contributors displayed
✅ Medal icons visible (🥇🥈🥉)
✅ Statistics per user

**Widget Section 3 - API Usage Tracker:**
✅ 3 statistics cards displayed
✅ Satellite API usage metrics
✅ Cache performance stats

**Command Reference Section:**
✅ Gray/dark background section  
✅ Heading: "Automated Quality Check Commands"  
✅ Two command code blocks visible:
```bash
php artisan ecosurvey:quality-check --flag-suspicious
php artisan ecosurvey:quality-check --auto-approve
```

---

## 2. QA Statistics Widget (2 minutes)

### Test: Widget Display

**This widget existed in Phase 8 but verify it still works:**

**Expected Statistics (6 cards):**

**1. Pending Review:**
✅ Count of pending data points  
✅ Yellow/warning color  
✅ 7-day trend chart  
✅ Clock icon

**2. Approved:**
✅ Count of approved data points  
✅ Green/success color  
✅ Shows approval rate percentage  
✅ Check circle icon

**3. Rejected:**
✅ Count of rejected data points  
✅ Red/danger color  
✅ X circle icon

**4. Active Campaigns:**
✅ Count of active campaigns  
✅ Blue/info color  
✅ Map icon

**5. Total Data Points:**
✅ Count of all data points  
✅ Blue/primary color  
✅ Clipboard icon

**6. Active Users:**
✅ Count of registered users  
✅ Green/success color  
✅ Users icon

**Validation:**
- Widget sort order: 1 (displays first)
- All stats accurate
- Colors correct
- Icons display properly

---

## 3. User Contribution Leaderboard (2 minutes)

### Test: Leaderboard Widget Display

**Expected Layout:**

**Widget Header:**
✅ Title: "User Contribution Leaderboard"  
✅ Widget sort order: 2 (displays after QA Stats)  
✅ Full width (columnSpan = 'full')

**Top 5 Contributors:**

**#1 - Gold Medal (🥇):**
✅ Medal emoji visible: 🥇  
✅ User name displayed  
✅ Main stat: "{X} submissions"  
✅ Description: "{Y}% approved | {Z}m avg accuracy"  
✅ Yellow/warning color (gold)  
✅ Check badge icon (heroicon-o-check-badge)

**#2 - Silver Medal (🥈):**
✅ Medal emoji visible: 🥈  
✅ User name displayed  
✅ Submission count  
✅ Approval % and avg accuracy  
✅ Gray color (silver)  
✅ Check badge icon

**#3 - Bronze Medal (🥉):**
✅ Medal emoji visible: 🥉  
✅ User name displayed  
✅ Submission count  
✅ Approval % and avg accuracy  
✅ Red/danger color (bronze)  
✅ Check badge icon

**#4 and #5:**
✅ Rank number: "#4" and "#5"  
✅ User name displayed  
✅ Submission count  
✅ Approval % and avg accuracy  
✅ Blue/primary color  
✅ Check badge icon

---

### Test: Empty State

**Steps:**
1. Clear all data points (or test on fresh database)
2. View Quality Dashboard

**Expected Results:**
✅ Single card displays:
- Title: "No Data"
- Value: "No user contributions in the last 30 days"
- Description: "Start collecting data to see leaderboard"
- Gray color
- Information icon (heroicon-o-information-circle)

---

### Test: Leaderboard Data Accuracy

**Validation Points:**

**For Each User:**
✅ `total_submissions`: Accurate count of all submissions in last 30 days  
✅ `approval_rate`: Correctly calculated: (approved / total) × 100, rounded to 1 decimal  
✅ `avg_accuracy`: Average GPS accuracy in meters, rounded to 2 decimals  
✅ Users sorted by total_submissions descending (most submissions first)

**Example:**
```
🥇 John Doe - 45 submissions
   95.6% approved | 7.23m avg accuracy
```

**Verify:**
- User has 45 data points created in last 30 days ✅
- 43 approved, 2 rejected: 43/45 = 95.6% ✅
- GPS accuracy values average to 7.23m ✅

---

### Test: Time Range

**Steps:**
1. Create data point 31 days ago
2. Create data point today
3. Check leaderboard

**Expected Results:**
✅ Data point from 31 days ago NOT counted  
✅ Data point from today IS counted  
✅ Leaderboard shows last 30 days only

---

## 4. API Usage Tracker (2 minutes)

### Test: Widget Display

**Expected Statistics (3 cards):**

**1. Satellite API Calls (Today):**
✅ Title: "Satellite API Calls (Today)"  
✅ Value: Count of satellite analyses created today  
✅ Description: "{X} this month"  
✅ Green/success color  
✅ Globe icon (heroicon-o-globe-alt)  
✅ 7-day trend chart showing daily API call counts

**2. Cache Hit Rate:**
✅ Title: "Cache Hit Rate"  
✅ Value: Percentage (e.g., "87.5%")  
✅ Description: "{hits} hits / {misses} misses today"  
✅ Color: Green if >80%, Yellow if ≤80%  
✅ Server stack icon (heroicon-o-server-stack)

**3. Avg Indices per Analysis:**
✅ Title: "Avg Indices per Analysis"  
✅ Value: Average count (e.g., "5.2")  
✅ Description: "Out of 7 available indices"  
✅ Blue/info color  
✅ Chart bar icon (heroicon-o-chart-bar)

**Widget Properties:**
✅ Widget sort order: 3 (displays after leaderboard)  
✅ Standard width (not full)

---

### Test: API Call Tracking

**Steps:**
1. Note current "Today" count on Quality Dashboard
2. **View a satellite overlay** on the map viewer
   - Go to `/maps/satellite`
   - Select a campaign
   - Choose a date
   - Select an overlay type (NDVI, Moisture, etc.)
   - Wait for overlay to load
3. Refresh Quality Dashboard
4. Check counts

**Expected Results:**
✅ "Today" count increases by 1  
✅ "This month" count increases by 1  
✅ 7-day trend chart updates  
✅ Call Type Breakdown shows "Overlay: 1"

**Alternative: Create DataPoint (triggers enrichment):**
```bash
ddev artisan tinker
>>> $dp = App\Models\DataPoint::factory()->create();
>>> exit
# Wait 5-10 seconds for queue to process
# Then refresh dashboard - should see "Enrichment: 1"
```

**What Gets Tracked:**
✅ **Overlay views** (map viewer) - 0.5 credits each  
✅ **Data point enrichment** (background job) - 1.0 credits each  
✅ **Single index analysis** - 0.75 credits each  
✅ **Cached vs fresh calls** - tracks cache hit rate  
✅ **Cost in credits** - ready for Stripe integration

**Verify in Database:**
```bash
ddev artisan tinker
>>> App\Models\SatelliteApiCall::whereDate('created_at', today())->get();
# Shows all API calls today with type, cost, coordinates, etc.
```

**Billing-Ready Features:**
- Different costs per call type (overlay cheaper than enrichment)
- Tracks user_id for per-user billing
- Tracks campaign_id for per-project billing
- Cost in credits ready for conversion to USD
- Cache tracking shows cost savings

---

### Test: Cache Hit Rate Calculation

**Understanding Cache Behavior:**
- **First request** for a location/date/overlay = FRESH call (cached = false)
- **Subsequent requests** for same location/date/overlay = CACHED call (cached = true)
- Cache is per combination of: latitude, longitude, date, overlay type, width, height

**Steps to Test Fresh vs Cached:**

**Step 1: Clear Application Cache**
```bash
ddev artisan cache:clear
```

**Step 2: Make First Request (Fresh)**
1. Go to `/maps/satellite`
2. Select campaign and date
3. Select overlay type (e.g., NDVI)
4. Wait for overlay to load
5. Note dashboard: Should show "0 cached / 1 fresh today" (0% cache hit rate)

**Step 3: Make Same Request Again (Cached)**
1. Refresh the satellite viewer page
2. Load the SAME overlay (same location, date, type)
3. Check dashboard: Should show "1 cached / 1 fresh today" (50% cache hit rate)

**Step 4: Make Different Request (Fresh)**
1. Change overlay type to Moisture
2. Wait for load
3. Check dashboard: Should show "1 cached / 2 fresh today" (33% cache hit rate)

**Step 5: Repeat Same Requests (Cached)**
1. Switch back to NDVI (already cached)
2. Then Moisture (already cached)
3. Check dashboard: Should show "3 cached / 2 fresh today" (60% cache hit rate)

**Validation:**

**If cache hit rate > 80%:**
✅ Color: Green/success  
✅ Indicates good caching performance

**If cache hit rate ≤ 80%:**
✅ Color: Yellow/warning  
✅ Indicates room for improvement

**Calculation:**
```
Total calls = cached + fresh
Hit rate = (cached / total) × 100

Example:
6 cached + 0 fresh = 6 total
Hit rate = (6 / 6) × 100 = 100% ✅ Green
```

**Why You See 100% Cache Hit:**
If you're seeing "6 cached / 0 fresh", it means:
- All 6 requests were for combinations already in cache
- No fresh API calls were made to Copernicus
- This is GOOD - saves API costs!

**To See Fresh Calls:**
```bash
# Clear cache
ddev artisan cache:clear

# Make a new satellite request
# First time = fresh call
# Second time = cached call
```

---

### Test: Average Indices Calculation

**Steps:**
1. View satellite analyses table
2. Count indices per analysis (NDVI, NDMI, NDRE, EVI, MSI, SAVI, GNDVI)
3. Calculate average manually
4. Compare to widget value

**Expected Results:**
✅ Widget shows accurate average  
✅ Counts only analyses created this month  
✅ Includes all 7 indices in calculation  
✅ Rounded to 1 decimal place

**Example Calculation:**
```
Analysis 1: 5 indices (NDVI, NDMI, NDRE, EVI, MSI)
Analysis 2: 7 indices (all)
Analysis 3: 4 indices (NDVI, NDMI, EVI, SAVI)

Average: (5 + 7 + 4) / 3 = 16 / 3 = 5.3
Widget shows: "5.3"
```

---

### Test: 7-Day Trend Chart

**Steps:**
1. View "Satellite API Calls" card
2. Check trend chart below value
3. Verify data points

**Expected Results:**
✅ Chart shows 7 data points (last 7 days)  
✅ Most recent day on right  
✅ Oldest day on left  
✅ Height represents call volume  
✅ Chart updates daily

---

## 5. QA Flags Display (2 minutes)

### Test: QA Flags Column in Data Points Table

**URL:** `/admin/data-points`

**Expected Column:**

**Column Name:** "QA Flags"  
**Position:** Between "Submitted By" and "Collected" columns  
**Visibility:** Visible by default (toggleable)

**Display Logic:**

**For Clean Data Points (no flags):**
✅ Badge displays: "Clean"  
✅ Color: Green/success  
✅ No tooltip

**For Flagged Data Points (has flags):**
✅ Badge displays: "X issue(s)" (e.g., "1 issue", "2 issues")  
✅ Color: Yellow/warning  
✅ Tooltip on hover shows flag reasons:
```
GPS accuracy 75.0m exceeds threshold (50m)
Value 50.00 outside expected range [-10.00 - 40.00] for Temperature
```
✅ Each reason on new line in tooltip

---

### Test: Flag Types Display

**Create/Find Data Points with Different Flags:**

**1. High GPS Error Flag:**
✅ Type: "high_gps_error"  
✅ Badge: "1 issue"  
✅ Tooltip: "GPS accuracy {X}m exceeds threshold (50m)"

**2. Unexpected Range Flag:**
✅ Type: "unexpected_range"  
✅ Badge: "1 issue"  
✅ Tooltip: "Value {X} outside expected range [{min} - {max}] for {metric}"

**3. Statistical Outlier Flag:**
✅ Type: "statistical_outlier"  
✅ Badge: "1 issue"  
✅ Tooltip: "Value {X} outside expected range [{lower} - {upper}] (IQR method)"

**4. Outside Zone Flag:**
✅ Type: "outside_zone"  
✅ Badge: "1 issue"  
✅ Tooltip: "Data point location is outside campaign survey zones"

**5. Multiple Flags:**
✅ Badge: "3 issues" (if 3 flags present)  
✅ Tooltip shows all flag reasons (one per line)

---

### Test: QA Status Filter

**Steps:**
1. Click "Filters" button
2. Scroll to "QA Status" filter
3. Select option
4. Apply filter

**Expected Filter Options:**

**1. Clean (No Issues):**
✅ Filters to data points with `qa_flags = NULL`  
✅ Only shows "Clean" badge items

**2. Flagged (Has Issues):**
✅ Filters to data points with `qa_flags NOT NULL`  
✅ Only shows "X issue(s)" badge items

**Filter Properties:**
✅ Not native select  
✅ "QA Status" indicator badge appears when filtered  
✅ Can be combined with other filters

---

### Test: Bulk Clear Flags Action

**Steps:**
1. Select multiple flagged data points (checkboxes)
2. Click bulk actions dropdown
3. Select **"Clear QA Flags"**
4. Confirm action
5. Check results

**Expected Results:**
✅ "Clear QA Flags" option visible in bulk actions  
✅ Blue/info color  
✅ Shield check icon (heroicon-o-shield-check)  
✅ **Confirmation modal appears:**
- Heading: "Clear Quality Flags"
- Description: "Are you sure you want to clear QA flags from the selected data points?"
- Submit button: "Yes, clear flags"
✅ After confirming:
- All selected items' qa_flags set to NULL
- Badges change to "Clean" (green)
- **Success notification:** "QA flags cleared! Cleared flags from X data point(s)."
- Proper singular/plural grammar

---

## 6. Automated Quality Checks (3 minutes)

### Test: Flag Suspicious Readings Command

**Command:**
```bash
ddev artisan ecosurvey:quality-check --flag-suspicious
```

**Steps:**
1. Create data points with quality issues:
   - One with GPS accuracy > 50m
   - One with value outside expected range
2. Run command
3. Check results

**Expected Output:**
```
Running quality checks...
✓ Flagged 2 suspicious readings for review

Quality check completed successfully!
```

**Verify:**
✅ Command executes without errors  
✅ Info message: "Running quality checks..."  
✅ Success message: "✓ Flagged {X} suspicious readings for review"  
✅ Completion message: "Quality check completed successfully!"  
✅ Exit code: 0 (success)

**Database Verification:**
✅ Data points with issues now have qa_flags populated  
✅ Flag types correct (high_gps_error, unexpected_range, etc.)  
✅ Flag reasons descriptive  
✅ flagged_at timestamp set

---

### Test: Auto-Approve Command

**Command:**
```bash
ddev artisan ecosurvey:quality-check --auto-approve
```

**Steps:**
1. Create high-quality pending data points:
   - GPS accuracy ≤ 10m
   - No qa_flags
   - Status: pending
2. Run command
3. Check results

**Expected Output:**
```
Running quality checks...
✓ Auto-approved 3 high-quality data points

Quality check completed successfully!
```

**Verify:**
✅ Command executes without errors  
✅ Success message shows count of approved items  
✅ Exit code: 0

**Database Verification:**
✅ High-quality data points now have status = 'approved'  
✅ reviewed_at timestamp set  
✅ review_notes = "Auto-approved: High GPS accuracy, no quality issues"  
✅ Data points with accuracy > 10m NOT approved  
✅ Data points with qa_flags NOT approved

---

### Test: Combined Command

**Command:**
```bash
ddev artisan ecosurvey:quality-check --flag-suspicious --auto-approve
```

**Expected Results:**
✅ Both actions execute  
✅ Two success messages displayed:
```
✓ Flagged {X} suspicious readings for review
✓ Auto-approved {Y} high-quality data points
```
✅ No conflicts between actions

---

### Test: No Options Error

**Command:**
```bash
ddev artisan ecosurvey:quality-check
```

**Expected Output:**
```
Running quality checks...
No action specified. Use --flag-suspicious or --auto-approve
Run with --help for more information
```

**Verify:**
✅ Warning message displayed (yellow)  
✅ Help hint provided  
✅ Exit code: 1 (failure)  
✅ No database changes

---

### Test: Help Documentation

**Command:**
```bash
ddev artisan ecosurvey:quality-check --help
```

**Expected Results:**
✅ Description: "Run automated quality checks on data points"  
✅ Options listed:
- `--auto-approve`: Auto-approve qualified data points
- `--flag-suspicious`: Flag suspicious readings  
✅ Usage examples shown

---

## 7. Expected Ranges Validation (2 minutes)

### Test: Environmental Metrics Expected Ranges

**URL:** `/admin` → Navigate to Environmental Metrics (if accessible)

**Expected Metrics with Ranges:**

**1. Air Quality Index:**
✅ expected_min: 0  
✅ expected_max: 500

**2. Temperature:**
✅ expected_min: -40  
✅ expected_max: 50

**3. Humidity:**
✅ expected_min: 0  
✅ expected_max: 100

**4. Noise Level:**
✅ expected_min: 30  
✅ expected_max: 120

**5. PM2.5:**
✅ expected_min: 0  
✅ expected_max: 500

**6. PM10:**
✅ expected_min: 0  
✅ expected_max: 600

**7. CO2:**
✅ expected_min: 300  
✅ expected_max: 5000

---

### Test: Range Validation in Quality Checks

**Steps:**
1. Create data point with Temperature = -50°C (below min -40)
2. Run `ddev artisan ecosurvey:quality-check --flag-suspicious`
3. Check data point

**Expected Results:**
✅ Data point flagged with "unexpected_range"  
✅ Reason: "Value -50.00 outside expected range [-40.00 - 50.00] for Temperature"  
✅ Flag severity: "warning"

---

### Test: Within Range (No Flag)

**Steps:**
1. Create data point with Temperature = 25°C (within range)
2. Run quality check command
3. Check data point

**Expected Results:**
✅ Data point NOT flagged for range issue  
✅ qa_flags remains NULL or doesn't include "unexpected_range"

---

## 8. Integration Tests (2 minutes)

### Test: Quality Check Workflow

**Complete Workflow:**

**Step 1: Submit Data Points**
- Create 10 data points with varying quality
- Some with high GPS error (>50m)
- Some with excellent GPS (<10m)
- Some outside expected ranges

**Step 2: Run Quality Checks**
```bash
ddev artisan ecosurvey:quality-check --flag-suspicious --auto-approve
```

**Step 3: Verify Results**
✅ Poor quality items flagged  
✅ High quality items auto-approved  
✅ Counts accurate

**Step 4: Check Dashboard**
✅ QA Stats widget updated:
- Pending count decreased
- Approved count increased
- Flagged count increased
✅ User Leaderboard updated:
- User submission counts correct
- Approval rates accurate
✅ API Usage unchanged (no satellite calls)

**Step 5: Manual Review**
1. Navigate to `/admin/data-points`
2. Filter by "QA Status: Flagged"
3. Review flagged items
4. Bulk clear flags or individual approve/reject

**Step 6: Final Verification**
✅ All flagged items reviewed  
✅ Dashboard stats accurate  
✅ No pending items with flags remaining

---

## Edge Cases & Error Handling

### Test: No Data Points

**Steps:**
1. Clear all data points
2. Run quality check command
3. View Quality Dashboard

**Expected Results:**
✅ Command output: "✓ Flagged 0 suspicious readings for review"  
✅ Leaderboard shows empty state  
✅ QA Stats show zeros  
✅ No errors

---

### Test: All Data Points High Quality

**Steps:**
1. Ensure all data points have:
   - GPS accuracy ≤ 10m
   - Values within expected ranges
   - No flags
2. Run auto-approve command

**Expected Results:**
✅ All pending items approved  
✅ Command shows accurate count  
✅ Dashboard reflects changes

---

### Test: All Data Points Flagged

**Steps:**
1. Ensure all pending data points have quality issues
2. Run flag-suspicious command
3. Run auto-approve command

**Expected Results:**
✅ Flag command flags all items  
✅ Auto-approve command approves 0 items (all have flags)  
✅ Messages accurate

---

### Test: Statistical Outlier Detection (Insufficient Data)

**Steps:**
1. Create campaign with <10 approved data points
2. Create new pending data point
3. Run quality check

**Expected Results:**
✅ No statistical outlier flag (insufficient data)  
✅ Command completes successfully  
✅ Only other checks applied (GPS, range, zone)

---

### Test: Statistical Outlier Detection (Sufficient Data)

**Steps:**
1. Create campaign with 15 approved data points (values 18-22°C)
2. Create outlier: 35°C
3. Run quality check

**Expected Results:**
✅ Outlier flagged with "statistical_outlier"  
✅ Reason includes IQR bounds  
✅ Details include Q1, Q3, IQR values

---

### Test: Zone Validation (No Zones Defined)

**Steps:**
1. Create campaign without survey zones
2. Create data point in campaign
3. Run quality check

**Expected Results:**
✅ Data point NOT flagged for "outside_zone"  
✅ Only campaigns with defined zones trigger this check

---

### Test: Zone Validation (With Zones)

**Steps:**
1. Create campaign with survey zone polygon
2. Create data point outside polygon
3. Run quality check

**Expected Results:**
✅ Data point flagged with "outside_zone"  
✅ Reason: "Data point location is outside campaign survey zones"

---

## Testing Completion Checklist

After completing all tests, verify:

### Quality Dashboard
- [ ] Quality Dashboard page loads at `/admin/quality-dashboard`
- [ ] Located in "Data Quality" navigation group
- [ ] Shield check icon visible
- [ ] Page heading and description correct
- [ ] Three widget sections display
- [ ] Command reference section visible with code blocks

### QA Statistics Widget
- [ ] 6 statistics cards display
- [ ] Pending review with trend chart
- [ ] Approved with approval rate
- [ ] Rejected count
- [ ] Active campaigns count
- [ ] Total data points count
- [ ] Active users count
- [ ] All colors correct
- [ ] Widget sort order: 1

### User Contribution Leaderboard
- [ ] Top 5 contributors displayed
- [ ] Gold medal (🥇) for #1
- [ ] Silver medal (🥈) for #2
- [ ] Bronze medal (🥉) for #3
- [ ] Rank numbers for #4 and #5
- [ ] Submission counts accurate
- [ ] Approval rates calculated correctly
- [ ] Average accuracy rounded to 2 decimals
- [ ] Empty state works (no data message)
- [ ] Widget sort order: 2
- [ ] Full width display

### API Usage Tracker
- [ ] Satellite API calls count (today and month)
- [ ] 7-day trend chart displays
- [ ] Cache hit rate percentage
- [ ] Cache hit/miss counts in description
- [ ] Color changes based on hit rate (>80% green, ≤80% yellow)
- [ ] Average indices calculation accurate
- [ ] Widget sort order: 3

### QA Flags Display
- [ ] QA Flags column visible in data points table
- [ ] "Clean" badge for unflagged items (green)
- [ ] "X issue(s)" badge for flagged items (yellow)
- [ ] Tooltip shows all flag reasons
- [ ] Multiple flags display correctly
- [ ] QA Status filter works (clean/flagged)
- [ ] Bulk clear flags action available
- [ ] Confirmation modal for bulk clear
- [ ] Success notification after clearing

### Automated Quality Checks
- [ ] `--flag-suspicious` command works
- [ ] Flags high GPS error (>50m)
- [ ] Flags unexpected range violations
- [ ] Flags statistical outliers (IQR method)
- [ ] Flags outside zone (when zones exist)
- [ ] `--auto-approve` command works
- [ ] Approves only high quality (≤10m, no flags)
- [ ] Both commands work together
- [ ] No options shows error message
- [ ] Help documentation accessible

### Expected Ranges
- [ ] All 7 metrics have expected_min and expected_max
- [ ] Ranges realistic and accurate
- [ ] Range validation triggers flags correctly
- [ ] Within-range values don't get flagged

### Integration
- [ ] Complete workflow from submission to review works
- [ ] Dashboard updates after quality checks
- [ ] Leaderboard reflects user activity
- [ ] API usage tracks satellite calls
- [ ] No data edge case handled
- [ ] All high quality edge case handled
- [ ] Insufficient data for outliers handled
- [ ] Zone validation logic correct

### Performance
- [ ] Quality checks complete in reasonable time
- [ ] Dashboard loads quickly
- [ ] No JavaScript errors
- [ ] No console warnings
- [ ] Widgets update without full page reload

---

## Automated Test Verification

### Incremental Testing (Fast Feedback)

**Problem:** Running full test suite is slow (~2-3 minutes)  
**Solution:** Test incrementally with these strategies:

#### 1. Run Single Test File (Fastest)
```bash
# Run only Quality Check tests (~2 minutes)
ddev artisan test tests/Feature/Services/QualityCheckServiceTest.php --compact
```

#### 2. Filter by Test Name
```bash
# Run specific test (1-5 seconds)
ddev artisan test --filter="detects high GPS error" --compact

# Run multiple related tests
ddev artisan test --filter="QualityCheck" --compact
```

#### 3. Run Last Failed Tests Only
```bash
# After a failure, re-run only failed tests
ddev artisan test --failed --compact
```

#### 4. Use Pest's Direct Runner (Faster)
```bash
# Skip Laravel bootstrapping overhead
ddev exec vendor/bin/pest tests/Feature/Services/QualityCheckServiceTest.php

# Single test with filter
ddev exec vendor/bin/pest --filter="detects high GPS error"
```

#### 5. Parallel Testing (Fastest for Full Suite)
```bash
# Run tests in parallel (requires parallel plugin)
ddev composer require pestphp/pest-plugin-parallel --dev
ddev artisan test --parallel --compact
```

#### 6. Watch Mode (Continuous Testing)
```bash
# Auto-run tests when files change (requires pest-plugin-watch)
ddev composer require pestphp/pest-plugin-watch --dev
ddev exec vendor/bin/pest --watch
```

#### 7. Run Specific Test Groups
```bash
# Tag tests with groups in test files:
# test('something')->group('quality', 'fast');

# Run only fast tests
ddev artisan test --group=quality --compact
```

### Recommended Workflow

**During Development:**
```bash
# 1. Run single test you're working on
ddev artisan test --filter="test name" --compact

# 2. When test passes, run the whole file
ddev artisan test tests/Feature/Services/QualityCheckServiceTest.php --compact

# 3. Before committing, run related tests
ddev artisan test --filter=QualityCheck --compact

# 4. Before pushing, run full suite (or use CI)
ddev artisan test --compact
```

**Time Savings:**
- Single test: ~2-5 seconds ⚡
- Single file: ~2 minutes 🚀
- Filtered tests: ~30-60 seconds 💨
- Full suite: ~3-5 minutes 🐌

### Run Automated Tests

**Steps:**
```bash
# Run Quality Check Service tests
ddev artisan test tests/Feature/Services/QualityCheckServiceTest.php --compact

# Expected: 8 passed (22 assertions)

# Run Analytics Service tests
ddev artisan test tests/Feature/Services/AnalyticsServiceTest.php --compact

# Expected: All tests pass

# Run all admin tests
ddev artisan test --filter=Admin --compact
```

**Expected Results:**
✅ All 8 QualityCheckService tests pass:
- ✓ detects high GPS error
- ✓ detects value outside expected range
- ✓ detects statistical outliers using IQR method
- ✓ passes clean data point with no issues
- ✓ gets campaign quality statistics
- ✓ gets user contribution statistics
- ✓ auto-approves qualified data points
- ✓ flags suspicious readings

✅ All AnalyticsService tests pass:
- ✓ get heatmap data returns formatted array
- ✓ get heatmap data filters by campaign
- ✓ get heatmap data filters by metric
- ✓ get time series data
- ✓ get campaign summary statistics
- etc.

✅ Total: 22 assertions (QualityCheck) + additional (Analytics)
✅ Duration: ~130 seconds  
✅ No failures

---

## Known Limitations (Not Bugs)

**Current Limitations:**
- Cache hit/miss tracking requires manual increment (not automatic)
- User contribution leaderboard limited to last 30 days
- Auto-approval threshold fixed at 10m (not configurable)
- GPS error threshold fixed at 50m (not configurable)
- Statistical outlier detection requires ≥10 approved data points
- Zone validation only checks if point is inside any zone (not specific zone)
- No email notifications for flagged items
- No audit trail for auto-approvals

**Future Enhancements (Deferred):**
- Configurable quality thresholds
- Machine learning for outlier detection
- Automatic cache tracking via middleware
- Real-time quality scoring
- Quality trend analysis
- Reviewer performance metrics
- Auto-notification for critical issues
- Quality SLA tracking

---

## Troubleshooting

### Quality Checks Not Flagging Items

**Check:**
1. Expected ranges configured on environmental metrics
2. GPS accuracy values present on data points
3. Campaign has survey zones (for zone check)
4. Sufficient approved data for outlier detection (≥10)

**Solution:**
```bash
# Check metric ranges
ddev artisan tinker
>>> App\Models\EnvironmentalMetric::all(['id', 'name', 'expected_min', 'expected_max']);

# Manually run quality check on one item
>>> $dp = App\Models\DataPoint::find(1);
>>> $service = app(App\Services\QualityCheckService::class);
>>> $flags = $service->runQualityChecks($dp);
>>> dd($flags);
```

---

### Leaderboard Not Showing Users

**Possible Causes:**
- No data points created in last 30 days
- Users have no submitted data points

**Check:**
```bash
ddev artisan tinker
>>> App\Models\DataPoint::where('created_at', '>=', now()->subDays(30))->count();
>>> App\Models\User::has('dataPoints')->count();
```

---

### API Usage Not Updating

**Possible Causes:**
- No satellite analyses created
- Cache values not being set

**Check:**
```bash
# Check satellite analyses count
ddev artisan tinker
>>> App\Models\SatelliteAnalysis::whereDate('analyzed_at', today())->count();

# Check cache values
>>> Cache::get('api.cache_hits.today');
>>> Cache::get('api.cache_misses.today');
```

**Note:** Cache tracking requires manual implementation in satellite service.

---

### Widgets Not Displaying

**Check:**
1. Widget classes registered in QualityDashboard page
2. Filament cache cleared
3. No PHP errors

**Solution:**
```bash
# Clear Filament cache
ddev artisan filament:clear-cached-components

# Check for errors
tail -f storage/logs/laravel.log
```

---

### Command Not Found

**Error:** `Command "ecosurvey:quality-check" is not defined`

**Solution:**
```bash
# Clear command cache
ddev artisan optimize:clear

# Verify command exists
ddev artisan list | grep quality

# Should show:
# ecosurvey:quality-check
```

---

## Notes for Developers

**If Issues Found During Testing:**

1. **Check service implementation:**
   ```bash
   # Verify QualityCheckService exists
   ls -la app/Services/QualityCheckService.php
   ```

2. **Verify widget registration:**
   ```php
   // In app/Filament/Admin/Pages/QualityDashboard.php
   protected function getHeaderWidgets(): array
   {
       return [
           QualityAssuranceStatsWidget::class,
           UserContributionLeaderboard::class,
           ApiUsageTracker::class,
       ];
   }
   ```

3. **Check migration ran:**
   ```bash
   ddev artisan migrate:status | grep expected_ranges
   # Should show: [1] Ran
   ```

4. **Verify model fillable:**
   ```php
   // In app/Models/EnvironmentalMetric.php
   protected $fillable = [
       'name', 'unit', 'description',
       'expected_min', 'expected_max', // Should be present
       'is_active',
   ];
   ```

5. **Check database schema:**
   ```bash
   ddev artisan tinker
   >>> Schema::hasColumn('environmental_metrics', 'expected_min');
   >>> Schema::hasColumn('environmental_metrics', 'expected_max');
   # Both should return true
   ```

---

## User Guide Reference

User guides to be created:
- **Quality Dashboard Guide** (for admins)
- **Automated Quality Checks Guide** (for admins)
- **Quality Metrics Interpretation Guide** (for admins)

---

## Success Criteria

**Phase 9 is COMPLETE when:**

- ✅ Quality Dashboard accessible at `/admin/quality-dashboard`
- ✅ All three widgets display correctly (QA Stats, Leaderboard, API Usage)
- ✅ User Contribution Leaderboard shows top 5 with medals
- ✅ API Usage Tracker shows satellite calls and cache performance
- ✅ QA Flags column visible in data points table
- ✅ QA Status filter works (clean/flagged)
- ✅ Bulk clear flags action functional
- ✅ `ecosurvey:quality-check --flag-suspicious` flags quality issues
- ✅ `ecosurvey:quality-check --auto-approve` approves high-quality data
- ✅ Expected ranges configured on all environmental metrics
- ✅ Statistical outlier detection works with IQR method
- ✅ Zone validation only triggers when campaign has zones
- ✅ All 8 automated tests pass (22 assertions)
- ✅ No errors or crashes
- ✅ Dashboard updates reflect quality check results
- ✅ Documentation complete

---

**Phase 9 Status:** ⏳ PENDING TESTING

**Testing Required:** Manual browser testing to verify all features work as documented

**Completion Date:** Pending  
**Total Testing Time:** 10-12 minutes estimated  
**Known Issues:** To be discovered during testing

**Next Steps:**
1. Run manual browser tests following this cookbook
2. Fix any issues discovered
3. Run automated tests
4. Update roadmap to mark Phase 9 as complete
5. Create user documentation

**Last Updated:** January 19, 2026
