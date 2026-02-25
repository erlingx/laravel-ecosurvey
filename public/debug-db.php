<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo '<h1>Laravel EcoSurvey - Database Debug</h1>';
echo '<pre>';

// Load Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Testing database connection...\n\n";
    $pdo = DB::connection()->getPdo();
    echo "✅ Database connected successfully!\n\n";
    echo 'Driver: '.$pdo->getAttribute(PDO::ATTR_DRIVER_NAME)."\n";
    echo 'Server version: '.$pdo->getAttribute(PDO::ATTR_SERVER_VERSION)."\n";

    // Test a simple query
    $result = DB::select('SELECT version()');
    echo 'PostgreSQL version: '.$result[0]->version."\n";

} catch (Exception $e) {
    echo "❌ Database connection FAILED:\n\n";
    echo 'Error: '.$e->getMessage()."\n\n";
    echo 'Error Code: '.$e->getCode()."\n\n";

    if (strpos($e->getMessage(), 'compute time quota') !== false) {
        echo "🚨 ROOT CAUSE: Neon free tier compute quota exceeded!\n\n";
        echo "SOLUTIONS:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "1. IMMEDIATE: Check Neon Dashboard\n";
        echo "   https://console.neon.tech/app/projects\n";
        echo "   View usage: Settings → Usage\n\n";
        echo "2. UPGRADE PLAN (Recommended for production)\n";
        echo "   Free:  300 compute hours/month\n";
        echo "   Pro:   750 compute hours/month + better features\n";
        echo "   Scale: Unlimited compute hours\n\n";
        echo "3. OPTIMIZE AUTO-SUSPEND\n";
        echo "   Settings → Compute → Auto-suspend delay\n";
        echo "   Reduce idle time before suspension\n\n";
        echo "4. TEMPORARY: Reset if early in billing cycle\n";
        echo "   Free tier resets monthly\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    } else {
        echo "This confirms the 500 error is due to database connection issues.\n\n";
        echo "Possible solutions:\n";
        echo "1. Check Neon dashboard for compute quota limits\n";
        echo "2. Upgrade Neon plan if free tier exceeded\n";
        echo "3. Verify database credentials in .env\n";
    }
}

echo '</pre>';
echo '<hr>';
echo '<p><strong>⚠️ DELETE THIS FILE (public/debug-db.php) after debugging!</strong></p>';
echo '<p>Run: <code>rm public/debug-db.php</code></p>';
