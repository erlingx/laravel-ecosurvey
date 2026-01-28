# Queue Setup for Simply.com / UnoEuro Production

## Overview
The `cron.php` file processes queued jobs on shared hosting where persistent `queue:work` processes aren't allowed.

## Setup Instructions

### 1. Deploy the cron.php file
The file is already in your project root. After deploying to production:

```bash
# SSH into production
ssh overstimulated.dk@linux216.unoeuro.com
cd ~/public_html/laravel-ecosurvey

# Verify cron.php exists
ls -l cron.php
# Should show: -rwxr-xr-x (executable)

# If not executable, make it so:
chmod +x cron.php
```

### 2. Configure Cron Job in Simply.com cPanel

**Simply.com uses URL-based cron jobs** (different from other hosts!)

1. Log in to Simply.com cPanel
2. Navigate to **Cron Jobs** → **Opret nyt cronjob**
3. Fill in the form:

   **URL:**
   ```
   https://laravel-ecosurvey.overstimulated.dk/cron-web.php?token=7xK9mP2nQ5wR8tY4vL6jH3sA1zC0bN
   ```
   
   **Timing** (Hurtig opsætning or manual):
   - **Minut**: `*`
   - **Time**: `*`
   - **Dag på måneden**: `*`
   - **Måned**: `*`
   - **Ugedag**: `*`

4. Click **Opret** (Create)

**Security Note:**
- The `?token=` parameter prevents unauthorized access
- You can change the token in `public/cron-web.php` if needed
- Without the correct token, the cron URL returns 403 Forbidden

**For other hosting providers** (GreenGeeks, shared hosting with CLI access):
Use the command-line approach with `cron.php`:
```bash
/usr/bin/php /home/username/public_html/laravel-ecosurvey/cron.php
```

### 3. Verify It's Working

**Test the web cron URL in browser:**
```
https://laravel-ecosurvey.overstimulated.dk/cron-web.php?token=7xK9mP2nQ5wR8tY4vL6jH3sA1zC0bN
```

Should return: `OK - Processed at 2026-01-28 11:00:00`

**Test manually via SSH:**
```bash
cd ~/public_html/laravel-ecosurvey

# Option 1: Call the web URL
curl "https://laravel-ecosurvey.overstimulated.dk/cron-web.php?token=7xK9mP2nQ5wR8tY4vL6jH3sA1zC0bN"

# Option 2: Execute cron.php directly
php cron.php

# Check logs
tail -20 storage/logs/laravel.log
# Should see: "Web cron job started" and "Web cron job completed"
```

**Check if jobs are being processed:**
```bash
# Check queue status
php artisan queue:monitor database

# Check pending/failed jobs count
php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); echo 'Pending: ' . DB::table('jobs')->count() . ', Failed: ' . DB::table('failed_jobs')->count() . PHP_EOL;"
```

**Create a test job:**
```bash
# Dispatch a test job
php artisan tinker --execute="\App\Jobs\TestQueueJob::dispatch();"

# Wait 1 minute for cron to run

# Check logs for execution
tail -50 storage/logs/laravel.log | grep -i testqueuejob
# Should see: "✅ TestQueueJob executed successfully"
```

### 4. Monitor in Production

**Check cron execution logs:**
```bash
# View recent cron runs
tail -100 storage/logs/laravel.log | grep "Cron job"

# Should show entries like:
# [2026-01-28 10:00:00] production.INFO: Cron job started {"time":"...","pending_jobs":0}
# [2026-01-28 10:00:03] production.INFO: Cron job completed {"exit_code":0,"remaining_jobs":0}
```

**Check for failed jobs:**
```bash
php artisan queue:failed
# Or check database:
echo "SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;" | php artisan db
```

## What Jobs Use the Queue?

1. **EnrichDataPointWithSatelliteData** - Fetches satellite data after creating a data point
2. Any future notification jobs (emails, Slack, etc.)

## Troubleshooting

### Empty log file (storage/logs/laravel.log)

If your log file is empty even after running cron jobs:

**1. Check log level in .env:**
```bash
# .env should have:
LOG_LEVEL=info

# NOT:
LOG_LEVEL=warning  # This only logs warnings/errors, not info messages
```

**2. Check file permissions:**
```bash
# Storage directory must be writable
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Check if log file exists and is writable
ls -la storage/logs/
# Should show: -rw-r--r-- laravel.log owned by your user
```

**3. Create log file if missing:**
```bash
touch storage/logs/laravel.log
chmod 664 storage/logs/laravel.log
```

**4. Test logging manually:**
```bash
php artisan tinker --execute="\Log::info('Test log entry');"

# Then check:
tail storage/logs/laravel.log
# Should show: [2026-01-28 ...] production.INFO: Test log entry
```

**5. Check .env file exists and is loaded:**
```bash
# View current LOG_LEVEL
php artisan tinker --execute="echo 'LOG_LEVEL: ' . config('logging.default') . PHP_EOL; echo 'Level: ' . env('LOG_LEVEL', 'not set') . PHP_EOL;"
```

**6. Clear config cache:**
```bash
php artisan config:clear
php artisan cache:clear
```

### Jobs not processing

**1. Check cron job exists:**
```bash
crontab -l
# Should show your cron.php command
```

**2. Test cron.php manually:**
```bash
php cron.php
echo $?  # Should output: 0 (success)
```

**3. Check for errors (remove quiet mode temporarily):**

Edit `cron.php` and change:
```php
'--quiet' => true,
```
to:
```php
'--quiet' => false,
```

Then check logs after cron runs.

**4. Verify database connection:**
```bash
php artisan queue:monitor database
# Should output: OK
```

### Cron not running every minute

Check cPanel cron configuration - must be `* * * * *` (all asterisks).

### Too many failed jobs

```bash
# Retry failed jobs
php artisan queue:retry all

# Or clear them
php artisan queue:flush
```

## Notes

- Jobs are processed within 1 minute of being queued (not instant)
- `ps aux | grep queue:work` won't show a continuous process - this is CORRECT
- The cron process only runs for 1-50 seconds per minute
- Exit code 0 = success, anything else = error
- **Simply.com uses URL-based crons** - the web endpoint is `public/cron-web.php`
- **Other hosts use CLI-based crons** - the CLI script is `cron.php` (root folder)

## File Location

- **Production**: `/home/overstimulated.dk/public_html/laravel-ecosurvey/cron.php`
- **Local**: `E:/web/laravel-ecosurvey/cron.php`

## Logs

All queue activity is logged to `storage/logs/laravel.log` with:
- Cron execution start/stop
- Job execution status
- Pending/remaining job counts
