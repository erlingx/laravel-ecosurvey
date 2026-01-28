<?php

/**
 * Web-Accessible Cron Endpoint for Simply.com
 *
 * Simply.com cron jobs work by calling URLs, not executing files directly.
 * This file is placed in the public folder so it's accessible via HTTP.
 *
 * URL: https://laravel-ecosurvey.overstimulated.dk/cron-web.php
 *
 * Security: Basic token authentication to prevent unauthorized access
 */

// Security check - require a secret token
$expectedToken = '7xK9mP2nQ5wR8tY4vL6jH3sA1zC0bN'; // Change this to a random string
$providedToken = $_GET['token'] ?? '';

if ($providedToken !== $expectedToken) {
    http_response_code(403);
    exit('Unauthorized - Invalid token');
}

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Log cron execution
\Log::info('Web cron job started', [
    'time' => now()->toDateTimeString(),
    'pending_jobs' => DB::table('jobs')->count(),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
]);

// Process queue jobs
$exitCode = $kernel->call('queue:work', [
    'connection' => 'database',
    '--stop-when-empty' => true,
    '--max-time' => 50,
    '--tries' => 3,
    '--quiet' => true,
]);

// Log completion
\Log::info('Web cron job completed', [
    'exit_code' => $exitCode,
    'remaining_jobs' => DB::table('jobs')->count(),
]);

// Return success response
http_response_code(200);
echo 'OK - Processed at '.now()->toDateTimeString();
exit($exitCode);
