#!/bin/bash
# Emergency Migration to Supabase - Quick Setup Guide
# Run this if you cannot upgrade Neon plan

echo "=============================================="
echo "Emergency Database Migration to Supabase"
echo "=============================================="
echo ""
echo "⚠️  WARNING: This will migrate your database to Supabase (free tier)"
echo ""
echo "Prerequisites:"
echo "1. Create Supabase account: https://supabase.com/dashboard"
echo "2. Create new project (choose region closest to your users)"
echo "3. Get connection details from Project Settings → Database"
echo ""
echo "=============================================="
echo ""

read -p "Have you created a Supabase project? (y/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo ""
    echo "Please complete these steps first:"
    echo ""
    echo "1. Go to: https://supabase.com/dashboard"
    echo "2. Sign up (free)"
    echo "3. Create new project:"
    echo "   - Name: laravel-ecosurvey"
    echo "   - Database password: [choose strong password]"
    echo "   - Region: Europe (eu-west-1) or closest to you"
    echo ""
    echo "4. Wait 2 minutes for project to provision"
    echo ""
    echo "5. Get connection details:"
    echo "   - Project Settings → Database → Connection string"
    echo "   - Copy 'URI' connection string"
    echo ""
    echo "6. Run this script again"
    echo ""
    exit 0
fi

echo ""
echo "Step 1: Export data from Neon (when quota resets or via local backup)"
echo "--------------------------------------------------------------------"
echo ""
echo "If you have a recent DDEV backup:"
cat << 'EOF'

# On your local machine (Windows):
ddev export-db --file=neon-backup.sql.gz

# Or if you have access to Neon:
pg_dump "postgresql://neondb_owner:npg_LWwZnUscq5A3@ep-orange-breeze-a9xvfbuw.gwc.azure.neon.tech:5432/neondb?sslmode=require" > neon-backup.sql

EOF

echo ""
read -p "Do you have a database backup file? (y/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo ""
    echo "⚠️  Cannot proceed without database backup!"
    echo ""
    echo "Options:"
    echo "1. Wait for Neon quota to reset (check Settings → Usage for date)"
    echo "2. Upgrade Neon temporarily to export data"
    echo "3. Use your latest local DDEV backup"
    echo "4. Accept data loss and start fresh"
    echo ""
    exit 1
fi

echo ""
echo "Step 2: Import to Supabase"
echo "--------------------------"
echo ""
echo "You'll need your Supabase connection string from:"
echo "Project Settings → Database → Connection string → URI"
echo ""
echo "It looks like:"
echo "postgresql://postgres:[YOUR-PASSWORD]@db.[YOUR-PROJECT-REF].supabase.co:5432/postgres"
echo ""

read -p "Enter your Supabase connection string: " SUPABASE_URL

if [ -z "$SUPABASE_URL" ]; then
    echo ""
    echo "❌ No connection string provided. Exiting."
    exit 1
fi

echo ""
read -p "Enter path to your backup file (e.g., neon-backup.sql): " BACKUP_FILE

if [ ! -f "$BACKUP_FILE" ]; then
    echo ""
    echo "❌ Backup file not found: $BACKUP_FILE"
    exit 1
fi

echo ""
echo "Importing to Supabase..."
echo ""

if [[ $BACKUP_FILE == *.gz ]]; then
    gunzip < "$BACKUP_FILE" | psql "$SUPABASE_URL"
else
    psql "$SUPABASE_URL" < "$BACKUP_FILE"
fi

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Database imported successfully!"
    echo ""
    echo "Step 3: Update production .env"
    echo "-------------------------------"
    echo ""
    echo "Parse your Supabase connection string and update these values:"
    echo ""

    # Parse connection string
    if [[ $SUPABASE_URL =~ postgresql://([^:]+):([^@]+)@([^:]+):([0-9]+)/([^?]+) ]]; then
        DB_USERNAME="${BASH_REMATCH[1]}"
        DB_PASSWORD="${BASH_REMATCH[2]}"
        DB_HOST="${BASH_REMATCH[3]}"
        DB_PORT="${BASH_REMATCH[4]}"
        DB_DATABASE="${BASH_REMATCH[5]}"

        echo "DB_CONNECTION=pgsql"
        echo "DB_HOST=$DB_HOST"
        echo "DB_PORT=$DB_PORT"
        echo "DB_DATABASE=$DB_DATABASE"
        echo "DB_USERNAME=$DB_USERNAME"
        echo "DB_PASSWORD=$DB_PASSWORD"
        echo "DB_SSLMODE=require"
        echo ""
        echo "# Remove these Neon-specific settings:"
        echo "# DB_OPTIONS=endpoint=ep-orange-breeze-a9xvfbuw"
        echo ""

        echo "Copy these values and:"
        echo "1. Update .env.production locally"
        echo "2. Upload to production: scp .env.production overstimulated.dk@linux216.unoeuro.com:laravel-ecosurvey/.env"
        echo "3. SSH to production and run:"
        echo "   cd laravel-ecosurvey"
        echo "   php artisan config:clear"
        echo "   php artisan config:cache"
        echo "   php artisan migrate --force"
        echo ""
    else
        echo "⚠️  Could not parse connection string. Manual update required."
        echo ""
        echo "In .env.production, update:"
        echo "DB_CONNECTION=pgsql"
        echo "DB_HOST=[from Supabase]"
        echo "DB_PORT=5432"
        echo "DB_DATABASE=[from Supabase]"
        echo "DB_USERNAME=[from Supabase]"
        echo "DB_PASSWORD=[from Supabase]"
        echo "DB_SSLMODE=require"
        echo ""
    fi

    echo "Step 4: Remove Neon-specific connector"
    echo "---------------------------------------"
    echo ""
    echo "The custom NeonPostgresConnector is no longer needed."
    echo "After updating .env, test the connection."
    echo ""

else
    echo ""
    echo "❌ Import failed. Check error messages above."
    exit 1
fi

echo ""
echo "=============================================="
echo "✅ Migration Guide Complete"
echo "=============================================="
echo ""
echo "Next steps:"
echo "1. Update .env.production with Supabase credentials (see above)"
echo "2. Upload to production"
echo "3. Clear caches on production"
echo "4. Test site: https://laravel-ecosurvey.overstimulated.dk/"
echo ""
echo "Supabase Free Tier:"
echo "- 500 MB database"
echo "- Unlimited compute hours"
echo "- Auto-pause after 1 week inactivity"
echo "- Perfect for low-traffic sites"
echo ""

