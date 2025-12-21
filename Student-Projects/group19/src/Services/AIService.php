<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use JsonException;

/**
 * Advanced AI Analysis Service.
 * Interfaces with LLMs (OpenAI/Claude) to generate psychometric insights.
 */
class AIService
{
    private string $apiKey;
    private string $model;
    private float $temperature;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/telegram.php';
        $this->apiKey = $config['ai_analysis']['api_key'];
        $this->model = $config['ai_analysis']['model'];
        $this->temperature = $config['ai_analysis']['temperature'];
    }

    /**
     * Perform deep analysis on aggregated user data.
     * * @param array $userData Aggregated scores and behavioral patterns.
     * @return array The structured analysis report.
     * @throws RuntimeException
     */
    public function analyze(array $userData): array
    {
        $prompt = $this->buildPrompt($userData);

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert psychometric analyst. Always return responses in valid JSON format.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $this->temperature,
            'response_format' => ['type' => 'json_object'] // Force JSON response (OpenAI support)
        ];

        return $this->executeRequest($payload);
    }

    /**
     * Build a highly detailed prompt for the AI.
     */
    private function buildPrompt(array $data): string
    {
        $jsonContext = json_encode($data, JSON_UNESCAPED_UNICODE);
        
        return <<<PROMPT
Analyze the following psychometric data: {$jsonContext}

Please provide a comprehensive report in PERSIAN (Farsi) with these keys:
1. "typology": Deep psychological status and traits.
2. "career_roadmap": List of compatible jobs and reasons.
3. "lifestyle": Recommendations for music genres, movies, and books.
4. "locations": 3 best countries/cities for this person.
5. "social_relations": Communication strengths and weaknesses.

Return ONLY a JSON object.
PROMPT;
    }

    /**
     * Execute the API call using optimized cURL settings.
     */
    private function executeRequest(array $payload): array
    {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        
        // AI requests take time; we set a higher timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException("AI API Connection Error: " . $error);
        }

        if ($httpCode !== 200) {
            throw new RuntimeException("AI API returned status code: " . $httpCode . " Response: " . $response);
        }

        try {
            $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            $content = $decoded['choices'][0]['message']['content'] ?? '{}';
            return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException("Failed to parse AI JSON response: " . $e->getMessage());
        }
    }
}