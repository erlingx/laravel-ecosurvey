# Fresh Supabase Migration - Quick Setup Guide

**Date:** February 24, 2026  
**Strategy:** Fresh database with seeded data (no data migration needed)  
**Time:** ~15 minutes total  
**Cost:** $0 (free tier)

---

## 🚀 Step 1: Create Supabase Project (5 minutes)

1. **Go to:** https://supabase.com/dashboard

2. **Sign up** (free account)
   - Use GitHub/Google login or email

3. **Create new project:**
   - Click: **"New Project"**
   - Organization: Create new or use existing
   - **Project name:** `laravel-ecosurvey`
   - **Database password:** [Generate strong password - SAVE THIS!]
   - **Region:** `Europe West (eu-west-1)` (closest to your hosting)
   - Click: **"Create new project"**

4. **Wait 2 minutes** for project to provision
   - You'll see a progress indicator
   - When ready, you'll see the project dashboard

---

## 🔗 Step 2: Get Connection Details (2 minutes)

1. In your Supabase project dashboard:
   - Click: **Settings** (left sidebar, bottom)
   - Click: **Database** tab

2. **Find "Connection string" section:**
   - Look for: **"URI"** connection string
   - Click: **"Show"** to reveal password
   - Copy the full connection string

3. **Parse the connection string:**
   ```
   postgresql://postgres.[PROJECT_REF]:[PASSWORD]@aws-0-eu-west-1.pooler.supabase.com:6543/postgres
   ```

   Extract these values:
   - **Host:** `aws-0-eu-west-1.pooler.supabase.com`
   - **Port:** `6543` (pooler) or `5432` (direct)
   - **Database:** `postgres`
   - **Username:** `postgres.[PROJECT_REF]`
   - **Password:** [your chosen password]

4. **Enable PostGIS extension** (required for EcoSurvey):
   - Go to: **Database** (left sidebar)
   - Click: **Extensions** tab
   - Search: `postgis`
   - Enable: **postgis** extension
   - Also enable: **postgis_topology** (if available)

---

## 📝 Step 3: Update .env.production (3 minutes)

Create updated `.env.production` file:

```env
APP_NAME=EcoSurvey
APP_ENV=production
APP_KEY=base64:3oVDVxx6Hqtte2wX5Z5bIRkl5BgqtpA5mPAERnjOzO4=
APP_DEBUG=false
APP_URL="https://laravel-ecosurvey.overstimulated.dk"

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info

# ===================================
# Production Database - Supabase PostgreSQL (EU West)
# ===================================
# ✅ Free tier with unlimited compute hours!
# Connection pooler for better performance
DB_CONNECTION=pgsql
DB_HOST=aws-0-eu-west-1.pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.[YOUR-PROJECT-REF]
DB_PASSWORD=[YOUR-SUPABASE-PASSWORD]
DB_SSLMODE=require

# Note: No DB_OPTIONS needed for Supabase (removed Neon-specific config)

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=public
QUEUE_CONNECTION=database
MEDIA_DISK=public

CACHE_STORE=file

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# ===================================
# Mail Configuration (Production)
# ===================================
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_FROM_ADDRESS=noreply@ecosurvey.app
MAIL_FROM_NAME=EcoSurvey

# ===================================
# AWS S3 (Optional)
# ===================================
# AWS_ACCESS_KEY_ID=
# AWS_SECRET_ACCESS_KEY=
# AWS_DEFAULT_REGION=eu-central-1
# AWS_BUCKET=
# AWS_USE_PATH_STYLE_ENDPOINT=false
```

**Replace these values:**
- `[YOUR-PROJECT-REF]` - From Supabase connection string
- `[YOUR-SUPABASE-PASSWORD]` - Password you set when creating project
- Host/Port - Verify from your Supabase connection string

---

## 🛠️ Step 4: Remove Neon-Specific Code (2 minutes)

The custom `NeonPostgresConnector` is no longer needed. We'll update the database config.

**File: `config/database.php`**

Check if there's a custom 'pgsql' connector reference. If so, ensure it uses standard PostgreSQL driver for Supabase.

---

## 🚀 Step 5: Deploy to Production (5 minutes)

```bash
# On your local machine (Git Bash or PowerShell)

# 1. Upload updated .env
scp .env.production overstimulated.dk@linux216.unoeuro.com:laravel-ecosurvey/.env

# 2. SSH to production
ssh overstimulated.dk@linux216.unoeuro.com
cd laravel-ecosurvey

# 3. Test database connection
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connected to Supabase!';"

# If connection successful, proceed:

# 4. Clear all caches
php artisan optimize:clear

# 5. Run fresh migrations with seeders
php artisan migrate:fresh --seed --force

# 6. Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Remove old debug files
rm -f public/debug-db.php

# 8. Test site
curl -I https://laravel-ecosurvey.overstimulated.dk/

# Should return HTTP 200!
```

---

## ✅ Step 6: Verification (2 minutes)

### Test in Browser:

1. **Homepage:** https://laravel-ecosurvey.overstimulated.dk/
   - ✅ Should load without errors

2. **Register new account:**
   - Test user registration
   - Check email verification flow

3. **Login:**
   - Use seeded admin account (check your seeders)
   - Or newly registered account

4. **Test core features:**
   - Create survey
   - View surveys
   - Submit responses

### Check Logs:

```bash
# On production
tail -50 storage/logs/laravel-$(date +%Y-%m-%d).log
```

Should show no errors related to database connections.

---

## 📊 Supabase Free Tier Benefits

✅ **500 MB database storage**  
✅ **Unlimited compute hours** (no 300 hour limit like Neon!)  
✅ **50,000 monthly active users**  
✅ **500 MB egress**  
✅ **Auto-pause after 1 week inactivity** (wakes instantly on request)  
✅ **PostGIS support** (for geo/spatial features)  
✅ **Real-time subscriptions** (if needed later)  
✅ **Built-in Auth** (alternative to Fortify if needed)  

**Perfect for production sites with light-to-medium traffic!**

---

## 🔒 Security Notes

1. **Password in .env:**
   - ✅ Ensure `.env` is in `.gitignore`
   - ✅ Use strong password from Supabase
   - ✅ Never commit `.env` to repository

2. **Connection Pooler:**
   - ✅ Port 6543 uses connection pooler (recommended)
   - ✅ Better performance for web apps
   - ⚠️ Some features may require direct connection (port 5432)

3. **SSL Mode:**
   - ✅ `DB_SSLMODE=require` enforces encrypted connections

---

## 📈 Monitoring

### Check Database Usage:

1. **Supabase Dashboard:**
   - Settings → Database → Usage
   - Monitor storage and connections

2. **Set up Alerts:**
   - Settings → Billing (if upgraded later)
   - Free tier has no billing alerts, but shows usage

### Laravel Logs:

```bash
# Check for database errors
ssh overstimulated.dk@linux216.unoeuro.com
cd laravel-ecosurvey
tail -f storage/logs/laravel.log | grep -i database
```

---

## 🆘 Troubleshooting

### Connection Fails:

1. **Check SSL mode:**
   ```bash
   # Try without SSL if needed (not recommended for production)
   # DB_SSLMODE=disable
   ```

2. **Verify PostGIS:**
   ```bash
   php artisan tinker
   > DB::select("SELECT PostGIS_version();");
   ```

3. **Check credentials:**
   - Supabase Dashboard → Settings → Database
   - Verify host, port, username match

### Migration Fails:

1. **Check PostGIS extension:**
   ```bash
   php artisan tinker
   > DB::statement("CREATE EXTENSION IF NOT EXISTS postgis;");
   > DB::statement("CREATE EXTENSION IF NOT EXISTS postgis_topology;");
   ```

2. **Run migrations step by step:**
   ```bash
   php artisan migrate:fresh --force
   # If error, check which migration failed
   php artisan migrate:status
   ```

### Seeders Fail:

1. **Run individually:**
   ```bash
   php artisan db:seed --class=UserSeeder --force
   php artisan db:seed --class=SurveySeeder --force
   ```

---

## 🎯 Success Checklist

- [ ] Supabase project created
- [ ] PostGIS extension enabled
- [ ] .env.production updated with Supabase credentials
- [ ] .env uploaded to production server
- [ ] Database connection tested (tinker)
- [ ] `migrate:fresh --seed` completed successfully
- [ ] Caches cleared and rebuilt
- [ ] Homepage loads (HTTP 200)
- [ ] User registration works
- [ ] Login works
- [ ] Core features functional
- [ ] No errors in logs

---

## 🎉 Expected Result

**Total Time:** ~15 minutes active work  
**Cost:** $0 (free forever for your traffic)  
**Site Status:** ✅ Online with fresh database  
**Data:** Seeded with sample/test data  

**Your site should now be:**
- ✅ Online and responsive
- ✅ Connected to Supabase (unlimited compute)
- ✅ Fresh database with seeded data
- ✅ No quota worries
- ✅ Better free tier than Neon

---

## 📞 Resources

- **Supabase Dashboard:** https://supabase.com/dashboard
- **Supabase Docs:** https://supabase.com/docs
- **Supabase Pricing:** https://supabase.com/pricing
- **Support:** https://supabase.com/support

---

**Next Action:** Create Supabase project now, then follow steps 2-6 above!

