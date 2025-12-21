<?php

declare(strict_types=1);

/**
 * Database Configuration
 *
 * This file handles the connection settings for MariaDB.
 * It utilizes PDO (PHP Data Objects) for a secure and abstraction-layer
 * independent interface.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work.
    */
    'default' => $_ENV['DB_CONNECTION'] ?? 'mysql',

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    | structure allows for multiple connections (e.g., Read/Write replicas)
    | in the future if scaling is required.
    */
    'connections' => [

        'mysql' => [
            'driver'    => 'mysql',
            'host'      => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port'      => $_ENV['DB_PORT'] ?? '3306',
            'database'  => $_ENV['DB_DATABASE'] ?? 'psychometry_platform',
            'username'  => $_ENV['DB_USERNAME'] ?? 'root',
            'password'  => $_ENV['DB_PASSWORD'] ?? '',
            'unix_socket' => $_ENV['DB_SOCKET'] ?? '',
            
            // Critical for Telegram Bots (Emojis & Persian Text)
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            
            /*
            |--------------------------------------------------------------------------
            | PDO Performance & Security Options
            |--------------------------------------------------------------------------
            */
            'options'   => [
                // Throw exceptions on errors (Fail Fast)
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                
                // Return arrays indexed by column name (Memory efficient)
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                
                // Security: Disable emulation to use Native Prepared Statements
                // This prevents advanced SQL Injection attacks.
                PDO::ATTR_EMULATE_PREPARES => false,
                
                // Ensure connection is clean and UTF8mb4 compliant
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                
                // Disable persistent connections for bot stability (avoids "MySQL server has gone away")
                PDO::ATTR_PERSISTENT => false,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    | This table keeps track of all the migrations that have already run for
    | your application.
    */
    'migrations' => 'migrations',
];