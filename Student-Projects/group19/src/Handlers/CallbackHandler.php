<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Core\Request;
use App\Services\ExamEngine;
use App\Services\TelegramService;
use App\Utils\KeyboardBuilder;

/**
 * Handles all callback_query updates from Inline Keyboards.
 * Designed for high-speed response to user interactions.
 */
class CallbackHandler
{
    public function __construct(
        private ExamEngine $examEngine,
        private TelegramService $telegram,
        private KeyboardBuilder $keyboardBuilder
    ) {}

    /**
     * Entry point for processing callback data.
     */
    public function handle(Request $request): void
    {
        $callbackData = $request->getCallbackData();
        $chatId = $request->getChatId();
        $messageId = $request->getMessageId();

        if (!$callbackData) {
            return;
        }

        // Parse command and parameters (e.g., "answer:question_id:option_id")
        $parts = explode(':', $callbackData);
        $action = $parts[0];

        match ($action) {
            'start_test' => $this->initiateTest($chatId, (int)$parts[1]),
            'nav'        => $this->navigateTest($chatId, $messageId, (int)$parts[1], (int)$parts[2]),
            'answer'     => $this->processAnswer($chatId, $messageId, (int)$parts[1], (int)$parts[2], (int)$parts[3]),
            'confirm_ai' => $this->triggerAIAnalysis($chatId),
            default      => $this->telegram->answerCallback($request->getCallbackQueryId(), "دستور نامعتبر است.")
        };

        // Always acknowledge the callback to remove the "loading" state in Telegram
        $this->telegram->answerCallback($request->getCallbackQueryId());
    }

    private function initiateTest(int $chatId, int $testId): void
    {
        $questionData = $this->examEngine->start($chatId, $testId);
        $this->sendQuestion($chatId, $questionData);
    }

    private function processAnswer(int $chatId, int $msgId, int $testId, int $qId, int $optId): void
    {
        // Save or update answer in database
        $this->examEngine->saveAnswer($chatId, $testId, $qId, $optId);

        // Move to next question automatically for better UX
        $nextQuestion = $this->examEngine->getNext($chatId, $testId, $qId);
        
        if ($nextQuestion) {
            $this->updateQuestionUI($chatId, $msgId, $nextQuestion);
        } else {
            $this->showCompletionMenu($chatId, $msgId, $testId);
        }
    }

    private function navigateTest(int $chatId, int $msgId, int $testId, int $targetOrder): void
    {
        $questionData = $this->examEngine->getQuestionByOrder($testId, $targetOrder);
        $this->updateQuestionUI($chatId, $msgId, $questionData);
    }

    private function updateQuestionUI(int $chatId, int $msgId, array $data): void
    {
        $text = "سوال " . $data['order'] . ":\n" . $data['text'];
        $keyboard = $this->keyboardBuilder->buildExamKeyboard($data);
        
        $this->telegram->editMessageText($chatId, $msgId, $text, $keyboard);
    }

    private function triggerAIAnalysis(int $chatId): void
    {
        $this->telegram->sendMessage($chatId, "⏳ در حال تحلیل داده‌های روان‌شناختی شما توسط هوش مصنوعی... این فرآیند ممکن است ۳۰ ثانیه زمان ببرد.");
        // Forward to AIService logic
    }
}