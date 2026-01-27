<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\PostgresConnector;

/**
 * Custom PostgreSQL Connector for Neon Database SNI Support
 *
 * This connector extends Laravel's PostgresConnector to add the
 * `options=endpoint=xxx` parameter to the DSN string, which is required
 * for Neon databases on hosts with older PostgreSQL client libraries (libpq)
 * that don't support SNI (Server Name Indication).
 *
 * @see https://neon.tech/sni
 */
class NeonPostgresConnector extends PostgresConnector
{
    /**
     * Create a DSN string from a configuration.
     *
     * @return string
     */
    protected function getDsn(array $config)
    {
        // Get the base DSN from parent
        $dsn = parent::getDsn($config);

        // If DB_OPTIONS is set, append it to the DSN
        $options = $config['neon_endpoint'] ?? env('DB_OPTIONS');

        if (! empty($options)) {
            $dsn .= ';options='.$options;
        }

        return $dsn;
    }
}
