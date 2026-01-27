#!/bin/bash
# Quick PostgreSQL Connection Test for Simply.com
# Tests both Neon and Supabase to see if external PostgreSQL works

echo "=========================================="
echo "PostgreSQL Connection Test for Simply.com"
echo "=========================================="
echo ""

# Test 1: Neon PostgreSQL (Port 5432)
echo "Test 1: Neon PostgreSQL (Port 5432)..."
php -r 'try { $pdo = new PDO("pgsql:host=ep-orange-breeze-a9xvfbuw.gwc.azure.neon.tech;port=5432;dbname=neondb;sslmode=require", "neondb_owner", "npg_LWwZnUscq5A3"); echo "✅ SUCCESS: Neon connection works on port 5432!\n"; } catch (Exception $e) { echo "❌ BLOCKED: " . $e->getMessage() . "\n"; }'
echo ""

# Test 2: Neon PostgreSQL (Port 6543)
echo "Test 2: Neon PostgreSQL (Port 6543 - pooler)..."
php -r 'try { $pdo = new PDO("pgsql:host=ep-orange-breeze-a9xvfbuw.gwc.azure.neon.tech;port=6543;dbname=neondb;sslmode=require", "neondb_owner", "npg_LWwZnUscq5A3"); echo "✅ SUCCESS: Neon connection works on port 6543!\n"; } catch (Exception $e) { echo "❌ BLOCKED: " . $e->getMessage() . "\n"; }'
echo ""

# Test 3: Supabase PostgreSQL (Port 5432)
echo "Test 3: Supabase PostgreSQL (Port 5432)..."
php -r 'try { $pdo = new PDO("pgsql:host=aws-1-eu-west-1.pooler.supabase.com;port=5432;dbname=postgres;sslmode=require", "postgres.sssajdwrbbkhcmxduipt", "YHtifRaYH4ld5q7t"); echo "✅ SUCCESS: Supabase connection works on port 5432!\n"; } catch (Exception $e) { echo "❌ BLOCKED: " . $e->getMessage() . "\n"; }'
echo ""

# Test 4: Supabase PostgreSQL (Port 6543)
echo "Test 4: Supabase PostgreSQL (Port 6543 - pooler)..."
php -r 'try { $pdo = new PDO("pgsql:host=aws-1-eu-west-1.pooler.supabase.com;port=6543;dbname=postgres;sslmode=require", "postgres.sssajdwrbbkhcmxduipt", "YHtifRaYH4ld5q7t"); echo "✅ SUCCESS: Supabase connection works on port 6543!\n"; } catch (Exception $e) { echo "❌ BLOCKED: " . $e->getMessage() . "\n"; }'
echo ""

echo "=========================================="
echo "Summary:"
echo "=========================================="
echo "If ALL tests show '❌ BLOCKED':"
echo "→ Simply.com blocks external PostgreSQL (like GreenGeeks)"
echo "→ You need VPS hosting or switch to Hetzner Cloud"
echo ""
echo "If ANY test shows '✅ SUCCESS':"
echo "→ External PostgreSQL works on Simply.com!"
echo "→ Use the working host/port in your .env file"
echo "=========================================="
