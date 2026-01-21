# Stripe Product Setup

## Create Products in Stripe Dashboard

### 1. Log in to Stripe Dashboard
Go to: https://dashboard.stripe.com (use Test mode)

### 2. Create Pro Product

1. Navigate: **Products** → **Add product**
2. Fill in:
   - **Name:** EcoSurvey Pro
   - **Description:** Professional tier with enhanced features
   - **Pricing model:** Standard pricing
   - **Price:** $29.00
   - **Billing period:** Monthly
3. Click **Add product**
4. Copy the **Price ID** (starts with `price_...`)
5. Add to `.env`:
   ```
   STRIPE_PRICE_PRO=price_xxxxxxxxxxxxx
   ```

### 3. Create Enterprise Product

1. Click **Add product**
2. Fill in:
   - **Name:** EcoSurvey Enterprise
   - **Description:** Enterprise tier with unlimited features
   - **Pricing model:** Standard pricing
   - **Price:** $99.00
   - **Billing period:** Monthly
3. Click **Add product**
4. Copy the **Price ID** (starts with `price_...`)
5. Add to `.env`:
   ```
   STRIPE_PRICE_ENTERPRISE=price_xxxxxxxxxxxxx
   ```

### 4. Verify Products

1. Go to **Products** in dashboard
2. Should see:
   - ✅ EcoSurvey Pro - $29.00/month
   - ✅ EcoSurvey Enterprise - $99.00/month

## Product Features Reference

### Free Tier (No Stripe Product Needed)
- **Price:** $0
- **Limits:**
  - 50 data points/month
  - 10 satellite analyses/month
  - 2 report exports/month
- **Features:**
  - Basic maps
  - Limited satellite data
  - Community support

### Pro Tier
- **Price:** $29/month
- **Stripe Price ID:** `STRIPE_PRICE_PRO`
- **Limits:**
  - 500 data points/month
  - 100 satellite analyses/month
  - 20 report exports/month
- **Features:**
  - All maps and visualization
  - Full satellite indices (7)
  - Advanced analytics
  - Priority support
  - Export to CSV/PDF

### Enterprise Tier
- **Price:** $99/month
- **Stripe Price ID:** `STRIPE_PRICE_ENTERPRISE`
- **Limits:**
  - Unlimited data points
  - Unlimited satellite analyses
  - Unlimited report exports
- **Features:**
  - Unlimited everything
  - API access
  - White-label option
  - Custom integrations
  - SLA guarantee
  - Dedicated support

## Production Setup

When going live:

1. Switch to **Live mode** in Stripe Dashboard
2. Create the same products in Live mode
3. Update `.env` with Live mode price IDs
4. Verify webhook endpoints point to production URL

## Testing

Use Stripe test mode for development:
- All transactions are simulated
- Use test card: `4242 4242 4242 4242`
- No real money charged
- Test webhooks with Stripe CLI

## Configuration

Price IDs are read from:
- `.env` file → `STRIPE_PRICE_PRO`, `STRIPE_PRICE_ENTERPRISE`
- `config/subscriptions.php` → Uses `env()` to read values
- User model → `subscriptionTier()` method checks against config

## Troubleshooting

### Price ID not working?
- Verify ID starts with `price_`
- Check you're in correct mode (Test vs Live)
- Ensure no extra spaces in `.env`
- Clear config cache: `ddev artisan config:clear`

### Product not showing in checkout?
- Confirm product is active in Stripe
- Check price ID matches in `.env`
- Verify Stripe API keys are correct

---

**Last Updated:** January 21, 2026
