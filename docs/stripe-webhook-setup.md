# Stripe Webhook Setup

## ✅ Automatic Subscription Sync (Now Active!)

The application now automatically syncs Stripe subscriptions when webhooks are received. This happens for:

- `checkout.session.completed` - When a user completes checkout
- `customer.subscription.created` - When a new subscription is created
- `customer.subscription.updated` - When a subscription is updated (upgrades, downgrades, renewals)

## Webhook Events Handled

The `StripeWebhookListener` (added Jan 22, 2026) automatically:
1. Detects subscription events from Stripe
2. Finds the user by their Stripe customer ID
3. Syncs the subscription data to the local database
4. Creates/updates subscription items with price information

All events are logged to `storage/logs/laravel.log` for debugging.

## Testing Webhooks Locally

### Option 1: Stripe CLI (Recommended for Development)

1. Install Stripe CLI: https://stripe.com/docs/stripe-cli

2. Login to Stripe:
   ```bash
   stripe login
   ```

3. Forward webhooks to your local server:
   ```bash
   stripe listen --forward-to https://laravel-ecosurvey.ddev.site/stripe/webhook
   ```

4. Test a checkout flow - the webhook will be automatically forwarded and synced!

### Option 2: Manual Sync (If Webhook Missed)

If a subscription was created in Stripe but not synced to the database:

```bash
ddev exec php artisan subscription:sync <user-id>
```

Example:
```bash
ddev exec php artisan subscription:sync 1
```

This will:
- Fetch all active subscriptions from Stripe for the user
- Create/update subscription records in the database
- Sync subscription items (price IDs, products, quantities)
- Show the user's current tier

## Production Setup

1. Switch to **Live mode** in Stripe Dashboard (toggle in top-left)
2. Repeat webhook setup with production URL: `https://yourdomain.com/stripe/webhook`
3. Update `.env` with Live mode secret:
   ```
   STRIPE_KEY=pk_live_...
   STRIPE_SECRET=sk_live_...
   STRIPE_WEBHOOK_SECRET=whsec_live_...
   ```

## Implementation Files

- **Webhook Listener**: `app/Listeners/StripeWebhookListener.php` (handles automatic sync)
- **Sync Command**: `app/Console/Commands/SyncStripeSubscription.php` (manual sync tool)
- **Registration**: `app/Providers/AppServiceProvider.php` (event listener registration)

## Troubleshooting

- Webhook route `/stripe/webhook` auto-registered by Laravel Cashier
- Test mode and Live mode have separate webhook secrets
- Use Test mode for development
