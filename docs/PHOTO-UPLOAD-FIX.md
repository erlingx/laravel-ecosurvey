# Photo Upload Fix for Production

## TL;DR - Complete Fix

**Photos not displaying in forms AND map popup:**

```bash
# On production server:
cd /path/to/laravel-ecosurvey

# 1. Fix APP_URL in .env
grep APP_URL .env
# Should be: APP_URL=https://your-actual-domain.com
# NOT: APP_URL=http://localhost or APP_URL=

nano .env  # Change APP_URL to your real domain

# 2. Clear cache
php artisan config:clear
php artisan cache:clear

# 3. Update code (already done in this commit)
# - app/Services/GeospatialService.php: Fixed photo URL generation for map

# 4. Refresh browser - photos should now display everywhere!
```

**What was wrong:**
1. `.env` had `APP_URL=http://localhost` → Fixed by setting correct domain
2. Map popup was using hardcoded `storage/` path → Fixed in `GeospatialService.php`
3. Form was using `Storage::disk('uploads')->url()` which depends on `APP_URL`

**What's fixed:**
✅ Photos display in create/edit forms  
✅ Photos display in map popup  
✅ Both use the correct URL from `APP_URL` + `/files/`

---

## Issue
Photos are not being displayed when creating or editing data-points on production.

## Root Cause
**UPDATE:** If you can see images in `public/files/data-points/` but they're not displaying in the UI, the issue is **URL generation**, not permissions.

The `uploads` disk uses `APP_URL` from `.env` to generate image URLs. If `APP_URL` is not set correctly, images won't load.

## Quick Fix (If Images Exist But Don't Display)

### Check your `.env` file on production:

```bash
# SSH into production
ssh your-user@your-server
cd /path/to/laravel-ecosurvey

# Check APP_URL
grep APP_URL .env
```

**It should look like:**
```env
APP_URL=https://your-domain.com
```

**NOT:**
```env
APP_URL=http://localhost
APP_URL=
```

### If APP_URL is wrong, fix it:

```bash
# Edit .env
nano .env
# or
vi .env

# Change APP_URL to your actual domain:
APP_URL=https://your-actual-domain.com

# Save and clear cache
php artisan config:clear
php artisan cache:clear
```

### Test the fix:
1. Refresh the page in your browser
2. The existing photos should now display
3. Try uploading a new photo - it should display immediately

### Debug the URL being generated:

Check what URL is actually being generated for your images:

```bash
# In production, run tinker
php artisan tinker

# Check what URL would be generated
>>> Storage::disk('uploads')->url('data-points/test.jpg');
=> "http://localhost/files/data-points/test.jpg"  # ❌ Wrong - localhost

# After fixing APP_URL, should be:
>>> Storage::disk('uploads')->url('data-points/test.jpg');
=> "https://your-domain.com/files/data-points/test.jpg"  # ✅ Correct

# Exit tinker
>>> exit
```

### Browser Console Check:

Open your browser's Developer Tools (F12) and check the Console/Network tab:
- Look for 404 errors on image requests
- Check the URL of failed image requests
- Compare with the actual file location

Example failed URL might show:
```
http://localhost/files/data-points/photo.jpg  ❌ Wrong domain
```

Should be:
```
https://your-actual-domain.com/files/data-points/photo.jpg  ✅ Correct
```

---

## Original Permissions Troubleshooting

## Photo Storage Configuration

From `config/filesystems.php`:
```php
'uploads' => [
    'driver' => 'local',
    'root' => public_path('files'),
    'url' => env('APP_URL').'/files',
    'visibility' => 'public',
    'throw' => false,
    'report' => false,
],
```

Photos are stored to: `public/files/data-points/`

## Fix on Production Server

### 1. SSH into production server
```bash
ssh your-user@your-server
cd /path/to/laravel-ecosurvey
```

### 2. Check if directory exists
```bash
ls -la public/files
ls -la public/files/data-points
```

### 3. Create directory if missing
```bash
mkdir -p public/files/data-points
```

### 4. Fix permissions
```bash
# Set ownership to web server user (apache or www-data)
sudo chown -R www-data:www-data public/files

# OR if using Apache with different user
sudo chown -R apache:apache public/files

# Set proper permissions
chmod -R 775 public/files
```

### 5. Verify permissions
```bash
ls -la public/files
# Should show: drwxrwxr-x www-data www-data

ls -la public/files/data-points
# Should show: drwxrwxr-x www-data www-data
```

## Test Upload

1. Try creating/editing a data-point with a photo
2. Check if file was created:
```bash
ls -la public/files/data-points/
```

3. Check Laravel logs for errors:
```bash
tail -f storage/logs/laravel.log
```

## Debug Logging

The code already has debug logging. Check logs for:
- `PHOTO DEBUG 1: Before photo logic`
- `PHOTO DEBUG 2: After photo logic`
- `PHOTO DEBUG 3: After save`

Look for:
- `photoPath` value
- Storage errors
- Permission errors

## Common Issues

### Issue: Directory doesn't exist
**Solution:** Create it
```bash
mkdir -p public/files/data-points
chmod 775 public/files/data-points
```

### Issue: Wrong ownership
**Solution:** Change ownership
```bash
sudo chown -R www-data:www-data public/files
```

### Issue: Wrong permissions
**Solution:** Fix permissions
```bash
chmod -R 775 public/files
```

### Issue: SELinux blocking writes
**Solution:** Set correct context (if using SELinux)
```bash
sudo chcon -R -t httpd_sys_rw_content_t public/files
```

## Verification Script

Run this on production to check everything:

```bash
#!/bin/bash
echo "=== Photo Upload Directory Check ==="
echo ""

# Check directory exists
if [ -d "public/files/data-points" ]; then
    echo "✓ Directory exists: public/files/data-points"
else
    echo "✗ Directory missing: public/files/data-points"
    echo "  Fix: mkdir -p public/files/data-points"
fi

# Check permissions
echo ""
echo "Current permissions:"
ls -la public/files/
ls -la public/files/data-points/ 2>/dev/null || echo "  (directory doesn't exist)"

# Check ownership
echo ""
echo "Current ownership:"
stat -c '%U:%G' public/files 2>/dev/null || stat -f '%Su:%Sg' public/files
stat -c '%U:%G' public/files/data-points 2>/dev/null || stat -f '%Su:%Sg' public/files/data-points 2>/dev/null || echo "  (directory doesn't exist)"

# Check write access
echo ""
if [ -w "public/files" ]; then
    echo "✓ public/files is writable"
else
    echo "✗ public/files is NOT writable"
    echo "  Fix: chmod 775 public/files"
fi

echo ""
echo "=== Recommended Fix ==="
echo "sudo chown -R www-data:www-data public/files"
echo "chmod -R 775 public/files"
```

Save as `check-photo-permissions.sh` and run:
```bash
chmod +x check-photo-permissions.sh
./check-photo-permissions.sh
```

## Alternative: Use Storage Symlink Method

If you prefer Laravel's standard approach, you can change to use `storage/app/public`:

1. Update `.env`:
```env
FILESYSTEM_DISK=public
```

2. Update code in `reading-form.blade.php`:
```php
$photoPath = $this->photo->store('data-points', 'public');
```

3. Create symlink:
```bash
php artisan storage:link
```

4. Fix permissions:
```bash
chmod -R 775 storage/app/public
```

But the current `uploads` disk approach is fine if permissions are correct.
