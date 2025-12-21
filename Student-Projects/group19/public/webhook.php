<?php

declare(strict_types=1);

/**
 * Emotipal Webhook Entry Point
 * * This is the only publicly accessible file. It receives updates from Telegram,
 * verifies the source, and boots the application kernel.
 */

use App\Core\Container;
use App\Core\Request;
use App\Middleware\AuthMiddleware;
use App\Handlers\UpdateHandler;

// 1. Load Autoloader & Environment
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Security Check: Telegram Secret Token
// This prevents unauthorized requests from reaching your logic.
$config = require __DIR__ . '/../config/telegram.php';
$headers = getallheaders();
$secretToken = $headers['X-Telegram-Bot-Api-Secret-Token'] ?? '';

if ($secretToken !== $config['webhook']['secret_token']) {
    http_response_code(403);
    exit('Unauthorized Access');
}

try {
    // 3. Capture Input
    $content = file_get_contents('php://input');
    if (!$content) {
        exit;
    }

    $update = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

    // 4. Initialize Dependency Injection Container
    $container = new Container();
    
    // 5. Build Request Object
    $request = new Request($update);
    $telegramId = $request->getUserId();

    // 6. Global Middleware Layer (Auth & Block Check)
    $auth = $container->get(AuthMiddleware::class);
    if (!$auth->handle($telegramId)) {
        exit; // User is blocked or access denied
    }

    // 7. Route to Main Handler
    // UpdateHandler decides whether to call CommandHandler or CallbackHandler
    $handler = $container->get(UpdateHandler::class);
    $handler->process($request);

    // 8. Finalize
    http_response_code(200);
    echo json_encode(['status' => 'success']);

} catch (Throwable $e) {
    // Log the error for the Senior Engineer
    error_log("Webhook Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    
    // Always return 200 to Telegram to prevent retry loops on internal errors
    http_response_code(200);
}