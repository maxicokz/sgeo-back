<?php

namespace App\Services;

use App\Models\Topic;
use App\Models\LLMResponse;
use App\Models\MonitoringRun;
use App\Database\Connection;

class DataCollectionService
{
    private OpenRouterService $openRouter;
    private AnalyticsService $analytics;

    public function __construct()
    {
        $this->openRouter = new OpenRouterService();
        $this->analytics = new AnalyticsService();
    }

    /**
     * Run full data collection cycle
     */
    public function runCollection(array $topicIds = [], array $llmModels = []): int
    {
        // Get topics to collect
        $topics = empty($topicIds) ? Topic::all() : array_map(fn($id) => Topic::find($id), $topicIds);
        $topics = array_filter($topics);

        if (empty($topics)) {
            throw new \RuntimeException('No topics found for collection');
        }

        // Get LLM models to query
        if (empty($llmModels)) {
            $llmModels = [
                'gpt4' => config('app.models.gpt4'),
                'gpt35' => config('app.models.gpt35'),
                'claude' => config('app.models.claude'),
                'gemini' => config('app.models.gemini'),
                'perplexity' => config('app.models.perplexity'),
            ];
        }

        // Create monitoring run
        $totalQueries = count($topics) * count($llmModels);
        $runId = MonitoringRun::create($totalQueries, 'Automated data collection');

        log_message("Starting data collection run #$runId with $totalQueries queries", 'info');

        // Collect data for each topic
        foreach ($topics as $topic) {
            $this->collectTopicData($runId, $topic, $llmModels);
        }

        // Mark run as completed
        MonitoringRun::markCompleted($runId);

        log_message("Completed data collection run #$runId", 'info');

        return $runId;
    }

    /**
     * Collect data for a specific topic
     */
    private function collectTopicData(int $runId, array $topic, array $llmModels): void
    {
        $topicName = $topic['name'];
        $topicId = $topic['id'];

        log_message("Collecting data for topic: $topicName", 'info');

        // Generate prompts for this topic
        $prompts = $this->generatePrompts($topic);

        foreach ($prompts as $promptIndex => $prompt) {
            // Query all LLM models with this prompt
            $results = $this->openRouter->queryMultipleModels($llmModels, $prompt);

            foreach ($results as $modelKey => $result) {
                try {
                    if ($result['success']) {
                        // Get LLM system ID
                        $llmSystemId = $this->getLLMSystemId($modelKey);

                        // Extract sources from response
                        $sources = $this->openRouter->extractSources($result['response']);

                        // Analyze response
                        $sentiment = $this->openRouter->analyzeSentiment($result['response']);
                        $completeness = $this->openRouter->analyzeCompleteness($result['response'], $topicName);
                        $correctness = $this->openRouter->analyzeCorrectness($result['response'], $topicName);

                        // Create response record
                        $responseData = [
                            'monitoring_run_id' => $runId,
                            'topic_id' => $topicId,
                            'llm_system_id' => $llmSystemId,
                            'prompt_id' => null, // TODO: save prompt to get ID
                            'response_text' => $result['response'],
                            'cited_sources' => json_encode($sources),
                            'sentiment_score' => $sentiment,
                            'completeness_score' => $completeness,
                            'correctness_score' => $correctness,
                            'processing_time' => $result['processing_time'],
                            'response_metadata' => json_encode([
                                'model' => $result['model'],
                                'prompt_index' => $promptIndex,
                            ]),
                        ];

                        LLMResponse::create($responseData);

                        // Process sources
                        $this->processSources($runId, $topicId, $sources);

                        MonitoringRun::incrementCompleted($runId);

                        log_message("Successfully collected response for topic #$topicId, model: $modelKey", 'info');
                    } else {
                        MonitoringRun::incrementFailed($runId);
                        log_message("Failed to collect response for topic #$topicId, model: $modelKey - " . $result['error'], 'error');
                    }
                } catch (\Exception $e) {
                    MonitoringRun::incrementFailed($runId);
                    log_message("Error processing response for topic #$topicId, model: $modelKey - " . $e->getMessage(), 'error');
                }

                // Rate limiting - sleep between requests
                usleep(500000); // 0.5 seconds
            }
        }
    }

    /**
     * Generate prompts for a topic
     */
    private function generatePrompts(array $topic): array
    {
        $topicName = $topic['name'];
        $topicNameEn = $topic['name_en'] ?? $topicName;

        return [
            "Расскажите подробно о теме: $topicName. Предоставьте актуальную и точную информацию с указанием источников.",
            "Что вы знаете о: $topicName? Приведите факты и ссылки на источники.",
            "Опишите ключевые аспекты темы: $topicName. Укажите надежные источники информации.",
            "Tell me in detail about: $topicNameEn. Provide accurate and up-to-date information with sources.",
            "What are the key facts about: $topicNameEn? Please include reliable sources.",
        ];
    }

    /**
     * Process and save sources
     */
    private function processSources(int $runId, int $topicId, array $sources): void
    {
        foreach ($sources as $sourceData) {
            try {
                $domain = $sourceData['domain'];

                // Find or create source
                $source = \App\Models\Source::findByDomain($domain);

                if (!$source) {
                    // Create new source
                    $sourceId = \App\Models\Source::create([
                        'domain' => $domain,
                        'url' => $sourceData['url'],
                        'title' => $this->extractTitle($sourceData['url']),
                        'source_type' => $this->detectSourceType($domain),
                        'is_official' => $this->isOfficialSource($domain),
                        'citation_count' => 1,
                    ]);
                } else {
                    $sourceId = $source['id'];
                    \App\Models\Source::incrementCitation($sourceId);
                }

                // Save source analytics
                Connection::insert('source_analytics', [
                    'source_id' => $sourceId,
                    'monitoring_run_id' => $runId,
                    'topic_id' => $topicId,
                    'citation_count' => 1,
                ]);
            } catch (\Exception $e) {
                log_message("Error processing source {$sourceData['domain']}: " . $e->getMessage(), 'error');
            }
        }
    }

    /**
     * Get LLM system ID by key
     */
    private function getLLMSystemId(string $key): int
    {
        $mapping = [
            'gpt4' => 1,
            'gpt35' => 2,
            'claude' => 3,
            'gemini' => 6,
            'perplexity' => 5,
        ];

        return $mapping[$key] ?? 1;
    }

    /**
     * Extract title from URL (simplified)
     */
    private function extractTitle(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $title = basename($path);
        return $title ?: parse_url($url, PHP_URL_HOST);
    }

    /**
     * Detect source type
     */
    private function detectSourceType(string $domain): string
    {
        if (strpos($domain, '.gov') !== false || strpos($domain, '.kz') !== false) {
            return 'government';
        } elseif (strpos($domain, 'wiki') !== false) {
            return 'wiki';
        } elseif (strpos($domain, 'news') !== false) {
            return 'news';
        } else {
            return 'general';
        }
    }

    /**
     * Check if source is official
     */
    private function isOfficialSource(string $domain): bool
    {
        $officialDomains = [
            'gov.kz',
            'akorda.kz',
            'primeminister.kz',
            'kazakhstan.kz',
            'stat.gov.kz',
        ];

        foreach ($officialDomains as $official) {
            if (strpos($domain, $official) !== false) {
                return true;
            }
        }

        return false;
    }
}
