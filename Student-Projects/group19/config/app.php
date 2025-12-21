<?php

declare(strict_types=1);

/**
 * Emotipal Application Configuration
 *
 * This file contains the core configuration for the application,
 * including environment settings, timezones, and directory paths.
 * It is designed to be immutable and read from environment variables.
 */

// Define the root path of the project (one level up from config)
$basePath = dirname(__DIR__);

return [
    /*
    |--------------------------------------------------------------------------
    | Application Meta Data
    |--------------------------------------------------------------------------
    | Basic information about the application instance.
    */
    'name'      => $_ENV['APP_NAME'] ?? 'Emotipal Bot',
    'version'   => '1.0.0',
    'env'       => $_ENV['APP_ENV'] ?? 'production', // Options: local, staging, production
    'debug'     => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
    'url'       => $_ENV['APP_URL'] ?? 'http://localhost',

    /*
    |--------------------------------------------------------------------------
    | Timezone & Locale
    |--------------------------------------------------------------------------
    | Crucial for correct timestamping in the database (MariaDB) and
    | user interactions. Defaults to Tehran for this project context.
    */
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Tehran',
    'locale'   => $_ENV['APP_LOCALE'] ?? 'fa',

    /*
    |--------------------------------------------------------------------------
    | Application Paths
    |--------------------------------------------------------------------------
    | deeply nested paths are hard to maintain. We define them here centrally.
    */
    'paths' => [
        'root'      => $basePath,
        'bin'       => $basePath . '/bin',
        'config'    => $basePath . '/config',
        'storage'   => $basePath . '/storage',
        'logs'      => $basePath . '/storage/logs',
        'cache'     => $basePath . '/storage/cache',
        'src'       => $basePath . '/src',
    ],

    /*
    |--------------------------------------------------------------------------
    | System Performance Limits
    |--------------------------------------------------------------------------
    | Defined to handle heavy AI analysis and Image processing tasks.
    */
    'system' => [
        'max_execution_time' => 60, // Seconds (Higher for AI processing)
        'memory_limit'       => '256M',
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    | Used for securing sensitive user data if needed (AES-256-CBC).
    */
    'cipher' => 'AES-256-CBC',
    'key'    => $_ENV['APP_KEY'] ?? '',
];