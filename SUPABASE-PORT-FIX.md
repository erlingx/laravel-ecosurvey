# 🔧 Fix: Supabase Prepared Statement Error

## Issue
Error during seeding: `prepared statement "pdo_stmt_000001f1" does not exist`

**Cause:** Supabase's Session mode pooler (port 6543) doesn't support prepared statements properly with Laravel.

## ✅ Solution: Switch to Transaction Mode

### On Production Server:

```bash
# Edit .env
nano .env

# Change ONLY this line:
DB_PORT=5432

# Was: DB_PORT=6543
# Now: DB_PORT=5432

# Save: Ctrl+O, Enter, Ctrl+X
```

### Then Run:

```bash
# Clear config
php artisan config:clear

# Run migrations and seeders again
php artisan migrate:fresh --seed --force

# Should complete successfully this time!

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Test site
curl -I https://laravel-ecosurvey.overstimulated.dk/
```

## 🎯 What Changed

| Mode | Port | Prepared Statements | Laravel Compatible |
|------|------|---------------------|-------------------|
| **Session** | 6543 | ❌ Not supported | ❌ Causes errors |
| **Transaction** | 5432 | ✅ Supported | ✅ Works perfectly |

**Transaction mode** is the correct choice for Laravel applications.

## 📋 Complete Working Configuration

```env
DB_CONNECTION=pgsql
DB_HOST=aws-1-eu-west-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.uuorwkqmuucqwdexevma
DB_PASSWORD=6NxmVNPdjoNs0fd9
DB_SSLMODE=require
```

**No `DB_OPTIONS` line!**

## ✅ Expected Result

After changing to port 5432:
- ✅ Migrations complete
- ✅ Seeders complete (no prepared statement errors)
- ✅ Site online
- ✅ All features working

Total time to fix: 2 minutes!

