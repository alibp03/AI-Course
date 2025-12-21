<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TestRepository;
use App\Repositories\AnswerRepository;
use App\Repositories\UserRepository;
use RuntimeException;

/**
 * The core logic for conducting tests and calculating results.
 * Optimized for stateful navigation and data aggregation.
 */
class ExamEngine
{
    public function __construct(
        private TestRepository $testRepository,
        private AnswerRepository $answerRepository,
        private UserRepository $userRepository
    ) {}

    /**
     * Start a new test for the user or resume if already in progress.
     */
    public function start(int $telegramId, int $testId): array
    {
        $user = $this->userRepository->findByTelegramId($telegramId);
        if (!$user) {
            throw new RuntimeException("User must be registered before starting a test.");
        }

        // Get the first question of the test
        return $this->getQuestionByOrder($testId, 1);
    }

    /**
     * Get a specific question by its order index.
     */
    public function getQuestionByOrder(int $testId, int $order): array
    {
        $question = $this->testRepository->getQuestion($testId, $order);
        if (!$question) {
            throw new RuntimeException("Question not found for order: {$order}");
        }

        // Fetch options for this question
        $options = $this->testRepository->getQuestionOptions($question['id']);
        
        return [
            'id'       => $question['id'],
            'test_id'  => $testId,
            'text'     => $question['question_text'],
            'order'    => $order,
            'options'  => $options,
            'total'    => $this->testRepository->getTotalQuestionsCount($testId)
        ];
    }

    /**
     * Logic for moving to the next question.
     */
    public function getNext(int $telegramId, int $testId, int $currentQuestionId): ?array
    {
        $currentOrder = $this->testRepository->getQuestionOrder($currentQuestionId);
        $nextOrder = $currentOrder + 1;

        if ($nextOrder > $this->testRepository->getTotalQuestionsCount($testId)) {
            return null; // Test completed
        }

        return $this->getQuestionByOrder($testId, $nextOrder);
    }

    /**
     * Save or Update the user's answer (UPSERT logic).
     */
    public function saveAnswer(int $telegramId, int $testId, int $questionId, int $optionId): void
    {
        $user = $this->userRepository->findByTelegramId($telegramId);
        
        // Repository handles the "ON DUPLICATE KEY UPDATE" logic for MariaDB
        $this->answerRepository->upsert(
            (int)$user['id'],
            $testId,
            $questionId,
            $optionId
        );
    }

    /**
     * Final Aggregation: Prepares data for the AI Service.
     */
    public function aggregateResultsForAI(int $telegramId): array
    {
        $user = $this->userRepository->findByTelegramId($telegramId);
        $answers = $this->answerRepository->getUserFullHistory((int)$user['id']);

        $payload = [
            'user_context' => [
                'id' => $telegramId,
                'completed_at' => date('Y-m-d H:i:s')
            ],
            'tests_data' => []
        ];

        foreach ($answers as $answer) {
            $testSlug = $answer['test_slug'];
            if (!isset($payload['tests_data'][$testSlug])) {
                $payload['tests_data'][$testSlug] = [];
            }

            // Aggregating weights for AI analysis
            $payload['tests_data'][$testSlug][] = [
                'question' => $answer['question_text'],
                'selected_option' => $answer['option_text'],
                'weights' => json_decode($answer['score_weight'], true)
            ];
        }

        return $payload;
    }
}