#!/bin/bash
# Enable PostGIS extension in Supabase via Laravel

echo "=============================================="
echo "Enabling PostGIS Extension in Supabase"
echo "=============================================="
echo ""

echo "Connecting to database and enabling PostGIS..."
echo ""

php artisan tinker --execute="
try {
    DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');
    echo '✅ PostGIS extension enabled\n';

    DB::statement('CREATE EXTENSION IF NOT EXISTS postgis_topology;');
    echo '✅ PostGIS Topology extension enabled\n';

    // Verify
    \$version = DB::select('SELECT PostGIS_version();');
    echo 'PostGIS version: ' . \$version[0]->postgis_version . '\n';

    exit(0);
} catch (Exception \$e) {
    echo '❌ Failed: ' . \$e->getMessage() . '\n';
    echo '\nYou may need to enable PostGIS from Supabase Dashboard:\n';
    echo 'https://supabase.com/dashboard/project/uuorwkqmuucqwdexevma/database/extensions\n';
    exit(1);
}
"

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ PostGIS enabled successfully!"
    echo ""
    echo "Now run migrations again:"
    echo "php artisan migrate:fresh --seed --force"
else
    echo ""
    echo "⚠️  Could not enable PostGIS via SQL."
    echo ""
    echo "Please enable manually:"
    echo "1. Go to: https://supabase.com/dashboard/project/uuorwkqmuucqwdexevma"
    echo "2. Click: Database → Extensions (left sidebar)"
    echo "3. Search: 'postgis'"
    echo "4. Click toggle to enable 'postgis' extension"
    echo "5. Also enable 'postgis_topology' if available"
    echo "6. Wait 10 seconds"
    echo "7. Run: php artisan migrate:fresh --seed --force"
fi

echo ""

