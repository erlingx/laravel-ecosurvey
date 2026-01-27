#!/bin/bash
# Search script to find Laravel files on GreenGeeks server
# IMPORTANT: You have 4+ Laravel projects on this server
# This script will show ALL of them to avoid confusion

echo "=========================================="
echo "Searching for Laravel EcoSurvey files..."
echo "=========================================="
echo ""

echo "1. ALL Laravel projects on this server (by artisan file):"
find ~/public_html -name "artisan" -type f -maxdepth 3 2>/dev/null
echo ""

echo "2. ALL .git folders (shows where git repos are):"
find ~/public_html -name ".git" -type d -maxdepth 3 2>/dev/null
echo ""

echo "3. Checking laravel-ecosurvey.electrominds.dk directory specifically:"
ls -la ~/public_html/laravel-ecosurvey.electrominds.dk/
echo ""

echo "4. Checking laravel-ecosurvey.electrominds.dk/public directory:"
ls -la ~/public_html/laravel-ecosurvey.electrominds.dk/public/ 2>/dev/null || echo "Directory doesn't exist or is empty"
echo ""

echo "5. Searching for EcoSurvey-specific files (to confirm it's OUR project):"
echo "   Looking for: Campaign model, SatelliteAnalysis, EnvironmentalMetric..."
find ~/public_html -name "Campaign.php" -type f 2>/dev/null
find ~/public_html -name "SatelliteAnalysis.php" -type f 2>/dev/null
find ~/public_html -name "EnvironmentalMetric.php" -type f 2>/dev/null
echo ""

echo "6. Disk usage for ALL subdomain directories:"
du -sh ~/public_html/*/ 2>/dev/null | sort -hr
echo ""

echo "7. List all subdirectories (to see your other Laravel projects):"
ls -d ~/public_html/*/ 2>/dev/null
echo ""

echo "8. Checking if EcoSurvey files are in laravel-ecosurvey.electrominds.dk:"
if [ -f ~/public_html/laravel-ecosurvey.electrominds.dk/artisan ]; then
    echo "✅ FOUND: artisan file in laravel-ecosurvey.electrominds.dk/"
    echo "   Checking composer.json for project name..."
    grep -i "ecosurvey\|name" ~/public_html/laravel-ecosurvey.electrominds.dk/composer.json 2>/dev/null | head -3
else
    echo "❌ NOT FOUND: artisan file missing in laravel-ecosurvey.electrominds.dk/"
fi
echo ""

echo "9. Checking if files accidentally went to PUBLIC_HTML root:"
if [ -f ~/public_html/artisan ]; then
    echo "⚠️  WARNING: Found artisan in public_html root (wrong location!)"
    echo "   Checking which project it is..."
    grep -i "name" ~/public_html/composer.json 2>/dev/null | head -1
else
    echo "✅ OK: No artisan in public_html root"
fi
echo ""

echo "10. Final check - Count PHP files in ecosurvey subdomain:"
file_count=$(find ~/public_html/laravel-ecosurvey.electrominds.dk -name "*.php" -type f 2>/dev/null | wc -l)
echo "   Found $file_count PHP files"
if [ "$file_count" -gt 100 ]; then
    echo "   ✅ Project files exist (>100 PHP files found)"
elif [ "$file_count" -gt 10 ]; then
    echo "   ⚠️  Some files exist but may be incomplete ($file_count files)"
else
    echo "   ❌ Almost no files - project not properly cloned ($file_count files)"
fi

echo ""
echo "=========================================="
echo "Search complete!"
echo "=========================================="


