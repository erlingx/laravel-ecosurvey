#!/bin/bash
# Deploy script to show database connection error on production

echo "==================================="
echo "Creating debug page for production"
echo "==================================="

# Create a simple debug page that will show the actual error
cat > public/debug-db.php << 'EOFPHP'
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Laravel EcoSurvey - Database Debug</h1>";
echo "<pre>";

// Load Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Testing database connection...\n";
    $pdo = DB::connection()->getPdo();
    echo "✅ Database connected successfully!\n";
    echo "Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
    echo "Server version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
} catch (Exception $e) {
    echo "❌ Database connection FAILED:\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nThis confirms the 500 error is due to database connection issues.\n";
    echo "\nPossible solutions:\n";
    echo "1. Check Neon dashboard for compute quota limits\n";
    echo "2. Upgrade Neon plan if free tier exceeded\n";
    echo "3. Verify database credentials in .env\n";
}

echo "</pre>";
echo "<p><strong>⚠️ DELETE THIS FILE after debugging!</strong></p>";
EOFPHP

echo ""
echo "✅ Debug file created: public/debug-db.php"
echo ""
echo "Next steps:"
echo "1. Upload this file to production: scp public/debug-db.php overstimulated.dk@linux216.unoeuro.com:laravel-ecosurvey/public/"
echo "2. Visit: https://laravel-ecosurvey.overstimulated.dk/debug-db.php"
echo "3. Check your Neon dashboard: https://console.neon.tech/"
echo "4. Delete the debug file after confirming the issue"
echo ""
echo "==================================="
echo "Neon Free Tier Limits:"
echo "==================================="
echo "- Compute time: 300 hours/month"
echo "- Storage: 3 GB"
echo "- Projects: 1"
echo ""
echo "If exceeded, you need to:"
echo "- Upgrade to Pro plan (~\$19/month for 750 compute hours)"
echo "- Or optimize auto-suspend settings"
echo ""

