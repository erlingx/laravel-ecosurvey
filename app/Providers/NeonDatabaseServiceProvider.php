<?php

namespace App\Providers;

use App\Database\Connectors\NeonPostgresConnector;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider for PostgreSQL Database Support (Neon/Supabase compatible)
 *
 * This provider registers a custom PostgreSQL connector that adds
 * SNI (Server Name Indication) support for Neon databases on hosts
 * with older PostgreSQL client libraries.
 *
 * Also works with Supabase and standard PostgreSQL without any issues.
 * The connector only adds options when DB_OPTIONS is set in .env.
 */
class NeonDatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Only register custom connector in non-testing environments
        // Works with: Neon (with DB_OPTIONS), Supabase (no DB_OPTIONS), standard PostgreSQL
        if (! app()->environment('testing')) {
            $this->app->bind('db.connector.pgsql', function () {
                return new NeonPostgresConnector;
            });
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
