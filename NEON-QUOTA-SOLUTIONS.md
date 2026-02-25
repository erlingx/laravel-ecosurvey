# Neon Compute Quota Exceeded - Solutions

**Date:** 2026-02-24
**Issue:** Production site (laravel-ecosurvey.overstimulated.dk) returning 500 errors
**Root Cause:** Neon PostgreSQL free tier compute quota (300 hours/month) exceeded

---

## Immediate Solutions

### Option 1: Upgrade Neon Plan (Recommended for Production) ✅

**Launch Plan** (~$19/month):
- 300 compute hours included
- Additional compute: $0.102/hour
- Best for production sites with moderate traffic

**Scale Plan** (~$69/month):
- Unlimited compute hours
- Advanced features (read replicas, point-in-time restore)
- Best for high-traffic production sites

**How to upgrade:**
1. Go to: https://console.neon.tech/app/projects
2. Select your project: `ep-orange-breeze-a9xvfbuw`
3. Click: **Settings → Billing → Upgrade plan**
4. Choose Launch or Scale
5. Site will be online within minutes

---

### Option 2: Optimize Auto-Suspend Settings (Free Tier) ⚙️

Reduce compute usage by suspending database when idle:

1. Go to: https://console.neon.tech/app/projects
2. Select your project
3. Go to: **Settings → Compute**
4. Set **Auto-suspend delay**: `5 minutes` (minimum)
5. Database will suspend after 5 min of no activity
6. Wakes automatically on first connection (adds ~1-2 sec latency)

**Note:** This conserves hours but may cause slow first loads after idle periods.

---

### Option 3: Wait for Monthly Reset 📅

Neon free tier resets monthly. If early in billing cycle:
- Check: Settings → Usage → See reset date
- Site will auto-recover when quota resets
- **Not viable for production** - could be weeks of downtime

---

### Option 4: Migrate to Alternative Database Service 🔄

If budget-constrained, consider:

#### A. Supabase (More generous free tier)
- 500 MB database
- Unlimited compute (community support)
- Free plan: https://supabase.com/pricing

#### B. Railway (PostgreSQL)
- $5/month for 500 hours
- Better than Neon free tier
- https://railway.app/pricing

#### C. ElephantSQL
- 20 MB free tier (very limited)
- Shared plan: $5/month (20 GB)

#### D. Local PostgreSQL on UnoEuro
Check if UnoEuro offers PostgreSQL hosting - may be included in your plan.

---

## Migration Instructions (If Choosing Alternative)

### Export from Neon:
```bash
# When quota resets or after upgrade
pg_dump "postgresql://neondb_owner:npg_LWwZnUscq5A3@ep-orange-breeze-a9xvfbuw.gwc.azure.neon.tech:5432/neondb?sslmode=require" > neon-backup.sql
```

### Import to new database:
```bash
# For new PostgreSQL service
psql "NEW_DATABASE_URL" < neon-backup.sql

# Update .env.production
DB_CONNECTION=pgsql
DB_HOST=new-host
DB_PORT=5432
DB_DATABASE=new-db
DB_USERNAME=new-user
DB_PASSWORD=new-pass

# Deploy updated .env
scp .env.production overstimulated.dk@linux216.unoeuro.com:laravel-ecosurvey/.env

# Clear caches on production
ssh overstimulated.dk@linux216.unoeuro.com
cd laravel-ecosurvey
php artisan config:clear
php artisan cache:clear
```

---

## Understanding Neon Compute Hours

**What counts as compute time:**
- Active connections to database
- Running queries
- Idle connections (still consuming compute!)
- Background maintenance tasks

**Why 300 hours exhausted quickly:**
- 300 hours = 12.5 days if running 24/7
- If auto-suspend not configured, database runs constantly
- Multiple active connections multiply usage

**Free tier compute usage scenarios:**
- Auto-suspend 5 min: ~50-100 hours/month (light traffic)
- Auto-suspend disabled: 720 hours/month (exceeds quota)
- Production site: Usually needs paid plan

---

## Recommended Action Plan

### For Production Site (Immediate):

**✅ Step 1: Upgrade to Launch Plan ($19/month)**
- Production sites need reliability
- 300 hours baseline + pay-as-you-go
- Worth the cost vs. downtime

**✅ Step 2: Configure Auto-Suspend**
- Set to 5 minutes even on paid plan
- Reduces costs during low-traffic periods
- Wakes instantly when needed

**✅ Step 3: Monitor Usage**
- Settings → Usage (check weekly)
- Set up billing alerts
- Adjust auto-suspend if needed

### After Database is Back Online:

```bash
# SSH to production
ssh overstimulated.dk@linux216.unoeuro.com
cd laravel-ecosurvey

# Test database connection
php artisan tinker
> DB::connection()->getPdo();
> exit

# Clear all caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Remove debug file
rm public/debug-db.php

# Test site
curl -I https://laravel-ecosurvey.overstimulated.dk/
```

---

## Cost Comparison

| Service | Free Tier | Paid Tier | Notes |
|---------|-----------|-----------|-------|
| Neon | 300h/mo | Launch: $19 (300h + overage) | Best for serverless |
| Supabase | Unlimited compute | Pro: $25/mo | More generous free |
| Railway | Trial only | $5/mo (500h) | Good value |
| Heroku Postgres | 10k rows | Mini: $5/mo (10M rows) | Reliable but pricey |
| DigitalOcean | None | $15/mo (managed) | Full control |

---

## Prevention for Future

1. **Enable auto-suspend** (5 min delay)
2. **Set billing alerts** in Neon dashboard
3. **Monitor usage weekly** especially early on
4. **Close idle connections** in application
5. **Use connection pooling** (PgBouncer) if high traffic
6. **Consider upgrade** when approaching limits

---

## Current Database Connection String

```
postgresql://neondb_owner:npg_LWwZnUscq5A3@ep-orange-breeze-a9xvfbuw.gwc.azure.neon.tech:5432/neondb?sslmode=require&options=endpoint%3Dep-orange-breeze-a9xvfbuw
```

**Project:** ep-orange-breeze-a9xvfbuw
**Region:** Azure West Central (gwc)
**Database:** neondb

---

## Support Links

- Neon Dashboard: https://console.neon.tech/app/projects
- Neon Pricing: https://neon.tech/pricing
- Neon Docs - Auto-suspend: https://neon.tech/docs/guides/auto-suspend-guide
- Neon Docs - Compute: https://neon.tech/docs/introduction/compute

---

**Next Action:** Upgrade Neon plan to Launch ($19/month) and configure auto-suspend to 5 minutes.

Site will be back online within 5 minutes of upgrading.

