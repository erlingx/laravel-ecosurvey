#!/usr/bin/env php
<?php

/**
 * Inspect Jobs Table
 * Shows what's actually in the jobs table right now
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "📊 Jobs Table Inspection\n";
echo "========================\n\n";

// Database info
echo "Database Connection:\n";
echo '  - Host: '.config('database.connections.'.config('database.default').'.host')."\n";
echo '  - Database: '.config('database.connections.'.config('database.default').'.database')."\n";
echo '  - Username: '.config('database.connections.'.config('database.default').'.username')."\n\n";

// Count jobs
$pending = DB::table('jobs')->count();
$failed = DB::table('failed_jobs')->count();

echo "Job Counts:\n";
echo "  - Pending: $pending\n";
echo "  - Failed: $failed\n\n";

// Show recent jobs if any
if ($pending > 0) {
    echo "Recent Pending Jobs (last 5):\n";
    $jobs = DB::table('jobs')->orderBy('id', 'desc')->limit(5)->get();

    foreach ($jobs as $job) {
        echo "  - Job #{$job->id}\n";
        echo "    Queue: {$job->queue}\n";
        echo "    Attempts: {$job->attempts}\n";
        echo '    Available at: '.date('Y-m-d H:i:s', $job->available_at)."\n";
        echo '    Created at: '.date('Y-m-d H:i:s', $job->created_at)."\n";

        // Try to decode payload to see job class
        $payload = json_decode($job->payload, true);
        if (isset($payload['displayName'])) {
            echo "    Job: {$payload['displayName']}\n";
        }
        echo "\n";
    }
} else {
    echo "✅ No pending jobs in queue\n\n";
}

// Show recent failed jobs if any
if ($failed > 0) {
    echo "Recent Failed Jobs (last 3):\n";
    $failedJobs = DB::table('failed_jobs')->orderBy('id', 'desc')->limit(3)->get();

    foreach ($failedJobs as $job) {
        echo "  - Failed Job #{$job->id}\n";
        echo "    Queue: {$job->queue}\n";
        echo "    Failed at: {$job->failed_at}\n";

        $payload = json_decode($job->payload, true);
        if (isset($payload['displayName'])) {
            echo "    Job: {$payload['displayName']}\n";
        }

        // Show first 200 chars of exception
        $exception = substr($job->exception, 0, 200);
        echo "    Error: {$exception}...\n";
        echo "\n";
    }
}

echo "========================\n";
echo "To manually process queue: php artisan queue:work database --stop-when-empty\n";
echo "To retry failed jobs: php artisan queue:retry all\n";
