# Shared Hosting Deployment Guide

## ⚠️ Important Considerations

This Laravel application was built with DDEV and uses **PostgreSQL 16 with PostGIS** for spatial/GIS features. Most shared hosting environments have limitations:

### Requirements Checklist

- [ ] **PHP 8.3+** (required)
- [ ] **PostgreSQL 16+** with PostGIS extension (required for spatial features)
- [ ] **Composer** access (for dependencies)
- [ ] **Node.js/NPM** access (for building frontend assets)
- [ ] **SSH/Terminal access** (for running artisan commands)
- [ ] **Process control** for queue workers (cron jobs as fallback

**Simply.com Pro accaount har det hele. 
MAngler hvis en postgis addon eller php ext så det ikke kan lade sig gøre
Ikke NPM men det kan løses ved a køre npm run build lokalt og adde 
til GIS**

**https://railway.com** er sandsynligvis muligt. $5 pr. måned

### Database Considerations

**Option 1: PostgreSQL with PostGIS (Recommended)**
- ✅ Full spatial/GIS features work
- ❌ Rare on shared hosting (more common on VPS/cloud)
- Providers: DigitalOcean, Linode, AWS RDS, Google Cloud SQL

**Option 2: MySQL/MariaDB (Common on shared hosting)**
- ✅ Available on most shared hosts
- ❌ **No PostGIS** - spatial features won't work
- ❌ Would require refactoring to remove GIS dependencies

---

## Deployment Steps (PostgreSQL Available)

### 1. Upload Files
Upload all files to your shared hosting account via FTP/SFTP, typically to `public_html` or `www` directory.

### 2. Configure Web Root
Point your domain to the `public` directory (not the root Laravel folder).

Example Apache `.htaccess` in root (if needed):
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

### 3. Environment Configuration
```bash
# Copy the shared hosting example
cp .env.shared-hosting.example .env

# Edit .env with your hosting credentials
nano .env  # or use cPanel File Manager editor
```

Update these values:
- `APP_URL` - Your domain
- `DB_*` - Your PostgreSQL credentials from hosting control panel
- `MAIL_*` - Your SMTP mail server settings

### 4. Generate Application Key
```bash
php artisan key:generate
```

### 5. Install Dependencies
```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install JavaScript dependencies
npm install

# Build production assets
npm run build
```

### 6. Database Setup
```bash
# Check if PostGIS is available
psql -h localhost -U your_user -d your_database -c "SELECT PostGIS_version();"

# If PostGIS not enabled, enable it
psql -h localhost -U your_user -d your_database -c "CREATE EXTENSION IF NOT EXISTS postgis;"

# Run migrations
php artisan migrate --force

# Seed database (optional)
php artisan db:seed --force
```

### 7. Set Permissions
```bash
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage bootstrap/cache  # If above doesn't work
```

### 8. Optimize for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 9. Setup Queue Worker (Cron Job)
Since shared hosting doesn't support long-running processes, use cron:

Add to crontab:
```bash
* * * * * cd /path/to/your/app && php artisan schedule:run >> /dev/null 2>&1
```

In `routes/console.php`, add:
```php
Schedule::command('queue:work --stop-when-empty')->everyMinute();
```

---

## Alternative: Use VPS/Cloud Instead

**Recommended hosting for this project:**

### Why VPS/Cloud is Better:
- ✅ PostgreSQL 16 + PostGIS support
- ✅ Long-running queue workers
- ✅ Full SSH access and process control
- ✅ Better performance
- ✅ More control over environment

### Recommended Providers:
1. **DigitalOcean** ($6-12/month)
   - Managed PostgreSQL with PostGIS
   - Laravel-friendly
   - Easy server setup

2. **Linode/Akamai** ($5-10/month)
   - Similar to DigitalOcean
   - Good PostgreSQL support

3. **Laravel Forge + DigitalOcean** ($12 server + $15 Forge)
   - Automated Laravel deployment
   - Easy PostgreSQL + PostGIS setup
   - Queue worker management

4. **Cloudways** ($14/month+)
   - Managed Laravel hosting
   - Request PostgreSQL support

---

## MySQL Migration (If PostgreSQL Unavailable)

If your host only supports MySQL, you'll need to:

1. **Remove PostGIS dependencies** from migrations
2. **Convert spatial columns** to regular lat/lng decimal columns
3. **Refactor spatial queries** to use Haversine formula for distance
4. **Lose spatial indexing** performance benefits

This requires significant code changes and is NOT recommended for a GIS-focused application.

---

## Testing the Deployment

```bash
# Check system requirements
php artisan about

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Test PostGIS
>>> DB::select("SELECT PostGIS_version();");

# Check for errors
tail -f storage/logs/laravel.log
```

---

## Troubleshooting

**500 Internal Server Error**
- Check `storage/logs/laravel.log`
- Verify storage permissions: `chmod -R 775 storage bootstrap/cache`
- Clear cache: `php artisan config:clear`

**Database Connection Failed**
- Verify DB credentials in `.env`
- Check if PostgreSQL is accessible from your hosting account
- Test connection: `psql -h DB_HOST -U DB_USERNAME -d DB_DATABASE`

**PostGIS Functions Not Found**
- Enable extension: `CREATE EXTENSION IF NOT EXISTS postgis;`
- Check if PostGIS is installed on server
- Contact hosting support

**Assets Not Loading (404)**
- Run `npm run build` again
- Check `public/build` directory exists
- Verify `APP_URL` in `.env` is correct

---

## Security Checklist

- [ ] Set `APP_DEBUG=false` in production
- [ ] Set `APP_ENV=production`
- [ ] Use strong `APP_KEY` (generated automatically)
- [ ] Set `DB_PASSWORD` to strong password
- [ ] Configure SSL certificate (HTTPS)
- [ ] Add `.env` to `.gitignore` (already done)
- [ ] Restrict `storage` and `bootstrap/cache` web access
- [ ] Keep Laravel and dependencies updated

---

## Maintenance

```bash
# Clear all caches
php artisan optimize:clear

# Rebuild caches
php artisan optimize

# Update dependencies
composer update
npm update
npm run build

# Run migrations (after updates)
php artisan migrate --force
```

