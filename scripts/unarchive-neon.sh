#!/bin/bash
# Unarchive Neon Database by Attempting Connection
# Run on production: overstimulated.dk@linux216.unoeuro.com

echo "=============================================="
echo "Neon Database Unarchive via Connection Test"
echo "=============================================="
echo ""
echo "The popup message says: 'Connecting to the branch will unarchive it.'"
echo "This script attempts a real database connection to trigger unarchive."
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Attempt 1
echo -e "${YELLOW}🔄 Attempt 1: Connecting to database...${NC}"
php artisan tinker --execute="
try {
    \$pdo = DB::connection()->getPdo();
    echo '✅ Connected successfully!\n';
    echo 'Server: ' . \$pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . '\n';
    exit(0);
} catch (Exception \$e) {
    echo '❌ Connection failed: ' . \$e->getMessage() . '\n';
    exit(1);
}
" 2>&1

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✅ Database is already active!${NC}"
    echo ""
    echo "Proceeding with cache clearing..."
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    echo ""
    echo -e "${GREEN}✅ Recovery complete!${NC}"
    echo "Test your site: https://laravel-ecosurvey.overstimulated.dk/"
    exit 0
fi

echo ""
echo -e "${YELLOW}Expected behavior: First attempt fails with archived/suspended error${NC}"
echo -e "${YELLOW}This triggers Neon to start unarchiving the compute endpoint...${NC}"
echo ""
echo -e "${YELLOW}⏳ Waiting 45 seconds for compute endpoint to activate...${NC}"

# Progress bar
for i in {1..45}; do
    echo -n "."
    sleep 1
    if [ $((i % 15)) -eq 0 ]; then
        echo -n " ${i}s"
    fi
done
echo ""

# Attempt 2
echo ""
echo -e "${YELLOW}🔄 Attempt 2: Connecting to database...${NC}"
php artisan tinker --execute="
try {
    \$pdo = DB::connection()->getPdo();
    echo '✅ Connected successfully!\n';
    echo 'Server: ' . \$pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . '\n';
    exit(0);
} catch (Exception \$e) {
    echo '❌ Connection still failed: ' . \$e->getMessage() . '\n';
    exit(1);
}
" 2>&1

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✅ Success! Database is now unarchived and active!${NC}"
    echo ""
    echo "Proceeding with cache clearing..."
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    echo ""
    echo "Removing debug file..."
    rm -f public/debug-db.php

    echo ""
    echo "Testing homepage..."
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://laravel-ecosurvey.overstimulated.dk/)

    if [ "$HTTP_CODE" = "200" ]; then
        echo -e "${GREEN}✅ Homepage returned HTTP $HTTP_CODE${NC}"
    else
        echo -e "${YELLOW}⚠️  Homepage returned HTTP $HTTP_CODE${NC}"
    fi

    echo ""
    echo "=============================================="
    echo -e "${GREEN}Recovery Complete!${NC}"
    echo "=============================================="
    echo ""
    echo "✅ Database unarchived and connected"
    echo "✅ Application caches cleared and rebuilt"
    echo "✅ Debug file removed"
    echo ""
    echo "Next steps:"
    echo "1. Test site: https://laravel-ecosurvey.overstimulated.dk/"
    echo "2. Configure auto-suspend in Neon:"
    echo "   Settings → Compute → Set to 5 minutes"
    echo "3. Enable billing alerts:"
    echo "   Settings → Billing → Usage alerts at 240 hours"
    echo ""
    exit 0
else
    echo ""
    echo -e "${RED}❌ Still unable to connect after 45 seconds.${NC}"
    echo ""
    echo "Possible reasons:"
    echo "1. Compute endpoint needs more time (wait 60+ seconds)"
    echo "2. Manual activation required in Neon dashboard"
    echo "3. Different issue (check error message above)"
    echo ""
    echo "Options:"
    echo "A. Wait 2 more minutes and run this script again"
    echo "B. Check Neon dashboard: https://console.neon.tech/"
    echo "   - Go to Settings → Compute"
    echo "   - Look for 'Resume' or 'Start' button"
    echo "   - Check compute endpoint status"
    echo ""
    echo "C. Try manual psql connection to trigger unarchive:"
    echo "   Check connection string in Neon UI (Connect button popup)"
    echo ""
    exit 1
fi

