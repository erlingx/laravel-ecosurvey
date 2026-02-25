#!/bin/bash
# Neon Recovery Script - After Unarchiving Branch
# Run on production server: overstimulated.dk@linux216.unoeuro.com

echo "=========================================="
echo "Neon Database Recovery & Verification"
echo "=========================================="
echo ""

# Test database connection
echo "Testing database connection..."
php artisan tinker --execute="
try {
    \$pdo = DB::connection()->getPdo();
    echo '✅ Database connected successfully!\n';
    echo 'Server: ' . \$pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . '\n';
    exit(0);
} catch (Exception \$e) {
    echo '❌ Connection failed: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

if [ $? -eq 0 ]; then
    echo ""
    echo "Clearing application caches..."
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    echo ""
    echo "Removing debug files..."
    rm -f public/debug-db.php

    echo ""
    echo "✅ Recovery complete! Site should be online."
    echo ""
    echo "Test: https://laravel-ecosurvey.overstimulated.dk/"
else
    echo ""
    echo "❌ Database still not accessible."
    echo ""
    echo "Next steps:"
    echo "1. Check Neon dashboard: https://console.neon.tech/"
    echo "2. Verify branch is unarchived"
    echo "3. Wait 2-3 minutes and try again"
fi

echo ""
echo "=========================================="

