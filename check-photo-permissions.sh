#!/bin/bash

# Photo Upload Directory Verification Script
# Run this on production server to check photo upload configuration

echo "========================================="
echo "Photo Upload Directory Check"
echo "========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if we're in Laravel root
if [ ! -f "artisan" ]; then
    echo -e "${RED}✗ Error: Not in Laravel root directory${NC}"
    echo "  Please cd to your Laravel project root first"
    exit 1
fi

echo "1. Checking directory structure..."
echo ""

# Check public/files directory
if [ -d "public/files" ]; then
    echo -e "${GREEN}✓${NC} public/files exists"
else
    echo -e "${RED}✗${NC} public/files is missing"
    echo -e "  ${YELLOW}Fix: mkdir -p public/files${NC}"
fi

# Check public/files/data-points directory
if [ -d "public/files/data-points" ]; then
    echo -e "${GREEN}✓${NC} public/files/data-points exists"
else
    echo -e "${RED}✗${NC} public/files/data-points is missing"
    echo -e "  ${YELLOW}Fix: mkdir -p public/files/data-points${NC}"
fi

echo ""
echo "2. Checking permissions..."
echo ""

# Check if public/files is writable
if [ -w "public/files" ]; then
    echo -e "${GREEN}✓${NC} public/files is writable"
else
    echo -e "${RED}✗${NC} public/files is NOT writable"
    echo -e "  ${YELLOW}Fix: chmod 775 public/files${NC}"
fi

# Check if public/files/data-points is writable (if it exists)
if [ -d "public/files/data-points" ]; then
    if [ -w "public/files/data-points" ]; then
        echo -e "${GREEN}✓${NC} public/files/data-points is writable"
    else
        echo -e "${RED}✗${NC} public/files/data-points is NOT writable"
        echo -e "  ${YELLOW}Fix: chmod 775 public/files/data-points${NC}"
    fi
fi

echo ""
echo "3. Checking ownership and permissions..."
echo ""

# Show detailed permissions
if [ -d "public/files" ]; then
    echo "public/files:"
    ls -ld public/files

    if [ -d "public/files/data-points" ]; then
        echo ""
        echo "public/files/data-points:"
        ls -ld public/files/data-points
    fi
fi

echo ""
echo "4. Checking web server user..."
echo ""

# Try to detect web server user
WEB_USER=""
if ps aux | grep -E "(apache|httpd)" | grep -v grep > /dev/null; then
    WEB_USER=$(ps aux | grep -E "(apache|httpd)" | grep -v grep | head -n 1 | awk '{print $1}')
    echo "Detected Apache running as: $WEB_USER"
elif ps aux | grep "nginx" | grep -v grep > /dev/null; then
    WEB_USER=$(ps aux | grep "nginx" | grep -v grep | grep "worker" | head -n 1 | awk '{print $1}')
    echo "Detected Nginx running as: $WEB_USER"
else
    echo "Could not detect web server user"
    echo "Common users: www-data (Debian/Ubuntu), apache (RHEL/CentOS)"
fi

echo ""
echo "5. Recommended fixes..."
echo ""

if [ ! -d "public/files/data-points" ] || [ ! -w "public/files/data-points" ]; then
    echo -e "${YELLOW}Run these commands to fix:${NC}"
    echo ""
    echo "# Create directory"
    echo "mkdir -p public/files/data-points"
    echo ""
    echo "# Fix permissions"
    echo "chmod -R 775 public/files"
    echo ""
    if [ -n "$WEB_USER" ]; then
        echo "# Fix ownership (you may need sudo)"
        echo "sudo chown -R $WEB_USER:$WEB_USER public/files"
    else
        echo "# Fix ownership (replace USER with your web server user)"
        echo "sudo chown -R www-data:www-data public/files"
        echo "# OR"
        echo "sudo chown -R apache:apache public/files"
    fi
else
    echo -e "${GREEN}✓ Everything looks good!${NC}"
fi

echo ""
echo "========================================="
echo "Check complete"
echo "========================================="
