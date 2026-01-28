# Fix 403 Forbidden Error on UnoEuro/Simply.com Shared Hosting

## Error Details:
```
403 Forbidden
You do not have permission to access this URL.
```

This happens because the web server can't find or access the Laravel entry point.

---

## Solution Steps:

### Step 1: Verify Document Root in cPanel

1. Log into cPanel
2. Go to **Domains** → Find `laravel-ecosurvey.overstimulated.dk`
3. Click **Manage** or **Edit**
4. **Document Root** should be set to:
   ```
   /home/overstimulated.dk/public_html/laravel-ecosurvey/public
   ```
   NOT just:
   ```
   /home/overstimulated.dk/public_html/laravel-ecosurvey
   ```

The document root MUST point to the `/public` subfolder!

---

### Step 2: Verify Files Exist in /public Folder

SSH into server and check:

```bash
cd ~/public_html/laravel-ecosurvey/public
ls -la
```

You should see:
- `index.php` ← CRITICAL - Laravel entry point
- `.htaccess` ← CRITICAL - Apache rewrite rules
- `favicon.ico`
- `robots.txt`
- Folders: `build/`, `css/`, `js/`, `files/`, etc.

If `index.php` or `.htaccess` are missing, they got deleted during git operations.

---

### Step 3: Restore Missing Files (If Needed)

If `index.php` or `.htaccess` are missing:

```bash
cd ~/public_html/laravel-ecosurvey
git checkout public/index.php public/.htaccess
```

Or manually create them:

**public/index.php:**
```php
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Register the Composer autoloader
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
```

**public/.htaccess:**
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

### Step 4: Fix File Permissions

```bash
cd ~/public_html/laravel-ecosurvey

# Fix directory permissions
find . -type d -exec chmod 755 {} \;

# Fix file permissions
find . -type f -exec chmod 644 {} \;

# Make sure storage and bootstrap/cache are writable
chmod -R 775 storage bootstrap/cache

# Fix .env permissions
chmod 600 .env
```

---

### Step 5: Verify .htaccess is Active

Check if Apache `mod_rewrite` is enabled on the server:

```bash
# Create test file
echo "<?php phpinfo(); ?>" > ~/public_html/laravel-ecosurvey/public/info.php

# Visit: https://laravel-ecosurvey.overstimulated.dk/info.php
# Look for "mod_rewrite" in the Apache section
# Should show "Loaded Modules" includes mod_rewrite
```

If `mod_rewrite` is not enabled, contact UnoEuro support.

---

### Step 6: Check Storage Symlink

Laravel needs a symlink from `public/storage` to `storage/app/public`:

```bash
cd ~/public_html/laravel-ecosurvey
php artisan storage:link
```

---

### Step 7: Clear All Caches

```bash
cd ~/public_html/laravel-ecosurvey
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan optimize
```

---

### Step 8: Verify Directory Structure

Your structure should look like this:

```
~/public_html/laravel-ecosurvey/          ← Git repo root
├── app/
├── bootstrap/
├── config/
├── database/
├── public/                                ← WEB SERVER POINTS HERE
│   ├── index.php                          ← Entry point
│   ├── .htaccess                          ← Rewrite rules
│   ├── favicon.ico
│   ├── robots.txt
│   ├── build/                             ← Compiled assets
│   ├── css/
│   ├── js/
│   └── storage -> ../storage/app/public   ← Symlink
├── resources/
├── routes/
├── storage/
├── vendor/
├── .env                                   ← Environment config
└── artisan
```

---

### Step 9: Test Access

After fixing, test these URLs:

1. **Main site:** https://laravel-ecosurvey.overstimulated.dk
   - Should show Laravel welcome or login page

2. **Direct access to public:** https://laravel-ecosurvey.overstimulated.dk/index.php
   - Should work the same as above

3. **Check routes:** https://laravel-ecosurvey.overstimulated.dk/login
   - Should show login page if routes work

---

## Common Mistakes:

❌ **Wrong:** Document Root = `/home/overstimulated.dk/public_html/laravel-ecosurvey`
✅ **Correct:** Document Root = `/home/overstimulated.dk/public_html/laravel-ecosurvey/public`

❌ **Wrong:** Deleting `.htaccess` because it's a "hidden file"
✅ **Correct:** Keep `.htaccess` - it's critical for Laravel routing

❌ **Wrong:** Running `git pull` from `/public` folder
✅ **Correct:** Run `git pull` from project root (`laravel-ecosurvey/`)

---

## Quick Fix Command Sequence:

Run all these commands in order:

```bash
cd ~/public_html/laravel-ecosurvey

# Verify files exist
ls -la public/index.php public/.htaccess

# If missing, restore them
git checkout public/index.php public/.htaccess

# Fix permissions
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 775 storage bootstrap/cache
chmod 600 .env

# Create storage symlink
php artisan storage:link

# Clear caches
php artisan optimize:clear
php artisan optimize

# Test
curl -I https://laravel-ecosurvey.overstimulated.dk
```

---

## Still Getting 403?

If none of the above works:

1. **Check Apache error logs:**
   ```bash
   tail -50 ~/logs/error_log
   ```

2. **Contact UnoEuro Support:**
   - Tell them: "My Laravel application shows 403 Forbidden"
   - Provide: Domain name and document root path
   - Ask: "Is mod_rewrite enabled? Are there any firewall rules blocking PHP execution?"

3. **Test with simple PHP file:**
   ```bash
   echo "<?php echo 'PHP works!'; ?>" > ~/public_html/laravel-ecosurvey/public/test.php
   # Visit: https://laravel-ecosurvey.overstimulated.dk/test.php
   # If this shows 403, it's a server configuration issue, not Laravel
   ```

---

## Expected Result:

After fixing, you should see:
- Laravel welcome page, OR
- Laravel login page, OR
- Your application's home page

NOT a 403 error!
