# Stripe Webhook Setup

## Get Webhook Secret

1. Go to: https://dashboard.stripe.com
2. Navigate: **Developers** → **Webhooks**
3. Click: **Add endpoint**
4. Enter URL: `https://laravel-ecosurvey.ddev.site/stripe/webhook`
5. Select events:
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
6. Save endpoint
7. Click **Reveal** in "Signing secret" section
8. Copy secret (starts with `whsec_...`)
9. Add to `.env`:
   ```
   STRIPE_WEBHOOK_SECRET=whsec_your_secret_here
   ```

## Local Testing (Alternative)

Use Stripe CLI:
```bash
stripe listen --forward-to https://laravel-ecosurvey.ddev.site/stripe/webhook
```

Generates temporary webhook secret for local development.

## Production Setup

1. Switch to **Live mode** in Stripe Dashboard (toggle in top-left)
2. Repeat webhook setup with production URL: `https://yourdomain.com/stripe/webhook`
3. Update `.env` with Live mode secret:
   ```
   STRIPE_KEY=pk_live_...
   STRIPE_SECRET=sk_live_...
   STRIPE_WEBHOOK_SECRET=whsec_live_...
   ```

## Notes

- Webhook route `/stripe/webhook` auto-registered by Laravel Cashier
- Test mode and Live mode have separate webhook secrets
- Use Test mode for development
