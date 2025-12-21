<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Core\Request;
use App\Services\TelegramService;
use App\Repositories\UserRepository;
use App\Repositories\TestRepository;
use App\Utils\KeyboardBuilder;

/**
 * Handles all slash commands and direct text messages.
 * This class acts as the primary router for user-initiated interactions.
 */
class CommandHandler
{
    public function __construct(
        private TelegramService $telegram,
        private UserRepository $userRepository,
        private TestRepository $testRepository,
        private KeyboardBuilder $keyboardBuilder
    ) {}

    /**
     * Entry point for processing text commands.
     */
    public function handle(Request $request): void
    {
        $text = $request->getText();
        $chatId = $request->getChatId();

        if ($text === null) return;

        match ($text) {
            '/start'   => $this->handleStart($chatId, $request->getUserData()),
            '/help'    => $this->handleHelp($chatId),
            '/admin'   => $this->handleAdmin($chatId),
            'آزمون‌های من' => $this->showAvailableTests($chatId),
            default    => $this->handleUnknown($chatId)
        };
    }

    /**
     * Handles the initial /start command.
     * Registers the user if they don't exist.
     */
    private function handleStart(int $chatId, array $userData): void
    {
        // Persistence Layer: Upsert user record
        $this->userRepository->syncUser($chatId, $userData['username'] ?? null);

        $welcomeMessage = "👋 به پلتفرم جامع روان‌سنجی خوش آمدید.\n\n" .
                          "🧠 در این بات می‌توانید تست‌های معتبر شخصیتی (MBTI, Big Five, ...) را انجام دهید " .
                          "و با استفاده از هوش مصنوعی، تحلیل عمیقی از ابعاد روانی خود دریافت کنید.";

        $keyboard = $this->keyboardBuilder->buildMainMenu();
        
        $this->telegram->sendMessage($chatId, $welcomeMessage, $keyboard);
    }

    /**
     * Shows the list of available psychometric tests.
     * Incorporates completion status (Checkmarks).
     */
    private function showAvailableTests(int $chatId): void
    {
        $tests = $this->testRepository->getAllActiveTests();
        $completedTestIds = $this->testRepository->getCompletedTestIdsForUser($chatId);

        $text = "📋 لیست آزمون‌های فعال:\n" .
                "تست‌هایی که با ✅ مشخص شده‌اند را قبلاً تکمیل کرده‌اید.";

        $keyboard = $this->keyboardBuilder->buildTestsList($tests, $completedTestIds);
        
        $this->telegram->sendMessage($chatId, $text, $keyboard);
    }

    /**
     * Admin Panel Access Layer.
     */
    private function handleAdmin(int $chatId): void
    {
        $user = $this->userRepository->findByTelegramId($chatId);
        
        if (!$user || !$user['is_admin']) {
            $this->telegram->sendMessage($chatId, "🚫 شما دسترسی به این بخش را ندارید.");
            return;
        }

        $text = "👨‍💻 به پنل مدیریت خوش آمدید.\nلطفاً یک گزینه را انتخاب کنید:";
        $keyboard = $this->keyboardBuilder->buildAdminMenu();
        
        $this->telegram->sendMessage($chatId, $text, $keyboard);
    }

    private function handleHelp(int $chatId): void
    {
        $helpText = "راهنمای استفاده:\n" .
                    "1. دکمه شروع آزمون را بزنید.\n" .
                    "2. سوالات را با دقت پاسخ دهید.\n" .
                    "3. پس از اتمام تمام تست‌ها، دکمه 'تحلیل هوش مصنوعی' فعال می‌شود.";
        
        $this->telegram->sendMessage($chatId, $helpText);
    }

    private function handleUnknown(int $chatId): void
    {
        $this->telegram->sendMessage($chatId, "❓ دستور نامفهوم است. لطفاً از منوی زیر استفاده کنید.");
    }
}