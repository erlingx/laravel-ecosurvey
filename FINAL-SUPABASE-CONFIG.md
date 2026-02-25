# Correct Supabase Connection - Final Configuration

## ✅ Update These Lines in Production .env

SSH to server and edit `.env`:

```bash
ssh overstimulated.dk@linux216.unoeuro.com
cd laravel-ecosurvey
nano .env
```

**Find the DB_ lines and update to:**

```bash
DB_CONNECTION=pgsql
DB_HOST=aws-1-eu-west-1.pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.uuorwkqmuucqwdexevma
DB_PASSWORD=6NxmVNPdjoNs0fd9
DB_SSLMODE=require
```

**⚠️ CRITICAL: Remove this line completely:**
```bash
DB_OPTIONS=endpoint=ep-orange-breeze-a9xvfbuw
```

Save: `Ctrl+O`, `Enter`, `Ctrl+X`

## 🚀 Run Migration Commands

```bash
# Clear config cache
php artisan config:clear

# Test connection
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connected to Supabase!';"

# If successful, run fresh migrations with seeders
php artisan migrate:fresh --seed --force

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Clean up debug files
rm -f public/debug-db.php

# Test site
curl -I https://laravel-ecosurvey.overstimulated.dk/
```

## 📋 What Changed

**The key difference was:**
- ❌ Wrong: `aws-0-eu-west-1.pooler.supabase.com`
- ✅ Correct: `aws-1-eu-west-1.pooler.supabase.com`

Your Supabase project is in a newer AWS region (`aws-1` prefix).

## ✅ Expected Result

After running the commands above:
- Database connection successful
- Fresh migrations with seeded data
- Site online at https://laravel-ecosurvey.overstimulated.dk/
- No more 500 errors
- Unlimited compute hours!

