<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Repositories\UserRepository;
use App\Services\TelegramService;

/**
 * Security Middleware: Access Control & User Validation.
 * Ensures only authorized and non-blocked users interact with the system.
 */
class AuthMiddleware
{
    public function __construct(
        private UserRepository $userRepository,
        private TelegramService $telegram
    ) {}

    /**
     * Handle the incoming request authorization.
     * * @param int $telegramId
     * @return bool True if authorized, False to terminate the request.
     */
    public function handle(int $telegramId): bool
    {
        // 1. Fetch user from repository (cached or high-performance lookup)
        $user = $this->userRepository->findByTelegramId($telegramId);

        // 2. Scenario: New User (First interaction)
        if (!$user) {
            // Allow the request to pass to CommandHandler (for /start registration)
            return true;
        }

        // 3. Scenario: Blocked User
        // Strict typing ensures $user['is_blocked'] is treated correctly
        if ((bool)$user['is_blocked']) {
            $this->telegram->sendMessage(
                $telegramId, 
                "⛔️ متاسفانه دسترسی شما به این ربات توسط مدیریت محدود شده است."
            );
            return false; // Terminate execution
        }

        // 4. Scenario: Active User
        return true;
    }

    /**
     * Secondary Middleware: Admin Rights Verification.
     * * @param int $telegramId
     * @return bool
     */
    public function isAdmin(int $telegramId): bool
    {
        $user = $this->userRepository->findByTelegramId($telegramId);
        
        if ($user && (bool)$user['is_admin']) {
            return true;
        }

        $this->telegram->sendMessage($telegramId, "🚫 دسترسی غیرمجاز.");
        return false;
    }
}