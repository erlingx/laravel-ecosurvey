# Subscription & Rate Limiting - Browser Testing Cookbook

**Last Updated:** January 22, 2026  
**Estimated Time:** 5-7 minutes  
**Prerequisites:** Logged in as user with Pro subscription (for cancellation tests)

**Testing Status:** ⏳ READY FOR TESTING

---

## Quick Test Checklist

- [ ] **Subscription Cancellation UI** (3 min)
- [ ] **Rate Limiting** (2 min)
- [ ] **Grace Period Handling** (2 min)

---

## 1. Subscription Cancellation UI (3 minutes)

### Test: Access Manage Subscription Page

**URL:** `/billing/manage`

**Steps:**
1. Navigate to `/billing/manage`
2. Review page sections

**Expected Results:**
✅ Page loads successfully  
✅ "Manage Subscription" heading visible  
✅ Current plan section displays  
✅ No JavaScript errors

---

### Test: Free Tier User View

**Prerequisites:** User on Free tier

**Steps:**
1. Login as free tier user
2. Go to `/billing/manage`

**Expected Results:**
✅ Shows "Free Plan"  
✅ "Upgrade Plan" button visible  
✅ NO "Cancel Subscription" button  
✅ NO "Subscription Actions" section  
✅ NO "Billing History" section

---

### Test: Pro/Enterprise User View

**Prerequisites:** User with active Pro or Enterprise subscription

**Steps:**
1. Login as subscribed user
2. Go to `/billing/manage`
3. Review all sections

**Expected Results:**

**Section 1: Current Plan**
✅ Shows plan name (e.g., "Pro Plan" or "Enterprise Plan")  
✅ Shows price (e.g., "$29/month" or "$99/month")  
✅ Shows status: "Active subscription" (green text)  
✅ "Change Plan" button visible

**Section 2: Subscription Actions**
✅ "Update Payment Method" button visible  
✅ "Cancel Subscription" button visible (red color)  
✅ Clear descriptions for each action

**Section 3: Billing History**
✅ "Billing History" heading visible  
✅ Invoice list displays (if any exist)  
✅ "Download PDF" links for each invoice

---

### Test: Cancel Subscription Flow

**Steps:**
1. Click red "Cancel" button
2. Review modal
3. Select cancellation option
4. Confirm cancellation

**Expected Results:**

**Step 1: Modal Opens**
✅ Modal appears centered on screen  
✅ Heading: "Cancel Subscription"  
✅ Subheading: "Choose how you'd like to cancel your subscription"  
✅ Dark backdrop overlay

**Step 2: Cancellation Options**
✅ Two radio options visible:
- "Cancel at end of billing period" (default selected)
  - Description: "Keep access until your current billing period ends"
- "Cancel immediately"
  - Description: "Cancel now and downgrade to Free tier"

**Step 3: Warning Box**
✅ Yellow warning box displays  
✅ Shows appropriate message based on selected option:
- End of period: "You'll continue to have access to your [Tier] features until the end of your current billing period."
- Immediately: "Your subscription will be cancelled immediately and you'll be downgraded to the Free tier right away."

**Step 4: Confirm Buttons**
✅ "Keep Subscription" button (ghost/secondary)  
✅ "Confirm Cancellation" button (red/danger)  
✅ Both buttons work correctly

---

### Test: Cancel at End of Period

**Steps:**
1. Open cancel modal
2. Select "Cancel at end of billing period"
3. Click "Confirm Cancellation"
4. Check results

**Expected Results:**
✅ Modal closes  
✅ Green success message: "Your subscription will be cancelled at the end of your billing period..."  
✅ Page refreshes  
✅ Status changes to: "Cancelled - Access until [date]" (orange text)  
✅ "Cancel Subscription" button REPLACED with "Resume Subscription" button (green)  
✅ Subscription still shows as Pro/Enterprise  
✅ User still has access to features

---

### Test: Cancel Immediately

**Steps:**
1. Open cancel modal
2. Select "Cancel immediately"
3. Click "Confirm Cancellation"
4. Check results

**Expected Results:**
✅ Modal closes  
✅ Green success message: "Your subscription has been cancelled immediately..."  
✅ Page refreshes  
✅ Plan changes to "Free Plan"  
✅ "Subscription Actions" section disappears  
✅ "Upgrade Plan" button appears  
✅ User immediately on Free tier

---

### Test: Resume Cancelled Subscription

**Prerequisites:** Subscription cancelled at end of period (grace period)

**Steps:**
1. Go to `/billing/manage`
2. Find "Resume Subscription" button
3. Click button
4. Check results

**Expected Results:**
✅ "Resume Subscription" button in orange box  
✅ Description: "Your subscription is set to cancel. Resume to continue access."  
✅ After clicking:
- Green success message: "Your subscription has been resumed!"
- Status returns to "Active subscription"
- "Cancel Subscription" button returns
- "Resume" button disappears

---

### Test: Update Payment Method

**Steps:**
1. Click "Update Payment Method" button
2. Check redirect

**Expected Results:**
✅ Redirects to Stripe Billing Portal  
✅ Stripe page loads in same tab  
✅ After updating, redirects back to `/billing/manage`

---

### Test: Invoice Viewing

**Prerequisites:** User has at least one invoice

**Steps:**
1. Scroll to "Billing History" section
2. Review invoice list
3. Click "Download PDF" link

**Expected Results:**
✅ Invoice list shows:
- Amount: "$29 - Jan 15, 2026" (or similar)
- Plan name: "Pro Plan"
- "Download PDF" link
✅ Clicking link opens PDF in new tab  
✅ PDF shows invoice details from Stripe

**If No Invoices:**
✅ Shows: "No invoices yet."

---

## 2. Rate Limiting (2 minutes)

### Test: Free Tier Rate Limit (60/hour)

**Prerequisites:** Free tier user

**Steps:**
1. Login as free tier user
2. Navigate to `/data-points/submit` repeatedly
3. Make 60 requests within 1 hour
4. Make 61st request

**Expected Results:**
✅ First 60 requests: Page loads successfully (200 status)  
✅ 61st request: Returns 429 error  
✅ Error message shows: "Too many requests. Please slow down."  
✅ Response includes `retry_after` value

**Quick Test (Manual):**
- Refresh `/data-points/submit` page 60 times rapidly
- 61st refresh should show error

---

### Test: Pro Tier Rate Limit (300/hour)

**Prerequisites:** Pro tier user

**Steps:**
1. Login as Pro tier user
2. Navigate to rate-limited route repeatedly
3. Verify higher limit

**Expected Results:**
✅ Can make more requests than free tier (300/hour)  
✅ First 100+ requests succeed  
✅ Eventually hits 429 after 300 requests

**Quick Test:**
- Refresh `/data-points/submit` 100 times
- Should still succeed (no 429)

---

### Test: Enterprise Tier Rate Limit (1000/hour)

**Prerequisites:** Enterprise tier user

**Steps:**
1. Login as Enterprise user
2. Make many requests to rate-limited route

**Expected Results:**
✅ Can make 1000 requests per hour  
✅ Much higher limit than Pro tier

---

### Test: Rate Limited Routes

**These routes ARE rate limited:**

**Data Collection:**
- `/data-points/submit` ✅
- `/data-points/{id}/edit` ✅

**Maps:**
- `/maps/survey` ✅
- `/maps/satellite` ✅

**Analytics:**
- `/analytics/heatmap` ✅
- `/analytics/trends` ✅

**Exports:**
- `/campaigns/{id}/export/json` ✅
- `/campaigns/{id}/export/csv` ✅
- `/campaigns/{id}/export/pdf` ✅

**Steps:**
1. Try accessing each route multiple times
2. Verify rate limiting applies

**Expected Results:**
✅ All listed routes enforce rate limits  
✅ 429 response after limit exceeded  
✅ Error message consistent

---

### Test: Non-Rate Limited Routes

**These routes are NOT rate limited:**

- `/campaigns` (campaign list)
- `/billing/plans`
- `/billing/manage`
- `/settings/profile`
- `/dashboard`

**Steps:**
1. Refresh these pages many times
2. Verify NO rate limiting

**Expected Results:**
✅ Can refresh unlimited times  
✅ Never get 429 error  
✅ Pages always load

---

### Test: 429 Error Response

**Steps:**
1. Exhaust rate limit (make 61 requests as free tier)
2. Check error response
3. Review error format

**Expected Results:**
✅ Status code: 429  
✅ Response body (JSON):
```json
{
  "message": "Too many requests. Please slow down.",
  "retry_after": 3540
}
```
✅ `retry_after` shows seconds until reset  
✅ Error message user-friendly

---

### Test: Rate Limit Reset

**Steps:**
1. Hit rate limit (get 429 error)
2. Wait 1 hour
3. Try request again

**Expected Results:**
✅ After 1 hour, limit resets  
✅ Can make requests again  
✅ New 60/300/1000 hour window starts

**Quick Test:**
- Not practical to wait 1 hour
- Verify `retry_after` value decreases over time
- Trust automated tests for full reset

---

### Test: Independent User Limits

**Steps:**
1. Login as User A (free tier)
2. Exhaust rate limit (60 requests)
3. Logout
4. Login as User B (free tier)
5. Try making request

**Expected Results:**
✅ User A hits 429 after 60 requests  
✅ User B can still make requests  
✅ Limits are per-user, not global  
✅ Users don't affect each other

---

## 3. Grace Period Handling (2 minutes)

### Test: Grace Period Status Display

**Prerequisites:** Subscription cancelled at end of period

**Steps:**
1. Cancel subscription (end of period)
2. Go to `/billing/manage`
3. Review status display

**Expected Results:**
✅ Status shows: "Cancelled - Access until [date]" (orange text)  
✅ Date shows end of current billing period  
✅ "Resume Subscription" button visible in orange box  
✅ Still shows Pro/Enterprise plan name  
✅ Still shows price  
✅ "Change Plan" button still visible

---

### Test: Grace Period Feature Access

**Prerequisites:** Subscription on grace period

**Steps:**
1. Try to access Pro/Enterprise features
2. Check usage limits
3. Verify full access

**Expected Results:**
✅ User still has Pro/Enterprise tier access  
✅ Usage limits still Pro/Enterprise levels  
✅ Rate limits still Pro/Enterprise (300/1000)  
✅ All features still available  
✅ Dashboard shows Pro/Enterprise tier

---

### Test: Grace Period End

**Prerequisites:** Grace period expires

**Steps:**
1. Wait for grace period end date to pass
2. Login and check access

**Expected Results:**
✅ User automatically downgraded to Free tier  
✅ `/billing/manage` shows "Free Plan"  
✅ Usage limits reset to Free tier  
✅ Rate limit drops to 60/hour  
✅ "Resume" button disappears  
✅ "Upgrade Plan" button appears

**Note:** Cannot easily test without waiting for billing cycle. Trust automated tests.

---

## Edge Cases & Error Handling

### Test: Already Cancelled Subscription

**Steps:**
1. Cancel subscription
2. Try to cancel again

**Expected Results:**
✅ "Cancel Subscription" button replaced with "Resume"  
✅ Cannot cancel twice  
✅ No errors

---

### Test: Free Tier Access to Manage Page

**Steps:**
1. Login as free tier user
2. Go to `/billing/manage`

**Expected Results:**
✅ Page loads normally  
✅ Shows Free tier information  
✅ No cancel/resume buttons  
✅ Just "Upgrade Plan" button

---

### Test: Rapid Requests (Rate Limit Stress)

**Steps:**
1. Open browser console
2. Run script to make 100 requests rapidly
3. Check responses

**Expected:**
✅ First 60 succeed (free tier)  
✅ Remaining 40 return 429  
✅ No server errors  
✅ No crashes  
✅ Consistent error messages

---

## Testing Completion Checklist

After completing all tests, verify:

### Subscription Cancellation
- [ ] Free tier users see upgrade button only
- [ ] Pro/Enterprise users see cancel button
- [ ] Cancel modal opens with 2 options
- [ ] "Cancel at end of period" sets grace period
- [ ] "Cancel immediately" downgrades to Free
- [ ] Resume button appears for grace period
- [ ] Resume button restores subscription
- [ ] Update payment redirects to Stripe
- [ ] Invoices display and download
- [ ] Success messages appear
- [ ] No JavaScript errors

### Rate Limiting
- [ ] Free tier limited to 60/hour
- [ ] Pro tier limited to 300/hour
- [ ] Enterprise tier limited to 1000/hour
- [ ] Rate limited routes return 429
- [ ] Non-rate limited routes work unlimited
- [ ] 429 response includes retry_after
- [ ] Error message user-friendly
- [ ] Limits are per-user
- [ ] Limits reset after 1 hour
- [ ] No server errors

### Grace Period
- [ ] Status shows "Cancelled - Access until [date]"
- [ ] Resume button appears
- [ ] User retains tier features
- [ ] Usage limits unchanged
- [ ] After expiry, downgrade to Free

---

## Known Limitations

**Subscription Cancellation:**
- No refund information shown
- No cancellation survey/feedback
- No retention offers
- No email confirmation sent

**Rate Limiting:**
- No user-facing rate limit display
- No warning before hitting limit
- No ability to purchase extra quota
- Fixed 1-hour window (not configurable per user)

---

## Success Criteria

**Features are COMPLETE when:**
- ✅ All subscription cancellation flows work
- ✅ Grace period handled correctly
- ✅ Rate limits enforce properly
- ✅ All automated tests pass (16/16)
- ✅ No errors or crashes
- ✅ User experience smooth

---

**Status:** ⏳ READY FOR TESTING  
**Estimated Testing Time:** 5-7 minutes  
**Automation:** 16 Pest tests passing  
**Next Steps:** Browser test, then production deployment

---

**Last Updated:** January 22, 2026
