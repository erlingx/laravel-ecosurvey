#!/usr/bin/env php
<?php

/**
 * Production Queue & Logging Verification Script
 *
 * Run this on production to verify:
 * - Logging is working
 * - Queue can connect to database
 * - File permissions are correct
 * - .env configuration is loaded
 */
echo "🔍 EcoSurvey Production Verification\n";
echo "====================================\n\n";

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$hasErrors = false;

// Test 1: Check .env is loaded
echo "1. Environment Configuration\n";
echo '   - APP_ENV: '.env('APP_ENV', 'NOT SET')."\n";
echo '   - LOG_LEVEL: '.env('LOG_LEVEL', 'NOT SET')."\n";
echo '   - LOG_STACK: '.env('LOG_STACK', 'NOT SET')."\n";
echo '   - QUEUE_CONNECTION: '.env('QUEUE_CONNECTION', 'NOT SET')."\n";

if (env('LOG_LEVEL') === 'warning') {
    echo "   ⚠️  WARNING: LOG_LEVEL is 'warning' - change to 'info' to see cron logs\n";
    $hasErrors = true;
} else {
    echo "   ✅ LOG_LEVEL is OK\n";
}
echo "\n";

// Test 2: Check storage permissions
echo "2. Storage Permissions\n";
$logPath = storage_path('logs/laravel.log');
$logsDir = storage_path('logs');

if (! is_dir($logsDir)) {
    echo "   ❌ logs directory doesn't exist: $logsDir\n";
    $hasErrors = true;
} elseif (! is_writable($logsDir)) {
    echo "   ❌ logs directory not writable: $logsDir\n";
    echo "      Run: chmod -R 775 storage\n";
    $hasErrors = true;
} else {
    echo "   ✅ logs directory is writable\n";
}

if (! file_exists($logPath)) {
    echo "   ⚠️  Log file doesn't exist, will be created\n";
} elseif (! is_writable($logPath)) {
    echo "   ❌ Log file not writable: $logPath\n";
    echo "      Run: chmod 664 $logPath\n";
    $hasErrors = true;
} else {
    echo "   ✅ Log file is writable\n";
}
echo "\n";

// Test 3: Test logging
echo "3. Logging Test\n";
try {
    \Log::info('✅ Verification script test log', ['timestamp' => now()->toDateTimeString()]);
    echo "   ✅ Log written successfully\n";
    echo "   Check: tail -5 storage/logs/laravel.log\n";
} catch (Exception $e) {
    echo '   ❌ Logging failed: '.$e->getMessage()."\n";
    $hasErrors = true;
}
echo "\n";

// Test 4: Database connection
echo "4. Database Connection\n";
try {
    DB::connection()->getPdo();
    echo "   ✅ Database connected\n";
    echo '   - Host: '.config('database.connections.pgsql.host')."\n";
    echo '   - Database: '.config('database.connections.pgsql.database')."\n";
} catch (Exception $e) {
    echo '   ❌ Database connection failed: '.$e->getMessage()."\n";
    $hasErrors = true;
}
echo "\n";

// Test 5: Queue table exists
echo "5. Queue Table\n";
try {
    $jobsCount = DB::table('jobs')->count();
    $failedCount = DB::table('failed_jobs')->count();
    echo "   ✅ Queue tables accessible\n";
    echo "   - Pending jobs: $jobsCount\n";
    echo "   - Failed jobs: $failedCount\n";
} catch (Exception $e) {
    echo '   ❌ Queue table error: '.$e->getMessage()."\n";
    echo "      Run: php artisan migrate --force\n";
    $hasErrors = true;
}
echo "\n";

// Test 6: Check cron.php exists and is executable
echo "6. Cron Script\n";
$cronPath = __DIR__.'/cron.php';
if (! file_exists($cronPath)) {
    echo "   ❌ cron.php not found: $cronPath\n";
    $hasErrors = true;
} else {
    echo "   ✅ cron.php exists\n";
    $perms = substr(sprintf('%o', fileperms($cronPath)), -4);
    echo "   - Permissions: $perms\n";
    if (! is_executable($cronPath)) {
        echo "   ⚠️  Not executable - run: chmod +x cron.php\n";
    }
}
echo "\n";

// Summary
echo "====================================\n";
if ($hasErrors) {
    echo "❌ ERRORS FOUND - Fix the issues above\n";
    exit(1);
} else {
    echo "✅ ALL CHECKS PASSED\n";
    echo "\nNext steps:\n";
    echo "1. Run cron manually: php cron.php\n";
    echo "2. Check logs: tail -20 storage/logs/laravel.log\n";
    echo "3. Create test data point and verify satellite job runs\n";
    exit(0);
}
