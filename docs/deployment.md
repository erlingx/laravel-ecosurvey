# Deployment Checklist

## Pre-Deployment

- [ ] Run all tests: `ddev exec php artisan test`
- [ ] Run Pint formatter: `ddev exec vendor/bin/pint --dirty`
- [ ] Build production assets: `ddev exec npm run build`
- [ ] Check `.env.example` is up to date
- [ ] Review and commit all changes

## Production Environment Setup

### 1. Server Requirements
- [ ] PHP 8.3+
- [ ] PostgreSQL 14+ with PostGIS extension
- [ ] Composer 2.x
- [ ] Node.js 18+ & npm
- [ ] Redis (for cache/queues)

### 2. Environment Configuration
- [ ] Copy `.env.example` to `.env`
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate app key: `php artisan key:generate`
- [ ] Configure database credentials
- [ ] Set queue driver: `QUEUE_CONNECTION=database` or `redis`

### 3. Third-Party Services
- [ ] Stripe Live mode keys (`STRIPE_KEY`, `STRIPE_SECRET`)
- [ ] NASA API key (`NASA_API_KEY`)
- [ ] Copernicus credentials (`COPERNICUS_USERNAME`, `COPERNICUS_PASSWORD`)
- [ ] Configure webhook secret: `STRIPE_WEBHOOK_SECRET`

### 4. Database Setup
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Seed initial data: `php artisan db:seed --force`
- [ ] Verify PostGIS extension: `CREATE EXTENSION IF NOT EXISTS postgis;`

### 5. File Storage
- [ ] Set `FILESYSTEM_DISK=s3` or configure local storage
- [ ] Create storage symlink: `php artisan storage:link`
- [ ] Set proper permissions on `storage/` and `bootstrap/cache/`

### 6. Queue Workers
- [ ] Set up supervisor/systemd for queue workers
- [ ] Configure: `php artisan queue:work --sleep=3 --tries=3 --timeout=60 --max-time=3600`
- [ ] Auto-restart on deployment

### 7. Scheduled Tasks
- [ ] Add to crontab: `* * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1`

### 8. Optimization
- [ ] Cache config: `php artisan config:cache`
- [ ] Cache routes: `php artisan route:cache`
- [ ] Cache views: `php artisan view:cache`
- [ ] Cache events: `php artisan event:cache`

### 9. Stripe Webhooks
- [ ] Create webhook endpoint in Stripe Dashboard (Live mode)
- [ ] URL: `https://yourdomain.com/stripe/webhook`
- [ ] Select events: `checkout.session.completed`, `customer.subscription.*`, `invoice.*`
- [ ] Add signing secret to `.env`: `STRIPE_WEBHOOK_SECRET=whsec_live_...`

### 10. Security
- [ ] Enable HTTPS/SSL
- [ ] Set secure session cookie: `SESSION_SECURE_COOKIE=true`
- [ ] Configure CORS if needed
- [ ] Set trusted proxies if behind load balancer
- [ ] Review `config/cors.php` and `config/session.php`

## Post-Deployment

- [ ] Test user registration/login
- [ ] Test Stripe checkout flow (small amount)
- [ ] Verify webhook receives events (check logs)
- [ ] Test data point creation
- [ ] Test satellite analysis fetch
- [ ] Monitor logs: `tail -f storage/logs/laravel.log`
- [ ] Verify queue workers running: `ps aux | grep queue:work`
- [ ] Test scheduled tasks: `php artisan schedule:list`

## Rollback Plan

If deployment fails:
1. Revert code to previous version
2. Run: `php artisan migrate:rollback` (if migrations changed)
3. Clear caches: `php artisan optimize:clear`
4. Restart queue workers

## Monitoring

- [ ] Set up error tracking (Sentry, Bugsnag, etc.)
- [ ] Monitor queue jobs: `php artisan queue:monitor database`
- [ ] Check disk space for uploads and logs
- [ ] Monitor database performance
- [ ] Watch Stripe webhook delivery in Dashboard

## Common Issues

**Subscription not syncing after checkout:**
```bash
php artisan subscription:sync <user-id>
```

**Queue jobs stuck:**
```bash
php artisan queue:restart
```

**Clear all caches:**
```bash
php artisan optimize:clear
```

**Rebuild frontend assets:**
```bash
npm run build
```
