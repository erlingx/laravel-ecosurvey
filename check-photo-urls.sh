#!/bin/bash

# Photo URL Generation Test Script
# Run this on production to verify APP_URL is correctly configured

echo "========================================="
echo "Photo URL Generation Test"
echo "========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if we're in Laravel root
if [ ! -f "artisan" ]; then
    echo -e "${RED}✗ Error: Not in Laravel root directory${NC}"
    echo "  Please cd to your Laravel project root first"
    exit 1
fi

echo "1. Checking .env configuration..."
echo ""

# Check APP_URL
APP_URL=$(grep "^APP_URL=" .env | cut -d '=' -f2-)

if [ -z "$APP_URL" ]; then
    echo -e "${RED}✗ APP_URL is not set in .env${NC}"
    echo -e "  ${YELLOW}Fix: Add APP_URL=https://your-domain.com to .env${NC}"
elif [[ "$APP_URL" == *"localhost"* ]]; then
    echo -e "${RED}✗ APP_URL is set to localhost: $APP_URL${NC}"
    echo -e "  ${YELLOW}Fix: Change to your production domain${NC}"
elif [[ "$APP_URL" == "http://"* ]]; then
    echo -e "${YELLOW}⚠ APP_URL uses HTTP (not HTTPS): $APP_URL${NC}"
    echo "  Consider using HTTPS if available"
else
    echo -e "${GREEN}✓ APP_URL looks good: $APP_URL${NC}"
fi

echo ""
echo "2. Testing URL generation..."
echo ""

# Test with a sample file
php artisan tinker --execute="
\$url = Storage::disk('uploads')->url('data-points/test.jpg');
echo 'Generated URL: ' . \$url . PHP_EOL;
if (str_contains(\$url, 'localhost')) {
    echo 'Status: ✗ WRONG - Contains localhost' . PHP_EOL;
    exit(1);
} elseif (str_starts_with(\$url, 'http://') && !str_starts_with(\$url, 'http://localhost')) {
    echo 'Status: ⚠ WARNING - Using HTTP instead of HTTPS' . PHP_EOL;
} else {
    echo 'Status: ✓ CORRECT' . PHP_EOL;
}
"

echo ""
echo "3. Checking for actual photo files..."
echo ""

if [ -d "public/files/data-points" ]; then
    FILE_COUNT=$(find public/files/data-points -type f | wc -l)
    if [ "$FILE_COUNT" -gt 0 ]; then
        echo -e "${GREEN}✓ Found $FILE_COUNT photo file(s) in public/files/data-points/${NC}"
        echo ""
        echo "Sample files:"
        find public/files/data-points -type f | head -n 3

        # Test URL for first file
        FIRST_FILE=$(find public/files/data-points -type f | head -n 1)
        if [ -n "$FIRST_FILE" ]; then
            RELATIVE_PATH="${FIRST_FILE#public/files/}"
            echo ""
            echo "Testing URL for: $RELATIVE_PATH"
            php artisan tinker --execute="
echo 'Expected URL: ' . Storage::disk('uploads')->url('$RELATIVE_PATH');
"
        fi
    else
        echo -e "${YELLOW}⚠ Directory exists but no photos found${NC}"
    fi
else
    echo -e "${RED}✗ Directory does not exist: public/files/data-points${NC}"
    echo -e "  ${YELLOW}Fix: mkdir -p public/files/data-points${NC}"
fi

echo ""
echo "4. Browser testing instructions..."
echo ""

echo -e "${BLUE}To test in browser:${NC}"
echo "1. Open Developer Tools (F12)"
echo "2. Go to Console/Network tab"
echo "3. Try to view a data-point with a photo"
echo "4. Check for 404 errors on image requests"
echo "5. Verify the image URL uses your production domain, not localhost"

echo ""
echo "========================================="

if [ -z "$APP_URL" ] || [[ "$APP_URL" == *"localhost"* ]]; then
    echo -e "${RED}ACTION REQUIRED: Fix APP_URL in .env${NC}"
    echo ""
    echo "Run these commands:"
    echo "  nano .env  # Change APP_URL to https://your-actual-domain.com"
    echo "  php artisan config:clear"
    echo "  php artisan cache:clear"
else
    echo -e "${GREEN}Configuration looks good!${NC}"
    echo ""
    echo "If photos still don't display:"
    echo "1. Clear browser cache (Ctrl+Shift+Delete)"
    echo "2. Check browser console for errors"
    echo "3. Verify .htaccess or nginx config allows access to /files/"
fi

echo "========================================="
