<?php

declare(strict_types=1);

/**
 * Database connection configuration.
 *
 * This is the ONLY configuration allowed to live in a file (per the Master
 * Specification). Every other setting must be stored in the database and read
 * through the ConfigService. Never introduce .env or environment variables.
 *
 * For local overrides, create config/database.local.php returning an array with
 * the same keys; it is gitignored and merged on top of these defaults.
 */

$config = [
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'port'      => 3306,
    'database'  => 'lrvweb',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',

    // PDO options applied to every connection.
    'options'   => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
    ],
];

$localFile = __DIR__ . '/database.local.php';
if (is_file($localFile)) {
    /** @var array<string, mixed> $local */
    $local = require $localFile;
    if (is_array($local)) {
        $config = array_replace($config, $local);
    }
}

return $config;
