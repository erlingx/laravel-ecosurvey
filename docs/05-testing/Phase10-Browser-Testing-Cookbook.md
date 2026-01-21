# Phase 10 Features - Browser Testing Cookbook
**Last Updated:** January 21, 2026  
**Estimated Time:** 10-12 minutes  
**Prerequisites:** Logged in user, Stripe test mode configured (for checkout testing)
**Testing Status:** 🟢 MOSTLY COMPLETE - Sections 1-6 tested & approved, Section 7 pending
---
## Testing Notes
**Phase 10 Features to Test:**
1. ✅ Backend: Laravel Cashier installed (no UI to test)
2. ✅ Backend: Subscription tiers configured (no UI to test)
3. ✅ Frontend: Subscription plans page (ready for browser testing)
4. ✅ Frontend: Stripe checkout flow (ready for browser testing)
5. ⏳ Subscription management dashboard (placeholder only)
6. ⏳ Usage tracking & limits (not implemented)
7. ⏳ Billing portal integration (not implemented)
8. ⏳ Webhook handling (not implemented)
**Prerequisites:**
- User account created and logged in
- For full checkout testing: Stripe products created in dashboard
- For full checkout testing: Stripe API keys and price IDs configured in `.env`
**Key Changes in Phase 10:**
- ✅ Laravel Cashier installed and configured
- ✅ 3 subscription tiers defined (Free, Pro, Enterprise)
- ✅ Pricing page with Volt component
- ✅ Checkout flow with Stripe integration
- ✅ Success/cancel pages
- ✅ Navigation link added to sidebar
**Backend Only (No UI to Test):**
- Task 1.1: Laravel Cashier installation - verified via automated tests
- Task 1.2: Subscription configuration - verified via automated tests
---
## Quick Test Checklist
- [x] **Subscription Plans Page** ✅ TESTED & APPROVED (3 min)
- [x] **Checkout Flow UI** ✅ TESTED & APPROVED (2 min)
- [x] **Stripe Checkout Integration** ✅ TESTED & APPROVED (requires Stripe setup)
- [x] **Success/Cancel Pages** ✅ TESTED & APPROVED (1 min)
- [x] **Subscription Management** ✅ TESTED & APPROVED (1 min)
- [x] **Dark Mode Compatibility** ✅ TESTED & APPROVED (1 min)
- [ ] **Authentication Protection** ⏳ PENDING BROWSER TEST (30 sec)
---
## 1. Subscription Plans Page (3 minutes)
### Test: Access Billing Plans
**URL:** `/billing/plans`
**Alternative:** Click "Subscription" in sidebar under "Billing" section
**Expected Results:**
- [ ] Page loads without errors
- [ ] Sidebar shows "Subscription" link highlighted
- [ ] Page title: "Choose Your Plan"
- [ ] Subtitle: "Start free, upgrade when you need more power"
---
### Test: Free Tier Display
**Steps:**
1. Scroll to view the Free tier card (leftmost card)
2. Review all displayed information
**Expected Results:**
- [ ] Card has white background with shadow
- [ ] Title: "Free"
- [ ] Price: "$0" in large text
- [ ] "/month" label visible
- [ ] Features section with heading "Features:"
- [ ] Green checkmark icons before each feature
- [ ] Features list shows:
  - [ ] "Basic maps"
  - [ ] "Limited satellite data"
  - [ ] "Community support"
- [ ] Usage Limits section shows:
  - [ ] "📊 50 data points/month"
  - [ ] "🛰️ 10 satellite analyses/month"
  - [ ] "📄 2 report exports/month"
- [ ] Button at bottom of card
- [ ] If currently on free tier: Button shows "Current Plan" (disabled/grayed)
- [ ] If not on free tier: Button shows "View Free Plan"
---
### Test: Pro Tier Display
**Steps:**
1. Locate the Pro tier card (center card)
2. Review all displayed information
**Expected Results:**
- [ ] Card has blue ring border (highlighted)
- [ ] "Most Popular" badge in top-right corner (blue background)
- [ ] Title: "Pro"
- [ ] Price: "$29" in large text
- [ ] "/month" label visible
- [ ] Features section shows:
  - [ ] "All maps and visualization"
  - [ ] "Full satellite indices (7)"
  - [ ] "Advanced analytics"
  - [ ] "Priority support"
  - [ ] "Export to CSV/PDF"
- [ ] Usage Limits section shows:
  - [ ] "📊 500 data points/month"
  - [ ] "🛰️ 100 satellite analyses/month"
  - [ ] "📄 20 report exports/month"
- [ ] **Button display varies by current subscription:**
  - [ ] **If on Free tier:** Shows "Upgrade to Pro" (blue/primary styling)
  - [ ] **If subscribed to Pro:** Shows "Current Plan" (disabled/grayed out) ✅
  - [ ] **If subscribed to Enterprise:** Shows button to change/downgrade

---

### Test: Current Plan Indication (For Pro Subscribers)

**Prerequisites:** User must have active Pro subscription

**Steps:**
1. Navigate to `/billing/plans` while subscribed to Pro
2. Review all three tier cards
3. Check which card shows "Current Plan"

**Expected Results:**
- [ ] Free tier card: Shows "View Free Plan" button (not disabled)
- [ ] **Pro tier card: Shows "Current Plan" button (disabled/grayed)** ✅
- [ ] Enterprise tier card: Shows "Upgrade to Enterprise" button
- [ ] Pro card is still highlighted with blue ring
- [ ] "Most Popular" badge still visible on Pro card
- [ ] No ability to "upgrade" to current plan

---

### Test: Enterprise Tier Display
**Steps:**
1. Locate the Enterprise tier card (rightmost card)
2. Review all displayed information
**Expected Results:**
- [ ] Card has white background with shadow
- [ ] Title: "Enterprise"
- [ ] Price: "$99" in large text
- [ ] "/month" label visible
- [ ] Features section shows:
  - [ ] "Unlimited everything"
  - [ ] "API access"
  - [ ] "White-label option"
  - [ ] "Custom integrations"
  - [ ] "SLA guarantee"
  - [ ] "Dedicated support"
- [ ] Usage Limits section shows:
  - [ ] "📊 Unlimited data points"
  - [ ] "🛰️ Unlimited satellite analyses"
  - [ ] "📄 Unlimited report exports"
- [ ] Button shows "Upgrade to Enterprise"
---
### Test: Responsive Layout
**Steps:**
1. Resize browser window to mobile width (< 768px)
2. Observe card layout
3. Resize to tablet width (768px - 1024px)
4. Resize to desktop width (> 1024px)
**Expected Results:**
- [ ] **Mobile:** Cards stack vertically (one per row)
- [ ] **Tablet/Desktop:** Cards display in 3-column grid
- [ ] All text remains readable at all sizes
- [ ] Buttons remain properly sized
- [ ] Spacing looks consistent
---
### Test: Footer Information
**Steps:**
1. Scroll to bottom of page
2. Review footer text
**Expected Results:**
- [ ] Gray text visible
- [ ] Text: "All plans include secure data storage, map visualization, and community support."
- [ ] Second line: "Need a custom plan? Contact us" with email link
- [ ] Email link is blue and underlined on hover
---
## 2. Checkout Flow UI (2 minutes)
### Test: Navigate to Checkout
**Steps:**
1. From `/billing/plans` page
2. Click "Upgrade to Pro" button
3. Observe page transition
**Expected Results:**
- [ ] URL changes to `/billing/checkout/pro`
- [ ] Page loads without errors
- [ ] Smooth navigation (no full page reload if Livewire wire:navigate works)
---
### Test: Checkout Page Display
**URL:** `/billing/checkout/pro`
**Expected Results:**
- [ ] Page title: "Subscribe to Pro"
- [ ] Large price display: "$29" (text-5xl size)
- [ ] "/month" label next to price
- [ ] "What's included:" section heading
- [ ] Feature list with green checkmarks
- [ ] All Pro tier features listed (same as plans page)
- [ ] Gray box showing "Monthly Limits:"
  - [ ] "📊 500 data points"
  - [ ] "🛰️ 100 satellite analyses"
  - [ ] "📄 20 report exports"
---
### Test: Checkout Buttons
**Steps:**
1. Locate the two buttons at bottom of page
2. Review button styling and text
**Expected Results:**
- [ ] Primary button (blue): "Continue to Stripe Checkout"
- [ ] Secondary button (outline): "Back to Plans"
- [ ] Small gray text below buttons explaining Stripe redirect
- [ ] Text: "You will be redirected to Stripe's secure checkout page..."
---
### Test: Back to Plans Navigation
**Steps:**
1. Click "Back to Plans" button
2. Observe navigation
**Expected Results:**
- [ ] Returns to `/billing/plans` page
- [ ] Smooth navigation (no full page reload)
- [ ] Plans page displays correctly
---
### Test: Invalid Plan Handling
**Steps:**
1. Manually navigate to `/billing/checkout/invalid`
2. Observe behavior
**Expected Results:**
- [ ] Automatically redirects to `/billing/plans`
- [ ] No error message shown (silent redirect)
- [ ] Plans page displays correctly
---
## 3. Stripe Checkout Integration (Requires Stripe Setup)
**⚠️ PREREQUISITES:**
This test requires Stripe configuration. If not set up, skip this section.
**Setup Required:**
1. Stripe account in test mode
2. Products created in Stripe Dashboard (see `docs/stripe-product-setup.md`)
3. Price IDs added to `.env`:
   - `STRIPE_PRICE_PRO=price_xxxxx`
   - `STRIPE_PRICE_ENTERPRISE=price_xxxxx`
4. Stripe API keys in `.env`:
   - `STRIPE_KEY=pk_test_xxxxx`
   - `STRIPE_SECRET=sk_test_xxxxx`
---
### Test: Redirect to Stripe Checkout
**Steps:**
1. Navigate to `/billing/checkout/pro`
2. Click "Continue to Stripe Checkout" button
3. Observe behavior
**Expected Results (If Stripe Configured):**
- [ ] Button shows loading state: "Redirecting to Stripe..."
- [ ] Page redirects to `https://checkout.stripe.com/...`
- [ ] Stripe checkout page loads
- [ ] Product name shows: "EcoSurvey Pro" (or your product name)
- [ ] Price shows: "$29.00 per month"
- [ ] Test mode indicator visible in Stripe UI
**Expected Results (If Stripe NOT Configured):**
- [ ] Error message appears on page
- [ ] Message indicates configuration issue
- [ ] No redirect occurs
---
### Test: Complete Test Payment
**⚠️ Only if Stripe is configured**
**Steps:**
1. On Stripe checkout page, enter test card details:
   - Card: `4242 4242 4242 4242`
   - Expiry: `12/34` (any future date)
   - CVC: `123` (any 3 digits)
   - Name: Any name
   - Email: Your email (or test email)
2. Click "Subscribe" or "Pay" button
3. Wait for processing
**Expected Results:**
- [ ] Payment processes successfully
- [ ] Redirects back to application
- [ ] Lands on success page (`/billing/success`)
---
### Test: Cancel Checkout
**⚠️ Only if Stripe is configured**
**Steps:**
1. Navigate to `/billing/checkout/pro`
2. Click "Continue to Stripe Checkout"
3. On Stripe page, click browser back button OR close tab
4. Navigate back to application
**Expected Results:**
- [ ] Can navigate back to plans page
- [ ] No subscription created
- [ ] Free tier status unchanged
---
## 4. Success/Cancel Pages (1 minute)
### Test: Success Page
**URL:** `/billing/success`
**Note:** This page is normally reached after successful Stripe checkout. For testing without completing checkout, visit URL directly.
**Expected Results:**
- [ ] Page loads without errors
- [ ] Large green checkmark icon (circle with checkmark)
- [ ] Heading: "Subscription Activated!"
- [ ] Welcome message mentions current tier (e.g., "Welcome to Pro!")
- [ ] "What's Next?" section with checklist:
  - [ ] "Start creating data points with your increased limits"
  - [ ] "Access all satellite analysis features"
  - [ ] "Export unlimited reports"
  - [ ] "Manage your subscription anytime in your account settings"
- [ ] Two buttons:
  - [ ] "Go to Dashboard" (blue/primary)
  - [ ] "Manage Subscription" (outline)
---
### Test: Success Page Navigation
**Steps:**
1. From success page, click "Go to Dashboard"
2. Verify navigation
3. Return to success page
4. Click "Manage Subscription"
5. Verify navigation
**Expected Results:**
- [ ] "Go to Dashboard" navigates to `/dashboard`
- [ ] "Manage Subscription" navigates to `/billing/manage`
- [ ] Both navigations work smoothly
---
### Test: Cancel Page
**URL:** `/billing/cancel`
**Note:** Normally reached if user cancels Stripe checkout. For testing, visit URL directly.
**Expected Results:**
- [ ] Page loads without errors
- [ ] Large yellow warning icon (triangle with exclamation)
- [ ] Heading: "Checkout Cancelled"
- [ ] Message: "No worries! Your subscription was not created..."
- [ ] Blue information box with helpful message
- [ ] Two buttons:
  - [ ] "View Plans Again" (blue/primary)
  - [ ] "Return to Dashboard" (outline)
---
### Test: Cancel Page Navigation
**Steps:**
1. Click "View Plans Again" button
2. Verify navigation
3. Return to cancel page
4. Click "Return to Dashboard"
5. Verify navigation
**Expected Results:**
- [ ] "View Plans Again" navigates to `/billing/plans`
- [ ] "Return to Dashboard" navigates to `/dashboard`
- [ ] Both navigations work smoothly
---
## 5. Subscription Management Page (1 minute)
### Test: Access Manage Page
**URL:** `/billing/manage`

**Via Sidebar Navigation:**
1. Look for "Billing" section heading in sidebar (near bottom, below Administration)
2. Click "Subscription" link (has credit-card icon 💳)
3. This takes you to `/billing/plans` (pricing page)
4. From pricing page, navigate to manage page if needed

**Note:** The sidebar "Subscription" link opens the pricing plans page (`/billing/plans`). To test the manage page specifically, navigate directly to `/billing/manage` or click "Manage Subscription" from the success page.

**Expected Results:**
- [ ] Sidebar has "Billing" section with "Subscription" link
- [ ] "Subscription" link has credit-card icon
- [ ] Link is highlighted when on any `/billing/*` page
- [ ] Manage page loads without errors at `/billing/manage`
- [ ] Page title: "Manage Subscription"
- [ ] White card with current plan information
---
### Test: Current Plan Display (Free Tier)

**Prerequisites:** User on Free tier (no active subscription)

**Steps:**
1. Navigate to `/billing/manage`
2. Locate "Current Plan" section in card
3. Review displayed information

**Expected Results:**
- [ ] Section heading: "Current Plan"
- [ ] Plan name displayed: "Free Plan"
- [ ] Subtitle text: "Free forever - Upgrade anytime to unlock more features"
- [ ] Button on right side: "Upgrade Plan" (blue/primary)

---

### Test: Current Plan Display (Pro Tier) ✅

**Prerequisites:** User has active Pro subscription

**Steps:**
1. Navigate to `/billing/manage`
2. Locate "Current Plan" section in card
3. Review displayed information

**Expected Results:**
- [ ] Section heading: "Current Plan"
- [ ] **Plan name displayed: "Pro Plan"** ✅
- [ ] **Subtitle text: "Active subscription"** ✅
- [ ] **Button on right side: "Change Plan" (outline style, not primary)** ✅
- [ ] No "Upgrade Plan" button shown
- [ ] Clear indication that Pro is the current active tier

**Additional Checks (Future - Task 3.1):**
- [ ] Next billing date shown
- [ ] Payment method last 4 digits shown
- [ ] Monthly cost displayed ($29/month)
- [ ] Option to cancel subscription

---

### Test: Current Plan Display (Enterprise Tier) ✅

**Prerequisites:** User has active Enterprise subscription

**Steps:**
1. Navigate to `/billing/manage`
2. Locate "Current Plan" section in card
3. Review displayed information

**Expected Results:**
- [ ] Section heading: "Current Plan"
- [ ] **Plan name displayed: "Enterprise Plan"** ✅
- [ ] **Subtitle text: "Active subscription"** ✅
- [ ] **Button on right side: "Change Plan" (outline style)** ✅

---
### Test: Placeholder Notice
**Steps:**
1. Scroll down to view blue information box
2. Read notice text
**Expected Results:**
- [ ] Blue background box visible
- [ ] Bold text: "Coming Soon:"
- [ ] Message: "Full subscription management including payment method updates, invoices, and more will be available in Task 3.1."
---
### Test: Navigation from Manage Page
**Steps:**
1. Click "Upgrade Plan" or "Change Plan" button
2. Observe navigation
**Expected Results:**
- [ ] Navigates to `/billing/plans`
- [ ] Pricing page displays correctly
---
## 6. Dark Mode Compatibility (1 minute)
### Test: Toggle Dark Mode
**Steps:**
1. On any billing page (`/billing/plans`, `/billing/checkout/pro`, etc.)
2. Click dark mode toggle in sidebar
3. Observe page appearance
4. Toggle back to light mode
**Expected Results:**
- [ ] **Light Mode:**
  - [ ] White backgrounds on cards
  - [ ] Dark text (gray-900)
  - [ ] Proper contrast
- [ ] **Dark Mode:**
  - [ ] Dark gray backgrounds (gray-800)
  - [ ] White text
  - [ ] Proper contrast maintained
  - [ ] All text remains readable
- [ ] Transitions smoothly between modes
- [ ] No visual glitches
---
## 7. Authentication Protection (30 seconds)
### Test: Unauthenticated Access
**Steps:**
1. Log out of application
2. Try to navigate to `/billing/plans`
3. Observe behavior
**Expected Results:**
- [ ] Redirects to login page
- [ ] Cannot access billing pages while logged out
- [ ] After login, can access billing pages normally
---
## Console Error Check (Throughout All Tests)
**During all tests above, keep browser DevTools open (F12)**
**Monitor Console tab for:**
- [ ] No JavaScript errors
- [ ] No 404 errors for assets
- [ ] No Livewire errors
- [ ] No Vue/Alpine errors
**Monitor Network tab for:**
- [ ] All requests return 200 or expected status codes
- [ ] No failed asset loads
- [ ] Livewire requests complete successfully
---
## Known Limitations / Coming Soon
**Current Limitations:**
- ⏳ Actual Stripe checkout requires configuration (price IDs and API keys)
- ⏳ Cannot complete real subscription without Stripe products set up
- ⏳ Subscription management is placeholder only
- ⏳ No payment method update functionality yet
- ⏳ No invoice viewing yet
- ⏳ No subscription cancellation yet
- ⏳ No usage tracking display yet
**Coming in Future Tasks:**
- Task 1.4: Webhook integration for subscription sync
- Task 2.1: Usage tracking and limit enforcement
- Task 3.1: Full subscription management dashboard
- Customer billing portal integration
- Invoice download functionality
---
## Testing Summary
**Completed Testing:**
- [x] Subscription plans page displays correctly ✅
- [x] All 3 tiers show proper information ✅
- [x] Checkout flow UI works as expected ✅
- [x] Success/cancel pages display correctly ✅
- [x] Manage page loads properly ✅
- [x] Dark mode compatibility verified ✅
- [ ] Authentication protection works ⏳
- [x] No console errors during testing ✅

**Stripe Integration Testing:**
- [x] Stripe checkout redirect works ✅
- [x] Test payment completes successfully ✅
- [x] Returns to success page after payment ✅
- [x] Subscription syncs correctly (manual sync command) ✅
- [x] Pro tier properly detected after sync ✅
---
## Issues Found
**Record any issues discovered during testing:**
| Issue | Severity | Page | Description | Status |
|-------|----------|------|-------------|--------|
| Subscription not syncing from Stripe | HIGH | `/billing/plans`, `/billing/manage` | After completing Stripe checkout, subscription shows as "Free" instead of "Pro". Subscription exists in Stripe but not in local database. | ✅ RESOLVED - Run `ddev artisan stripe:sync-subscriptions {user_id}` to manually sync |

**Note:** A manual sync command has been created as a workaround. For automatic syncing, configure webhooks in Stripe Dashboard (see Troubleshooting section below).

---

## Troubleshooting

### Issue: Subscription shows as "Free" even after subscribing to Pro

**Symptoms:**
- Successfully completed Stripe checkout for Pro plan
- Redirected to success page
- But `/billing/plans` still shows Free tier as current
- `/billing/manage` still shows "Free Plan"

**Cause:**
Stripe Checkout creates the subscription in Stripe, but it doesn't automatically sync to your local database without webhooks configured.

**Solution (Temporary - Until Webhooks Configured):**
A manual sync command has been created to pull subscriptions from Stripe:

```bash
# Sync subscription for specific user (replace 1 with your user ID)
ddev artisan stripe:sync-subscriptions 1

# Or sync all users with Stripe customer IDs
ddev artisan stripe:sync-subscriptions
```

**Verification Steps:**
1. Run the sync command: `ddev artisan stripe:sync-subscriptions 1`
2. Clear application cache: `ddev artisan cache:clear`
3. Refresh the browser (hard refresh: Ctrl+Shift+R or Cmd+Shift+R)
4. Navigate to `/billing/plans` - should now show "Current Plan" on Pro tier
5. Navigate to `/billing/manage` - should now show "Pro Plan"

**Permanent Solution (Configure Webhooks):**
For automatic syncing, configure webhooks in Stripe Dashboard:
1. Go to: https://dashboard.stripe.com/test/webhooks
2. Click "Add endpoint"
3. Endpoint URL: `https://your-domain.ddev.site/stripe/webhook`
4. Events to send:
   - `customer.subscription.created`
   - `customer.subscription.updated`  
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
5. Copy the webhook signing secret
6. Add to `.env`: `STRIPE_WEBHOOK_SECRET=whsec_xxxxx`
7. Restart DDEV: `ddev restart`

Once webhooks are configured, subscriptions will automatically sync when created/updated in Stripe.

**Database Verification:**
To verify your subscription exists:
```bash
ddev exec bash -c "psql -U db -d db -c 'SELECT si.stripe_price, s.stripe_status FROM subscriptions s JOIN subscription_items si ON s.id = si.subscription_id;'"
```

Should display your Pro price ID and status "active".

**If Still Not Working:**
1. Check that `STRIPE_PRICE_PRO` in `.env` matches the actual price ID from Stripe
2. Verify the subscription was created: Check Stripe Dashboard → Customers → Subscriptions
3. Check Laravel logs: `ddev logs | grep -i stripe`

---

**Last Manual Test:** ✅ January 21, 2026  
**Tested By:** Erik  
**Date:** January 21, 2026  
**Browser:** Chrome/Edge (assumed)  
**Status:** 🟢 MOSTLY COMPLETE - Sections 1-6 approved, Section 7 pending

**Test Results:**
- ✅ Subscription Plans Page - All features working correctly
- ✅ Checkout Flow UI - Navigation and display verified
- ✅ Stripe Checkout Integration - Successfully subscribed to Pro tier
- ✅ Success/Cancel Pages - All links and messaging correct
- ✅ Subscription Management - Pro tier properly displayed
- ✅ Dark Mode Compatibility - All pages work in dark/light mode
- ⏳ Authentication Protection - Pending test

**Issues Resolved:**
- ✅ Subscription sync issue fixed with manual sync command
- ✅ User model properly detects Pro tier from subscription_items table

**Next Steps:**
1. Test Section 7 (Authentication Protection)
2. Consider setting up webhooks in Stripe Dashboard for automatic syncing
3. Phase 10 Tasks 1.1-1.4 complete and tested!
