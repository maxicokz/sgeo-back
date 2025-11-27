<?php

namespace App\Services;

class OpenRouterService
{
    private string $apiKey;
    private string $apiUrl;
    private array $defaultHeaders;

    public function __construct()
    {
        $this->apiKey = config('app.openrouter.api_key');
        $this->apiUrl = config('app.openrouter.api_url');

        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenRouter API key is not configured');
        }

        $this->defaultHeaders = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'HTTP-Referer: ' . config('app.url'),
            'X-Title: SGEO Analytics Dashboard',
        ];
    }

    /**
     * Send a chat completion request
     */
    public function chat(string $model, array $messages, array $options = []): array
    {
        $endpoint = $this->apiUrl . '/chat/completions';

        $payload = [
            'model' => $model,
            'messages' => $messages,
        ];

        // Merge optional parameters
        // OpenAI models require 'max_completion_tokens' instead of 'max_tokens'
        $isOpenAIModel = str_starts_with($model, 'openai/');
        $maxTokensKey = $isOpenAIModel ? 'max_completion_tokens' : 'max_tokens';

        $payload = array_merge($payload, array_filter([
            'temperature' => $options['temperature'] ?? 0.7,
            $maxTokensKey => $options['max_tokens'] ?? null,
            'top_p' => $options['top_p'] ?? null,
            'frequency_penalty' => $options['frequency_penalty'] ?? null,
            'presence_penalty' => $options['presence_penalty'] ?? null,
        ]));

        return $this->request('POST', $endpoint, $payload);
    }

    /**
     * Query a specific model with a prompt
     */
    public function query(string $model, string $prompt, array $options = []): string
    {
        $messages = [
            ['role' => 'user', 'content' => $prompt]
        ];

        $response = $this->chat($model, $messages, $options);

        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }

        throw new \RuntimeException('Invalid response from OpenRouter API');
    }

    /**
     * Query multiple models with the same prompt
     */
    public function queryMultipleModels(array $models, string $prompt, array $options = []): array
    {
        $results = [];

        foreach ($models as $modelKey => $modelId) {
            try {
                $startTime = microtime(true);
                $response = $this->query($modelId, $prompt, $options);
                $processingTime = round((microtime(true) - $startTime) * 1000); // ms

                $results[$modelKey] = [
                    'success' => true,
                    'model' => $modelId,
                    'response' => $response,
                    'processing_time' => $processingTime,
                    'error' => null,
                ];
            } catch (\Exception $e) {
                $results[$modelKey] = [
                    'success' => false,
                    'model' => $modelId,
                    'response' => null,
                    'processing_time' => 0,
                    'error' => $e->getMessage(),
                ];

                log_message("OpenRouter query failed for model $modelId: " . $e->getMessage(), 'error');
            }
        }

        return $results;
    }

    /**
     * Get available models
     */
    public function getModels(): array
    {
        $endpoint = $this->apiUrl . '/models';
        return $this->request('GET', $endpoint);
    }

    /**
     * Analyze sentiment of text (using GPT)
     */
    public function analyzeSentiment(string $text, string $model = null): float
    {
        $model = $model ?? config('app.models.gpt35');

        $prompt = "Analyze the sentiment of the following text about Kazakhstan. Rate from 0 (very negative) to 5 (very positive). Only return the numeric score.\n\nText: " . $text;

        try {
            $response = $this->query($model, $prompt, ['temperature' => 0.3, 'max_tokens' => 10]);
            $score = (float) trim($response);
            return max(0, min(5, $score)); // Clamp between 0 and 5
        } catch (\Exception $e) {
            log_message("Sentiment analysis failed: " . $e->getMessage(), 'error');
            return 0;
        }
    }

    /**
     * Analyze completeness of text
     */
    public function analyzeCompleteness(string $text, string $topic): float
    {
        $model = config('app.models.gpt35');

        $prompt = "Analyze how complete and comprehensive the following text is about '$topic'. Rate from 0 (incomplete, missing key information) to 5 (comprehensive and complete). Only return the numeric score.\n\nText: " . $text;

        try {
            $response = $this->query($model, $prompt, ['temperature' => 0.3, 'max_tokens' => 10]);
            $score = (float) trim($response);
            return max(0, min(5, $score));
        } catch (\Exception $e) {
            log_message("Completeness analysis failed: " . $e->getMessage(), 'error');
            return 0;
        }
    }

    /**
     * Analyze correctness of text
     */
    public function analyzeCorrectness(string $text, string $topic): float
    {
        $model = config('app.models.gpt4');

        $prompt = "Analyze the factual correctness of the following text about '$topic'. Rate from 0 (contains major errors) to 5 (factually accurate). Only return the numeric score.\n\nText: " . $text;

        try {
            $response = $this->query($model, $prompt, ['temperature' => 0.3, 'max_tokens' => 10]);
            $score = (float) trim($response);
            return max(0, min(5, $score));
        } catch (\Exception $e) {
            log_message("Correctness analysis failed: " . $e->getMessage(), 'error');
            return 0;
        }
    }

    /**
     * Extract sources from response text
     */
    public function extractSources(string $text): array
    {
        $sources = [];

        // Extract URLs using regex
        preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $text, $matches);

        foreach ($matches[0] as $url) {
            $domain = parse_url($url, PHP_URL_HOST);
            if ($domain) {
                $sources[] = [
                    'url' => $url,
                    'domain' => $domain,
                ];
            }
        }

        // Remove duplicates by domain
        $uniqueSources = [];
        $seenDomains = [];

        foreach ($sources as $source) {
            if (!in_array($source['domain'], $seenDomains)) {
                $uniqueSources[] = $source;
                $seenDomains[] = $source['domain'];
            }
        }

        return $uniqueSources;
    }

    /**
     * Make HTTP request
     */
    private function request(string $method, string $url, ?array $data = null): array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->defaultHeaders);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new \RuntimeException("cURL error: $error");
        }

        if ($httpCode >= 400) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? "HTTP $httpCode error";
            throw new \RuntimeException("OpenRouter API error: $errorMessage");
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON response from OpenRouter API');
        }

        return $decoded;
    }
}
