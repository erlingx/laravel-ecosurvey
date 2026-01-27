<?php

namespace App\Providers;

use App\Database\Connectors\NeonPostgresConnector;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider for Neon PostgreSQL Database Support
 *
 * This provider registers a custom PostgreSQL connector that adds
 * SNI (Server Name Indication) support for Neon databases on hosts
 * with older PostgreSQL client libraries.
 */
class NeonDatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the custom Neon PostgreSQL connector
        $this->app->bind('db.connector.pgsql', function () {
            return new NeonPostgresConnector;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
