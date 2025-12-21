<?php

declare(strict_types=1);

/**
 * Telegram Bot & AI Integration Configuration
 * * This file contains settings for the Telegram API, webhook security,
 * and the specific parameters for the AI analysis engine.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Bot Credentials
    |--------------------------------------------------------------------------
    | These values are pulled from your .env file for security.
    */
    'bot_token' => $_ENV['TELEGRAM_BOT_TOKEN'] ?? '',
    'bot_username' => $_ENV['TELEGRAM_BOT_USERNAME'] ?? 'EmotipalBot',

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    | secret_token is used to verify that the request is coming from Telegram.
    | max_connections is optimized for shared/VPS hosting to prevent CPU spikes.
    */
    'webhook' => [
        'url' => ($_ENV['APP_URL'] ?? '') . '/webhook.php',
        'secret_token' => $_ENV['TELEGRAM_SECRET_TOKEN'] ?? '',
        'max_connections' => 40,
        'allowed_updates' => ['message', 'callback_query'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Administrative Settings
    |--------------------------------------------------------------------------
    | Numerical Telegram User IDs for Admin access.
    */
    'admins' => explode(',', $_ENV['TELEGRAM_ADMIN_IDS'] ?? ''),

    /*
    |--------------------------------------------------------------------------
    | AI Hyper-Analysis Parameters
    |--------------------------------------------------------------------------
    | Settings for the AI engine that processes user test results.
    */
    'ai_analysis' => [
        'api_key' => $_ENV['AI_API_KEY'] ?? '',
        'model'   => 'gpt-4o', // Most capable model for psychology
        'temperature' => 0.7, // Balance between creative and factual
        'max_tokens'  => 2000,
        'system_prompt' => "You are an expert clinical psychologist and career coach. " .
                          "Analyze the provided test scores and generate a detailed report " .
                          "in Persian (Farsi) including typology, career path, and lifestyle tips."
    ],

    /*
    |--------------------------------------------------------------------------
    | Security & Throttling
    |--------------------------------------------------------------------------
    | To prevent spam and flooding from malicious users.
    */
    'limits' => [
        'commands_per_minute' => 30,
        'max_message_length' => 4096, // Telegram standard
    ],
];