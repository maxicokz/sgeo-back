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
     * OPTIMIZED: Reduced number of API calls for faster collection
     */
    private function collectTopicData(int $runId, array $topic, array $llmModels): void
    {
        $topicName = $topic['name'];
        $topicId = $topic['id'];

        log_message("Collecting data for topic: $topicName", 'info');

        // Generate prompts for this topic (SIMPLIFIED: only 1 prompt now)
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

                        // SIMPLIFIED: Use basic scoring instead of additional API calls
                        $sentiment = $this->calculateBasicSentiment($result['response']);
                        $completeness = $this->calculateBasicCompleteness($result['response']);
                        $correctness = $this->calculateBasicCorrectness($result['response'], count($sources));

                        // Create response record
                        $responseData = [
                            'monitoring_run_id' => $runId,
                            'topic_id' => $topicId,
                            'llm_system_id' => $llmSystemId,
                            'prompt_id' => null,
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
     * OPTIMIZED: Only 1 prompt instead of 5 to reduce API calls by 80%
     */
    private function generatePrompts(array $topic): array
    {
        $topicName = $topic['name'];

        return [
            "Расскажите подробно о теме: $topicName в контексте Казахстана. Предоставьте актуальную и точную информацию с указанием источников.",
        ];
    }

    /**
     * Calculate basic sentiment score without additional API calls
     * Analyzes text for positive/negative keywords
     */
    private function calculateBasicSentiment(string $text): float
    {
        $text = mb_strtolower($text);

        // Positive keywords
        $positiveWords = ['успех', 'развитие', 'прогресс', 'рост', 'достижение', 'улучшение', 'инновац', 'положительн'];
        $positiveCount = 0;
        foreach ($positiveWords as $word) {
            $positiveCount += substr_count($text, $word);
        }

        // Negative keywords
        $negativeWords = ['проблем', 'кризис', 'упадок', 'снижение', 'ухудшение', 'отрицательн', 'негативн'];
        $negativeCount = 0;
        foreach ($negativeWords as $word) {
            $negativeCount += substr_count($text, $word);
        }

        // Calculate score (0-5)
        $total = $positiveCount + $negativeCount;
        if ($total == 0) return 3.0; // Neutral

        $score = 3.0 + (($positiveCount - $negativeCount) / max($total, 1)) * 2;
        return round(max(0, min(5, $score)), 1);
    }

    /**
     * Calculate basic completeness score based on text length and structure
     */
    private function calculateBasicCompleteness(string $text): float
    {
        $length = mb_strlen($text);

        // Score based on length
        if ($length < 100) return 1.0;
        if ($length < 300) return 2.5;
        if ($length < 600) return 3.5;
        if ($length < 1000) return 4.0;

        // Bonus for structured content (paragraphs, lists)
        $hasStructure = (substr_count($text, "\n\n") > 1) || (substr_count($text, "- ") > 2);

        return $hasStructure ? 5.0 : 4.5;
    }

    /**
     * Calculate basic correctness score based on sources and text quality
     */
    private function calculateBasicCorrectness(string $text, int $sourceCount): float
    {
        // Base score from number of sources
        $score = min(3.0 + ($sourceCount * 0.5), 5.0);

        // Penalty for very short responses
        if (mb_strlen($text) < 100) {
            $score -= 1.0;
        }

        return round(max(1, min(5, $score)), 1);
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
