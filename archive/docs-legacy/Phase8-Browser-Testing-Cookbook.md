# Phase 8 Features - Browser Testing Cookbook

**Last Updated:** January 19, 2026  
**Estimated Time:** 8-10 minutes  
**Prerequisites:** Logged in as admin user, data points with various statuses exist

**Testing Status:** ✅ TESTED AND APPROVED (Completed: January 19, 2026)

**All Features Tested:**
- ✅ Quality Assurance Dashboard widget
- ✅ Data point review interface
- ✅ Approval/rejection workflow
- ✅ Bulk operations (approve/reject)
- ✅ Filtering and search
- ✅ GPS accuracy validation
- ✅ Photo upload and preview
- ✅ Edit functionality
- ✅ Centered success notifications

---

## Testing Notes

**Phase 8 Features to Test:**
1. Quality Assurance Dashboard widget
2. Data point review interface
3. Approval/rejection workflow
4. Bulk operations
5. Filtering and search
6. GPS accuracy validation

**Important:** There are TWO edit interfaces for data points:
1. **Admin Panel Edit** (this phase) - `/admin/data-points/{id}/edit` - Full admin control
2. **Map Edit Modal** (Phase 3) - `/maps/survey` - User-facing, simplified

Both interfaces edit the same database records but serve different purposes and user roles.

**Prerequisites:**
- Admin access to `/admin`
- Data points with different statuses (draft, pending, approved, rejected)
- Multiple campaigns active
- Data points with varying GPS accuracy

**Key Features in Phase 8:**
- ✅ QA dashboard with 6 statistics
- ✅ Data point review table
- ✅ Approve/reject workflow
- ✅ Bulk approve/reject operations
- ✅ Advanced filtering (status, campaign, metric, GPS accuracy)
- ✅ GPS accuracy color coding

---

## Quick Test Checklist

- [ ] **Quality Assurance Dashboard** (2 min)
- [ ] **Data Point Review Table** (3 min)
- [ ] **Approval/Rejection Workflow** (2 min)
- [ ] **Bulk Operations** (2 min)
- [ ] **Filtering & Search** (1 min)

---

## 1. Quality Assurance Dashboard (2 minutes)

### Test: Access Admin Dashboard

**URL:** `/admin`

**Steps:**
1. Navigate to admin panel
2. Review dashboard layout
3. Check QA statistics widget

**Expected Results:**
✅ Admin dashboard loads successfully  
✅ QA statistics widget displays at top  
✅ 6 statistics cards visible in grid layout  
✅ No JavaScript errors

---

### Test: QA Statistics Widget

**Expected Statistics (6 cards):**

**1. Pending Review (Warning - Yellow):**
✅ Count of pending data points  
✅ Description: "Data points awaiting approval"  
✅ Clock icon (heroicon-o-clock)  
✅ 7-day trend chart showing recent pending submissions

**2. Approved (Success - Green):**
✅ Count of approved data points  
✅ Description: "{X}% approval rate" (calculated percentage)  
✅ Check circle icon (heroicon-o-check-circle)

**3. Rejected (Danger - Red):**
✅ Count of rejected data points  
✅ Description: "Quality control rejections"  
✅ X circle icon (heroicon-o-x-circle)

**4. Active Campaigns (Info - Blue):**
✅ Count of active campaigns  
✅ Description: "Currently collecting data"  
✅ Map icon (heroicon-o-map)

**5. Total Data Points (Primary - Blue):**
✅ Count of all data points  
✅ Description: "All submitted measurements"  
✅ Clipboard icon (heroicon-o-clipboard-document-check)

**6. Active Users (Success - Green):**
✅ Count of registered users  
✅ Description: "Registered contributors"  
✅ Users icon (heroicon-o-users)

**Validation:**
- All counts are accurate
- Approval rate calculated correctly: (approved / total) × 100
- Colors match status (warning, success, danger, info, primary)
- Icons display correctly
- Trend chart shows 7 days of data

---

### Test: Pending Review Badge

**Steps:**
1. Check navigation sidebar
2. Find "Review Data Points" menu item

**Expected Results:**
✅ Menu item labeled "Review Data Points"  
✅ Located in "Data Quality" group  
✅ Badge showing pending count (yellow/warning color)  
✅ Clipboard with check icon (heroicon-o-clipboard-document-check)

**Example:**
```
Data Quality
  Review Data Points [12] ← Yellow badge with count
```

---

## 2. Data Point Review Table (3 minutes)

### Test: Access Review Interface

**URL:** `/admin/data-points`

**Steps:**
1. Click "Review Data Points" in navigation
2. Review table layout
3. Check all columns visible

**Expected Results:**
✅ Data points table loads  
✅ Multiple data points displayed  
✅ Table is sortable  
✅ Pagination works (if >15 items)  
✅ No errors

---

### Test: Table Columns

**Verify All Columns Present (12 total):**

**1. ID:**
✅ Numeric ID  
✅ Sortable  
✅ Hidden by default (toggle to show)

**2. Status (Badge):**
✅ Color-coded badges:
- Draft: Gray (secondary)
- Pending: Yellow (warning)
- Approved: Green (success)
- Rejected: Red (danger)
✅ Sortable  
✅ Searchable

**3. Campaign:**
✅ Campaign name  
✅ Searchable  
✅ Sortable  
✅ Wrapped text (medium weight)
✅ Limited to 30 characters

**4. Metric (Badge):**
✅ Environmental metric name  
✅ Blue badge (info color)  
✅ Searchable  
✅ Sortable
✅ Wrapped text

**5. Value:**
✅ Formatted number (2 decimals)  
✅ Includes unit (e.g., "22.50 °C")  
✅ Sortable

**6. GPS (Coordinates):**
✅ Latitude, Longitude format: "55.7072, 12.5704"  
✅ 4 decimal places  
✅ **Hidden by default** (toggle to show)

**7. GPS Accuracy (Badge):**
✅ Formatted as "{number}m" (e.g., "7.5m")  
✅ Color-coded:
- <10m: Green (success) - Excellent
- 10-20m: Yellow (warning) - Good
- >20m: Red (danger) - Poor
- NULL: Gray (secondary) - Unknown
✅ Sortable  
✅ **Visible by default**

**8. Photo:**
✅ Thumbnail image (40px height)  
✅ Displays if photo_path exists  
✅ Empty if no photo  
✅ **Hidden by default** (toggle to show)
✅ Stored in public disk

**9. Submitted By:**
✅ User name  
✅ Searchable  
✅ Sortable  
✅ **Hidden by default** (toggle to show)
✅ Wrapped text

**10. Collected:**
✅ Collection date/time: "Jan 15, 2026 14:30"  
✅ Sortable  
✅ **Hidden by default** (toggle to show)

**11. Notes:**
✅ Limited to 50 characters  
✅ Text wraps  
✅ Hidden by default (toggle to show)

**12. Submitted:**
✅ Submission date/time  
✅ Sortable  
✅ Hidden by default (toggle to show)

**Default Visible Columns (5 total):**
1. Status
2. Campaign (limited to 30 chars)
3. Metric
4. Value
5. GPS Accuracy

**Default Hidden Columns (can be toggled on):**
1. ID
2. GPS Coordinates
3. Photo
4. Submitted By
5. Collected date
6. Notes
7. Submitted date

**Table Layout:**
✅ No horizontal scrollbar with default columns  
✅ Responsive design matches campaigns table  
✅ Text wrapping on long content  
✅ Users can toggle additional columns via column manager

**Default Sort:**
✅ Newest first (created_at desc)

---

### Test: GPS Accuracy Color Coding

**Create/Find Data Points with Different Accuracy:**

**Steps:**
1. Find data point with accuracy <10m
2. Find data point with accuracy 10-20m
3. Find data point with accuracy >20m
4. Find data point with NULL accuracy

**Expected Badge Colors:**
✅ <10m: Green badge "7.5m" (excellent)  
✅ 10-20m: Yellow badge "15.2m" (good)  
✅ >20m: Red badge "25.8m" (poor)  
✅ NULL: Gray badge "N/A" (unknown)

---

## 3. Approval/Rejection Workflow (2 minutes)

### Test: Approve Single Data Point

**Steps:**
1. Find a pending data point
2. Click **"Approve"** button (green, check icon)
3. Confirm in dialog
4. Check result

**Expected Results:**
✅ Approve button visible (green color, check circle icon)  
✅ Confirmation dialog appears: "Are you sure?"  
✅ After confirming:
- Status changes to "Approved" (green badge)
- Approve button disappears
- Reject button still visible
✅ Dashboard stats update (pending -1, approved +1)  
✅ Success notification shown

---

### Test: Reject Single Data Point

**Steps:**
1. Find a pending data point
2. Click **"Reject"** button (red, X icon)
3. Confirm in dialog
4. Check result

**Expected Results:**
✅ Reject button visible (red color, X circle icon)  
✅ Confirmation dialog appears  
✅ After confirming:
- Status changes to "Rejected" (red badge)
- Reject button disappears
- Approve button still visible
✅ Dashboard stats update (pending -1, rejected +1)  
✅ Success notification shown

---

### Test: Edit Data Point

**Note:** This is the **Admin Panel Edit** interface. There's also a separate **Map Edit Modal** for users on `/maps/survey`. Both are functional and serve different purposes:
- Admin Panel: Full control, all fields, QA features
- Map Modal: User-facing, simplified, field corrections

**Steps:**
1. Click **"Edit"** button (pencil icon)
2. Review form sections
3. Modify a field
4. Save changes

**Expected Results:**
✅ Edit form opens with 4 sections:

**Section 1: Data Point Information**
- Campaign (searchable dropdown)
- Environmental Metric (searchable dropdown)
- Measurement Value (numeric, 2 decimals)
- Status (dropdown: draft/pending/approved/rejected)

**Section 2: Location Information**
- Latitude (numeric, -90 to +90)
- Longitude (numeric, -180 to +180)
- GPS Accuracy in meters (numeric)

**Section 3: Collection Details**
- Collection Date & Time (datetime picker)
- Submitted By (user dropdown)
- Device Model (text)
- Sensor Type (text)

**Section 4: Additional Information**
- Photo (file upload, image preview, max 5MB, stored in public/data-points)
- Notes (textarea, max 1000 chars)

✅ All fields editable  
✅ GPS coordinates populated from PostGIS location  
✅ **Loading spinner shown during save** (Filament default)
✅ **Success notification after save:** "Reading updated successfully! The data point has been updated." (appears CENTERED on screen, green toast with enhanced shadow)
✅ **Auto-redirect to list page** after successful save
✅ **Photo preview tests:**
- ✅ Existing photo shows preview on page load (200px height)
- ✅ Photo is downloadable (click to download)
- ✅ Photo is openable (click to view full size)
- ✅ Can remove existing photo (X button)
- ✅ Can upload new photo (replaces old)
- ✅ After save: Photo preview remains visible (not just filename)
- ✅ After reload: Photo preview still shows correctly
✅ New photo can be uploaded  
✅ Photo preview shows 200px height  
✅ Image editor available  
✅ Save button works  
✅ Returns to list after saving  
✅ Changes reflected in table  
✅ GPS location updated in database  
✅ Photo saved to public disk (public/data-points/)  
✅ Photo persists across saves

---

### Test: Photo Upload and Preview

**This is a critical test for the photo functionality fix.**

**Steps:**
1. Find a data point that **has a photo**
2. Click Edit button
3. Verify existing photo preview
4. Save without changes
5. Check photo persists
6. Upload new photo
7. Save and verify

**Test 1: Existing Photo Preview on Load**
✅ Photo preview displays immediately when edit page loads  
✅ Preview shows image (not just filename)  
✅ Preview height is 200px  
✅ Download button visible (can click to download)  
✅ Open button visible (can click to view full size)  
✅ Remove button visible (X icon)

**Test 2: Save Without Photo Changes**
1. Make a change to a different field (e.g., notes)
2. Click Save
3. **Expected:** Photo preview remains visible after save
4. **Not:** Only filename showing
5. Return to edit page
6. **Expected:** Photo preview still shows

**Test 3: Upload New Photo**
1. Click "Choose file" or drag & drop new photo
2. **Expected:** Preview updates to new photo immediately
3. Click Save
4. **Expected:** New photo preview remains visible
5. Check table - thumbnail shows new photo
6. Return to edit
7. **Expected:** New photo preview shows

**Test 4: Remove Photo**
1. Click X button on photo preview
2. **Expected:** Photo preview clears
3. Click Save
4. **Expected:** Photo removed from database
5. Check table - no thumbnail
6. Return to edit
7. **Expected:** No photo preview, upload area shown

**Common Issues (Now Fixed):**
- ❌ OLD BUG: Photo showed on load but disappeared after save (only filename shown)
- ✅ FIXED: Photo preview persists after save
- ✅ FIXED: Photo path properly saved to database
- ✅ FIXED: Filament FileUpload array handling corrected

---

### Test: Action Button Visibility

**For Approved Data Points:**
✅ Approve button hidden  
✅ Reject button visible  
✅ Edit button visible

**For Rejected Data Points:**
✅ Approve button visible  
✅ Reject button hidden  
✅ Edit button visible

**For Pending Data Points:**
✅ Approve button visible  
✅ Reject button visible  
✅ Edit button visible

---

## 4. Bulk Operations (2 minutes)

### Test: Bulk Approve

**Steps:**
1. Select multiple pending data points (checkboxes)
2. Click bulk actions dropdown
3. Select **"Approve Selected"**
4. Confirm action
5. Check results

**Expected Results:**
✅ Checkboxes appear on each row  
✅ "Select all" checkbox in header works  
✅ Bulk actions button appears when items selected  
✅ "Approve Selected" option visible (green, check icon)  
✅ **Confirmation modal appears CENTERED on screen:**
- Heading: "Approve Data Points"
- Description: "Are you sure you want to approve the selected data points?"
- Submit button: "Yes, approve them"
- Modal has dark backdrop overlay
- Modal is centered horizontally and vertically
✅ After confirming:
- Modal closes
- All selected items change to "Approved"
- Dashboard stats update
- **Success notification appears CENTERED on screen** (green toast): "Data points approved! X data points have been approved."
- Proper singular/plural grammar ("1 data point has" vs "3 data points have")
- Notification auto-dismisses after 3 seconds
- Notification has enhanced shadow for visibility

---

### Test: Bulk Reject

**Steps:**
1. Select multiple pending data points
2. Click bulk actions dropdown
3. Select **"Reject Selected"**
4. Confirm action

**Expected Results:**
✅ "Reject Selected" option visible (red, X icon)  
✅ **Confirmation modal appears CENTERED on screen:**
- Heading: "Reject Data Points"
- Description: "Are you sure you want to reject the selected data points?"
- Submit button: "Yes, reject them"
- Modal has dark backdrop overlay
- Modal is centered horizontally and vertically
✅ After confirming:
- Modal closes
- All selected items change to "Rejected"
- Dashboard stats update
- **Danger notification appears CENTERED on screen** (red toast): "Data points rejected! X data points have been rejected."
- Proper singular/plural grammar
- Notification auto-dismisses after 3 seconds
- Notification has enhanced shadow for visibility

---

### Test: Other Bulk Actions

**Available Bulk Actions:**
✅ Delete (soft delete)  
✅ Force Delete (permanent delete)  
✅ Restore (restore soft-deleted items)

**Verify:**
- Confirmation dialogs appear
- Actions work correctly
- Appropriate permissions required

---

## 5. Filtering & Search (1 minute)

### Test: Status Filter

**Steps:**
1. Click "Filters" button
2. Open "Status" filter
3. Select multiple statuses
4. Apply filter

**Expected Results:**
✅ Filter panel opens  
✅ Status filter shows 4 options:
- Draft
- Pending Review
- Approved
- Rejected
✅ Multi-select (can choose multiple)  
✅ Not native select (custom Filament dropdown)  
✅ "Status" indicator badge appears when filtered  
✅ Table updates to show only selected statuses

---

### Test: Campaign Filter

**Steps:**
1. Open "Campaign" filter
2. Search for campaign name
3. Select campaign
4. Apply filter

**Expected Results:**
✅ Campaign filter is searchable dropdown  
✅ Shows all campaigns (preloaded)  
✅ Not native select  
✅ "Campaign" indicator badge appears  
✅ Table shows only data points from selected campaign

---

### Test: Metric Filter

**Steps:**
1. Open "Metric" filter
2. Search for metric
3. Select metric
4. Apply filter

**Expected Results:**
✅ Metric filter is searchable dropdown  
✅ Shows all environmental metrics (preloaded)  
✅ Not native select  
✅ "Metric" indicator badge appears  
✅ Table filters correctly

---

### Test: GPS Accuracy Filter

**Steps:**
1. Open "GPS Accuracy" filter
2. Select quality level
3. Apply filter

**Expected Results:**
✅ Three options available:
- Excellent (<10m)
- Good (10-20m)
- Poor (>20m)
✅ Not native select  
✅ "Accuracy" indicator badge appears  
✅ Table shows only matching accuracy range

**Validation:**
- Excellent: Only shows accuracy <10m
- Good: Only shows accuracy 10-20m
- Poor: Only shows accuracy >20m

---

### Test: Trashed Filter

**Steps:**
1. Open "Trashed" filter
2. Select "Only trashed" or "With trashed"
3. Apply filter

**Expected Results:**
✅ Trashed filter works  
✅ Shows soft-deleted items when selected  
✅ Can restore from this view

---

### Test: Combined Filters

**Steps:**
1. Apply Status: "Pending"
2. Apply GPS Accuracy: "Excellent"
3. Apply Campaign: Select one
4. Review results

**Expected Results:**
✅ All filters work together (AND logic)  
✅ Only shows pending items with excellent GPS from selected campaign  
✅ Multiple indicator badges visible  
✅ Clear filters button works

---

### Test: Search Functionality

**Searchable Columns:**
- Status
- Campaign name
- Metric name
- Submitted by user name

**Steps:**
1. Enter search term in search box
2. Press Enter or wait for auto-search

**Expected Results:**
✅ Table filters by search term  
✅ Searches across searchable columns  
✅ Real-time or debounced search  
✅ Clear search button works

---

## 6. Column Management

### Test: Toggle Columns

**Steps:**
1. Click column manager button (grid icon)
2. Toggle various columns on/off
3. Check table updates

**Expected Results:**
✅ Column manager modal opens  
✅ All 12 columns listed with toggles  
✅ Default hidden columns indicated:
- ID (hidden by default)
- GPS Coordinates (hidden by default)
- Photo (hidden by default)
- Submitted By (hidden by default)
- Collected date (hidden by default)
- Notes (hidden by default)
- Submitted date (hidden by default)
✅ Toggling columns updates table immediately  
✅ Settings persist during session  
✅ Close button works
✅ Table has no horizontal scrollbar with default columns (only 5 visible)

---

## 7. Sorting

### Test: Sort by Different Columns

**Sortable Columns:**
- ID, Status, Campaign, Metric, Value
- GPS Accuracy, Collected date, Submitted date

**Steps:**
1. Click column header to sort
2. Click again to reverse sort
3. Check sort indicator (arrow icon)

**Expected Results:**
✅ Click once: Ascending sort (↑)  
✅ Click twice: Descending sort (↓)  
✅ Click third time: Remove sort  
✅ Data sorts correctly  
✅ Sort indicator visible on active column

**Default Sort:**
✅ Created_at descending (newest first)

---

## Edge Cases & Error Handling

### Test: Empty State

**Steps:**
1. Apply filters that return no results
2. Check empty state message

**Expected Results:**
✅ "No data points found" message  
✅ Clear filters suggestion  
✅ No errors

---

### Test: No Pending Items

**Steps:**
1. Approve all pending items
2. Check dashboard
3. Check "Review Data Points" badge

**Expected Results:**
✅ Pending count shows "0"  
✅ Navigation badge disappears or shows "0"  
✅ Table can still load all items  
✅ No errors

---

### Test: Concurrent Approval

**Steps:**
1. Open data point in two browser tabs
2. Approve in first tab
3. Try to approve in second tab

**Expected Results:**
✅ Second approval attempt should work (already approved)  
✅ No error thrown  
✅ Status already "Approved" message or silent success

---

## Testing Completion Checklist

After completing all tests, verify:

- [ ] QA dashboard widget displays correctly
- [ ] All 6 statistics show accurate counts
- [ ] Approval rate calculation correct
- [ ] Pending trend chart shows 7 days
- [ ] Navigation badge shows pending count
- [ ] Data points table loads with all columns
- [ ] GPS accuracy color coding works (<10m green, 10-20m yellow, >20m red)
- [ ] Status badges color-coded correctly
- [ ] Approve button works (green, confirmation required)
- [ ] Reject button works (red, confirmation required)
- [ ] Edit button opens form
- [ ] Bulk approve works with confirmation
- [ ] Bulk reject works with confirmation
- [ ] Status filter (multi-select)
- [ ] Campaign filter (searchable)
- [ ] Metric filter (searchable)
- [ ] GPS accuracy filter (excellent/good/poor)
- [ ] Trashed filter works
- [ ] Combined filters work (AND logic)
- [ ] Search functionality works
- [ ] Column manager toggles columns
- [ ] Sorting works on all sortable columns
- [ ] Default sort is newest first
- [ ] Pending count updates after approval/rejection
- [ ] Dashboard stats update in real-time
- [ ] No JavaScript errors
- [ ] No console warnings
- [ ] Action button visibility logic correct
- [ ] Empty states handled gracefully

---

## Automated Test Verification

### Run Automated Tests

**Steps:**
```powershell
# Run Phase 8 widget tests (when created)
ddev artisan test tests/Feature/Widgets/QualityAssuranceStatsWidgetTest.php

# Run data point tests
ddev artisan test tests/Feature/DataPointApprovalTest.php

# Or run all admin tests
ddev artisan test --filter=Admin
```

**Expected Results:**
✅ All tests passing  
✅ No failures

---

## Known Limitations (Not Bugs)

**Current Limitations:**
- No automated quality checks (GPS threshold auto-reject)
- No multi-level approval workflow
- No comments/feedback on rejections
- No audit log for status changes
- No email notifications for approvals/rejections
- No rejection reason tracking

**Future Enhancements (Deferred):**
- Automated outlier detection
- Rejection reason dropdown
- Reviewer comments
- Approval history timeline
- Email notifications
- QA performance reports
- Reviewer analytics

---

## Troubleshooting

### Dashboard Stats Not Updating

**Check:**
1. Hard refresh page (Ctrl+Shift+R)
2. Check browser console for errors
3. Verify database connection

**Solution:**
```powershell
# Clear cache
ddev artisan cache:clear

# Check queue is running (if stats are queued)
ddev artisan queue:work
```

---

### Approve/Reject Buttons Not Working

**Possible Causes:**
- JavaScript error
- Missing permissions
- Database connection issue

**Check:**
1. Browser console for errors
2. Network tab for failed requests
3. User has admin permissions

---

### Filters Not Applying

**Check:**
1. Filter indicator badges visible
2. Filter panel closes after applying
3. Table actually filters (check row count)

**Solution:**
```powershell
# Clear Filament cache
ddev artisan filament:clear-cached-components
```

---

### GPS Coordinates Show "N/A"

**Possible Causes:**
- PostGIS query failing
- Location data NULL in database

**Expected:**
- If location is NULL, "N/A" is correct behavior
- If location exists but shows N/A, check PostGIS extension

---

## Notes for Developers

**If Issues Found During Testing:**

1. **Check Filament version:**
   ```powershell
   ddev composer show filament/filament
   ```

2. **Verify resource registration:**
   ```powershell
   ddev artisan filament:list
   ```

3. **Check widget registration:**
   ```
   app/Providers/Filament/AdminPanelProvider.php
   ```

4. **Clear caches:**
   ```powershell
   ddev artisan cache:clear
   ddev artisan config:clear
   ddev artisan view:clear
   ```

5. **Check logs:**
   ```
   storage/logs/laravel.log
   ```

---

## User Guide Reference

User guide to be created: **Admin Panel Guide** (documentation pending)

---

## Success Criteria

**Phase 8 is COMPLETE ✅** (Tested and Approved: January 19, 2026)

**All criteria met:**
- ✅ QA dashboard displays all 6 statistics correctly
- ✅ Data point review table loads correctly with proper column layout
- ✅ Approve/reject workflow functions perfectly
- ✅ Bulk operations work with centered confirmation modals
- ✅ All filters apply correctly (status, campaign, metric, GPS accuracy)
- ✅ GPS accuracy color coding works (<10m green, 10-20m yellow, >20m red)
- ✅ Column management functions (toggle 12 columns)
- ✅ Sorting works on all sortable columns
- ✅ No errors or crashes
- ✅ Dashboard stats update after actions
- ✅ Photo upload and preview working correctly
- ✅ Centered success notifications with enhanced shadows
- ✅ User guide created (Phase 8 testing cookbook)

---

**Phase 8 Status: ✅ TESTED AND APPROVED**

**Completion Date:** January 19, 2026  
**Total Testing Time:** 8-10 minutes  
**All Features:** Working as expected  
**Known Issues:** None

**Next Steps:** Ready for production deployment

**Last Updated:** January 19, 2026
