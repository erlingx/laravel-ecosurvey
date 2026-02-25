#!/bin/bash
# Supabase Migration Deployment Script
# Run this AFTER creating Supabase project and updating .env.production.supabase

set -e  # Exit on any error

echo "=============================================="
echo "Supabase Migration - Deployment Script"
echo "=============================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if .env.production.supabase exists
if [ ! -f ".env.production.supabase" ]; then
    echo -e "${RED}❌ Error: .env.production.supabase not found!${NC}"
    echo ""
    echo "Please:"
    echo "1. Copy .env.production.supabase template"
    echo "2. Update with your Supabase credentials"
    echo "3. Run this script again"
    echo ""
    exit 1
fi

# Check if DB credentials are still placeholders
if grep -q "YOUR-PROJECT-REF-HERE\|YOUR-SUPABASE-PASSWORD-HERE" .env.production.supabase; then
    echo -e "${RED}❌ Error: .env.production.supabase still has placeholder values!${NC}"
    echo ""
    echo "Please update these values in .env.production.supabase:"
    echo "- DB_USERNAME=postgres.[YOUR-PROJECT-REF-HERE]"
    echo "- DB_PASSWORD=[YOUR-SUPABASE-PASSWORD-HERE]"
    echo ""
    echo "Get them from: Supabase Dashboard → Settings → Database → Connection string"
    echo ""
    exit 1
fi

echo -e "${YELLOW}Pre-deployment Checklist:${NC}"
echo ""
echo "Have you completed these steps?"
echo "1. ✅ Created Supabase project"
echo "2. ✅ Enabled PostGIS extension in Supabase"
echo "3. ✅ Updated .env.production.supabase with real credentials"
echo "4. ✅ Tested connection locally (optional)"
echo ""

read -p "Continue with deployment? (y/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Deployment cancelled."
    exit 0
fi

# Step 1: Upload .env file
echo ""
echo -e "${BLUE}Step 1: Uploading .env to production...${NC}"
scp .env.production.supabase overstimulated.dk@linux216.unoeuro.com:laravel-ecosurvey/.env

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ .env uploaded successfully${NC}"
else
    echo -e "${RED}❌ Failed to upload .env${NC}"
    exit 1
fi

# Step 2: SSH and run migration
echo ""
echo -e "${BLUE}Step 2: Connecting to production server...${NC}"
echo ""

ssh overstimulated.dk@linux216.unoeuro.com << 'ENDSSH'
set -e

cd laravel-ecosurvey

echo ""
echo "================================================"
echo "Running database migration on production server"
echo "================================================"
echo ""

# Test database connection
echo "🔗 Testing Supabase connection..."
php artisan tinker --execute="
try {
    \$pdo = DB::connection()->getPdo();
    echo '✅ Connected to Supabase successfully!\n';
    echo 'PostgreSQL version: ' . \$pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . '\n';
    exit(0);
} catch (Exception \$e) {
    echo '❌ Connection failed: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

if [ $? -ne 0 ]; then
    echo ""
    echo "❌ Database connection failed!"
    echo "Check your Supabase credentials in .env"
    exit 1
fi

echo ""
echo "🧹 Clearing caches..."
php artisan optimize:clear

echo ""
echo "🗄️  Running fresh migrations with seeders..."
echo "⚠️  This will DROP all tables and recreate them!"
php artisan migrate:fresh --seed --force

if [ $? -ne 0 ]; then
    echo ""
    echo "❌ Migration failed!"
    exit 1
fi

echo ""
echo "📦 Rebuilding caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo ""
echo "🧹 Cleaning up debug files..."
rm -f public/debug-db.php
rm -f public/debug.php

echo ""
echo "🧪 Testing site..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://laravel-ecosurvey.overstimulated.dk/)

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ Homepage returned HTTP $HTTP_CODE"
else
    echo "⚠️  Homepage returned HTTP $HTTP_CODE (may need time to load)"
fi

echo ""
echo "================================================"
echo "✅ Deployment Complete!"
echo "================================================"
echo ""
echo "Your site should now be online:"
echo "https://laravel-ecosurvey.overstimulated.dk/"
echo ""

ENDSSH

# Check SSH command result
if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}=============================================="
    echo "✅ SUCCESS - Migration Complete!"
    echo "==============================================${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Visit: https://laravel-ecosurvey.overstimulated.dk/"
    echo "2. Test user registration and login"
    echo "3. Verify survey features work"
    echo "4. Check logs if any issues"
    echo ""
    echo "Supabase Dashboard: https://supabase.com/dashboard"
    echo ""
    echo -e "${YELLOW}⚠️  Remember:${NC}"
    echo "- Database is fresh with seeded data"
    echo "- All old production data is gone"
    echo "- Supabase free tier has unlimited compute hours!"
    echo ""
else
    echo ""
    echo -e "${RED}=============================================="
    echo "❌ DEPLOYMENT FAILED"
    echo "==============================================${NC}"
    echo ""
    echo "Check the error messages above."
    echo ""
    echo "Common issues:"
    echo "1. Wrong Supabase credentials in .env"
    echo "2. PostGIS extension not enabled in Supabase"
    echo "3. Migration syntax errors"
    echo "4. SSH connection issues"
    echo ""
    echo "SSH to production to debug:"
    echo "ssh overstimulated.dk@linux216.unoeuro.com"
    echo "cd laravel-ecosurvey"
    echo "tail -50 storage/logs/laravel.log"
    echo ""
fi

