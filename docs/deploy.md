# Kogebog for deployment af Laravel 12 projekt til UnoEuro/Simply.com shared hosting #

✅ **CONFIRMED WORKING:** PostgreSQL + Neon fungerer på UnoEuro/Simply.com!

Shared hosting har git, composer, terminal men ikke node.js og npm (så npm run build køres lokalt og synkroniseres med GIT)

## Lokalt: ##
- kør ddev pint og ddev artisan test
- ddev npm run build (VIGTIG: Husk altid at køre dette efter frontend ændringer - ellers vil nye 
  komponenter/ændringer ikke virke på production!)
- Update composer.lock: `ddev composer update --lock` (if composer.json was changed)
- Create and edit .env.production
- git add, git tag v1.0.0, git commit, git push

## UnoEuro/Simply.com Deployment: ##

### Step 1: Opret subdomain i cPanel
- Opret subdomain (f.eks. laravel-ecosurvey.overstimulated.dk)
- Note: Document root sættes senere med .htaccess trick

### Step 2: SSH ind på serveren og klon repository
```bash
cd ~/public_html
mkdir laravel-ecosurvey
cd laravel-ecosurvey

# Klon repository (husk punktum for at klone til current directory)
git clone https://github.com/erlingx/laravel-ecosurvey.git .
# Username: erlingx
# Password: Brug Personal Access Token (not your GitHub password)
```

### Step 3: Install PHP dependencies
```bash
composer install --optimize-autoloader --no-dev
```

### Step 4: Create .env file
```bash
nano .env
```

Paste indholdet fra din lokale `.env.production` file:
```env
APP_NAME=EcoSurvey
APP_ENV=production
APP_KEY=base64:3oVDVxx6Hqtte2wX5Z5bIRkl5BgqtpA5mPAERnjOzO4=
APP_DEBUG=false
APP_URL="https://laravel-ecosurvey.overstimulated.dk"

# Neon PostgreSQL (EU Frankfurt) - WORKING on UnoEuro!
DB_CONNECTION=pgsql
DB_HOST=ep-orange-breeze-a9xvfbuw.gwc.azure.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=neondb_owner
DB_PASSWORD=npg_LWwZnUscq5A3
DB_SSLMODE=require
DB_OPTIONS=endpoint=ep-orange-breeze-a9xvfbuw

# Use file-based cache (not database)
CACHE_STORE=file

# ... rest of your .env settings ...
```

Save: `Ctrl+X`, `Y`, `Enter`

### Step 5: Set permissions
```bash
chmod 600 .env
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 775 storage bootstrap/cache
```

### Step 6: Run migrations and seed database
```bash
php artisan migrate --force --seed
php artisan storage:link
php artisan optimize
```

### Step 7: Fix Document Root with .htaccess (CRITICAL!)
**Problem:** Most shared hosting providers don't allow changing document root to `/public` subfolder.

**Solution:** Create `.htaccess` redirect in project root

```bash
cd ~/public_html/laravel-ecosurvey
nano .htaccess
```

Add this content:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Redirect all requests to public subfolder
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

Save: `Ctrl+X`, `Y`, `Enter`

**What this does:** Automatically redirects all web requests from `https://laravel-ecosurvey.overstimulated.dk/` to `https://laravel-ecosurvey.overstimulated.dk/public/` where Laravel's `index.php` lives.

### Step 8: Test your deployment
Visit: `https://laravel-ecosurvey.overstimulated.dk`

You should see your Laravel application (not a 403 Forbidden error)!

---

## Troubleshooting UnoEuro/Simply.com:

### PostgreSQL "could not find driver" error:
1. cPanel → Software → Select PHP Version
2. Find and CHECK: `pdo_pgsql` extension
3. Click "Save"
4. Verify: `php -m | grep pdo_pgsql`
5. Retry migrations

### PostgreSQL "Endpoint ID is not specified" error:
Your `.env` file is missing `DB_OPTIONS=endpoint=xxx` parameter.

Add to `.env`:
```env
DB_OPTIONS=endpoint=ep-orange-breeze-a9xvfbuw
```

Then:
```bash
php artisan config:clear
php artisan migrate --force
```

### 403 Forbidden error:
You're missing the `.htaccess` redirect in project root (see Step 7 above).

Or check detailed fix guide: `docs/FIX-403-FORBIDDEN.md`

### Seeder "duplicate key" error:
Database already has users. Either:
- Skip seeding: `php artisan migrate --force` (no --seed flag)
- Fresh migration: `php artisan migrate:fresh --force --seed` (⚠️ deletes all data!)

---

## GreenGeeks Deployment (NOT RECOMMENDED - PostgreSQL BLOCKED): ##

**⚠️ WARNING:** GreenGeeks blocks ALL outbound PostgreSQL connections (ports 5432, 6543, etc.)
**Recommendation:** Use UnoEuro/Simply.com instead (PostgreSQL works!) or switch to VPS hosting.

If you must use GreenGeeks, you'll need to use MySQL instead of PostgreSQL.

Opret email adresse og sæt info i env.production (til at sende email notifikationer)

In C-panel:
- create a new domain (subdomain):
  https://ams200.greengeeks.net:2083/cpsess0619723521/frontend/jupiter/domains/index.html#/create
 - set document root in tools>domains>manage hvis ikke gjort ovenfor (Laravel: public)
- In terminal:
- cd til /public
- change github rpos til public i settings i topmenu
- cd til public_html/laravel-ecosurvey (ikke /public)
- git clone https://github.com/erlingx/laravel-ecosurvey.git . (husk punktum) (Git remote add er ikke nødvendig når
  man kloner )
- username: erlingx
- password: Brug token istedet for password:
- 
- Install dependencies (on production server):
    composer install --optimize-autoloader --no-dev
- php artisan key:generate
- php artisan migrate --force
- php artisan config:clear
- php artisan optimize
- php artisan db:seed --class=ProductionSeeder

**IMPORTANT: If you get "could not find driver" error:**
PostgreSQL PDO driver is not enabled. Fix this:
1. Go to cPanel → Software → Select PHP Version
2. Find and CHECK: `pdo_pgsql` extension
3. Click "Save"
4. Verify: `php -m | grep pdo_pgsql` (should show "pdo_pgsql")
5. Retry: `php artisan migrate --force`

**CRITICAL: Connection refused to Neon PostgreSQL**

GreenGeeks blocks outbound PostgreSQL connections despite what support says. 

**SOLUTION: Use MySQL instead (works immediately)**

**Step 1: Create MySQL database in cPanel**
1. cPanel → Databases → MySQL Databases
2. Create Database: `electr37_ecosurvey`
3. Create User: `electr37_eco` (with strong password)
4. Add User to Database with ALL PRIVILEGES

**Step 2: Update .env on server**
```bash
cd ~/public_html/laravel-ecosurvey.electrominds.dk
nano .env
```

Change database settings:
```env
# Change TO MySQL:
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=electr37_ecosurvey
DB_USERNAME=electr37_eco
DB_PASSWORD=your-mysql-password-from-cpanel

# Remove this line:
# DB_SSLMODE=require
```

**Step 3: Run migrations**
```bash
php artisan config:clear
php artisan migrate --force
php artisan optimize
php artisan db:seed --class=ProductionSeeder
```

**Note about PostGIS:** MySQL doesn't have PostGIS, but has spatial types (POINT, POLYGON). Your migrations will work if they use Laravel's spatial columns.

**Option 2: Use MySQL Instead (IMMEDIATE FIX)**
Switch from Neon PostgreSQL to GreenGeeks MySQL:

1. Create MySQL database in cPanel:
   - cPanel → Databases → MySQL Databases
   - Create database: `electr37_ecosurvey`
   - Create user with password
   - Add user to database with ALL PRIVILEGES

2. Update .env file:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=electr37_ecosurvey
   DB_USERNAME=electr37_dbuser
   DB_PASSWORD=your-mysql-password
   # Remove: DB_SSLMODE=require
   ```

3. Update code to remove PostGIS dependencies:
   - Comment out PostGIS migrations
   - Use MySQL spatial types instead (POINT, POLYGON)
   - Test locally with MySQL first

**Option 3: Use Local PostgreSQL via SSH Tunnel (ADVANCED)**
Not recommended for shared hosting - requires persistent SSH connection.

**Recommendation:** Contact GreenGeeks support first. If they can't whitelist Neon, switch to MySQL (requires code changes for spatial data).

- I file manager i c-panel:
-  delete /docs folder and update .gitignore with /docs
  - opret .env 
  - paste indholdet fra lokale .env.produktion 
  - set permissions to 600 on .env file
  
## Cronjob ##
Sæt cronjob til at at køre queue der bruges af email/slack notifikationer
GreenGeeks: 
c-panel > advanced > cron jobs
- add new cron job
- common settings: once per one minute 
-  	cd /home/electr37/public_html/laravel-organizer.electrominds.dk && /usr/local/bin/php artisan queue:work database --stop-when-empty --max-time=50 >/dev/null 2>&1

If something breaks: Remove '>/dev/null 2>&1' temporarily to see error messages for debugging.
- save
- Tjek terminal om køen kører: `ps aux | grep "queue:work"`
- test ved at sende en notifikation



## Admin Access: ##
 Use ProductionSeeder to create admin user




