#!/usr/bin/env php
<?php

/**
 * Cron Job Entry Point for Simply.com / UnoEuro Shared Hosting
 *
 * This file is called by cPanel cron job every minute.
 * It processes all queued jobs and then exits.
 *
 * Setup in cPanel > Cron Jobs:
 * Frequency: Every 1 minute
 * Command: /usr/bin/php /home/overstimulated.dk/public_html/laravel-ecosurvey/cron.php
 */

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Log cron execution
\Log::info('Cron job started', [
    'time' => now()->toDateTimeString(),
    'pending_jobs' => DB::table('jobs')->count(),
]);

// Process queue jobs
// --stop-when-empty: Exit after processing all jobs (don't wait for new ones)
// --max-time=50: Safety timeout (before cron kills the process)
// --tries=3: Retry failed jobs up to 3 times
$exitCode = $kernel->call('queue:work', [
    'connection' => 'database',
    '--stop-when-empty' => true,
    '--max-time' => 50,
    '--tries' => 3,
    '--quiet' => true,
]);

// Log completion
\Log::info('Cron job completed', [
    'exit_code' => $exitCode,
    'remaining_jobs' => DB::table('jobs')->count(),
]);

// Return exit code
exit($exitCode);
