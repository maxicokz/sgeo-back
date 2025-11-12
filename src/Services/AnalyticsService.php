<?php

namespace App\Services;

use App\Models\Source;
use App\Models\Topic;
use App\Models\LLMResponse;
use App\Models\MonitoringRun;
use App\Database\Connection;

class AnalyticsService
{
    /**
     * Get dashboard overview
     */
    public function getDashboardOverview(?int $runId = null): array
    {
        $runId = $runId ?? MonitoringRun::getLatest()['id'] ?? null;

        if (!$runId) {
            return $this->getEmptyOverview();
        }

        return [
            'run' => MonitoringRun::getWithStats($runId),
            'overall_scores' => $this->getOverallScores($runId),
            'top_sources' => Source::getTopCited(10, $runId),
            'topic_performance' => $this->getTopicPerformanceSummary($runId),
            'llm_comparison' => $this->getLLMComparison($runId),
            'alerts' => $this->generateAlerts($runId),
            'recommendations' => $this->generateRecommendations($runId),
        ];
    }

    /**
     * Get overall E-E-A-T scores
     */
    public function getOverallEEATScore(): array
    {
        $result = Connection::queryOne('
            SELECT
                AVG(expertise_score) as avg_expertise,
                AVG(authoritativeness_score) as avg_authoritativeness,
                AVG(trustworthiness_score) as avg_trustworthiness,
                AVG(experience_score) as avg_experience,
                AVG(overall_eeat_score) as avg_overall,
                AVG(authorship_clarity) as avg_authorship_clarity,
                AVG(content_accuracy) as avg_content_accuracy,
                AVG(reputation_score) as avg_reputation,
                AVG(security_score) as avg_security,
                AVG(freshness_score) as avg_freshness
            FROM sources
            WHERE overall_eeat_score IS NOT NULL
        ');

        return $result ?? [];
    }

    /**
     * Get overall content scores
     */
    public function getOverallScores(?int $runId = null): array
    {
        return LLMResponse::getAverageScores($runId);
    }

    /**
     * Get topic performance summary
     */
    public function getTopicPerformanceSummary(?int $runId = null): array
    {
        $sql = '
            SELECT
                t.id,
                t.name,
                t.strategic_importance,
                COUNT(lr.id) as response_count,
                AVG(lr.sentiment_score) as avg_sentiment,
                AVG(lr.completeness_score) as avg_completeness,
                AVG(lr.correctness_score) as avg_correctness,
                (AVG(lr.sentiment_score) + AVG(lr.completeness_score) + AVG(lr.correctness_score)) / 3 as overall_score
            FROM topics t
            LEFT JOIN llm_responses lr ON t.id = lr.topic_id
        ';

        $params = [];

        if ($runId) {
            $sql .= ' AND lr.monitoring_run_id = ?';
            $params[] = $runId;
        }

        $sql .= ' GROUP BY t.id ORDER BY overall_score DESC';

        return Connection::query($sql, $params);
    }

    /**
     * Get LLM comparison
     */
    public function getLLMComparison(?int $runId = null): array
    {
        $sql = '
            SELECT
                ls.id,
                ls.name,
                ls.provider,
                COUNT(lr.id) as response_count,
                AVG(lr.sentiment_score) as avg_sentiment,
                AVG(lr.completeness_score) as avg_completeness,
                AVG(lr.correctness_score) as avg_correctness,
                AVG(lr.processing_time) as avg_processing_time
            FROM llm_systems ls
            LEFT JOIN llm_responses lr ON ls.id = lr.llm_system_id
        ';

        $params = [];

        if ($runId) {
            $sql .= ' AND lr.monitoring_run_id = ?';
            $params[] = $runId;
        }

        $sql .= ' GROUP BY ls.id ORDER BY ls.name';

        return Connection::query($sql, $params);
    }

    /**
     * Get source citation distribution
     */
    public function getSourceCitationDistribution(?int $runId = null, int $limit = 10): array
    {
        $sql = '
            SELECT
                s.domain,
                s.title,
                s.source_type,
                s.is_official,
                s.overall_eeat_score,
                COUNT(sa.id) as citation_count,
                COUNT(DISTINCT sa.topic_id) as topics_covered
            FROM sources s
            JOIN source_analytics sa ON s.id = sa.source_id
        ';

        $params = [];

        if ($runId) {
            $sql .= ' WHERE sa.monitoring_run_id = ?';
            $params[] = $runId;
        }

        $sql .= '
            GROUP BY s.id
            ORDER BY citation_count DESC
            LIMIT ?
        ';

        $params[] = $limit;

        return Connection::query($sql, $params);
    }

    /**
     * Calculate health index for source
     */
    public function calculateSourceHealthIndex(int $sourceId): float
    {
        $source = Source::find($sourceId);
        if (!$source) {
            return 0;
        }

        // Health index components (weighted)
        $components = [
            'overall_eeat_score' => 0.35, // 35%
            'content_accuracy' => 0.25,    // 25%
            'freshness_score' => 0.15,     // 15%
            'security_score' => 0.15,      // 15%
            'reputation_score' => 0.10,    // 10%
        ];

        $healthIndex = 0;

        foreach ($components as $field => $weight) {
            if (isset($source[$field]) && $source[$field] !== null) {
                // Normalize to 0-10 scale if needed
                $value = $source[$field];
                if ($field === 'overall_eeat_score') {
                    $value = $value / 10; // 0-100 to 0-10
                }
                $healthIndex += ($value * $weight * 10);
            }
        }

        return round($healthIndex, 2);
    }

    /**
     * Generate alerts based on data
     */
    public function generateAlerts(?int $runId = null): array
    {
        $alerts = [];

        // Check for low-scoring topics
        $lowTopics = Connection::query('
            SELECT t.name, AVG(lr.correctness_score) as avg_score
            FROM topics t
            JOIN llm_responses lr ON t.id = lr.topic_id
            ' . ($runId ? 'WHERE lr.monitoring_run_id = ?' : '') . '
            GROUP BY t.id
            HAVING AVG(lr.correctness_score) < 3
            ORDER BY avg_score ASC
        ', $runId ? [$runId] : []);

        foreach ($lowTopics as $topic) {
            $alerts[] = [
                'level' => 'critical',
                'type' => 'low_correctness',
                'message' => "🔴 ALERT: Low correctness score (" . round($topic['avg_score'], 2) . "/5) for topic '{$topic['name']}'",
            ];
        }

        // Check for sources with low E-E-A-T
        $lowSources = Connection::query('
            SELECT domain, overall_eeat_score
            FROM sources
            WHERE overall_eeat_score < 50 AND citation_count > 5
            ORDER BY citation_count DESC
            LIMIT 5
        ');

        foreach ($lowSources as $source) {
            $alerts[] = [
                'level' => 'warning',
                'type' => 'low_eeat',
                'message' => "🟡 WARNING: Frequently cited source '{$source['domain']}' has low E-E-A-T score (" . round($source['overall_eeat_score'], 2) . "/100)",
            ];
        }

        // Check citation rate of official sources
        $officialCitationRate = Connection::queryOne('
            SELECT
                COUNT(CASE WHEN s.is_official THEN 1 END) * 100.0 / COUNT(*) as official_rate
            FROM source_analytics sa
            JOIN sources s ON sa.source_id = s.id
            ' . ($runId ? 'WHERE sa.monitoring_run_id = ?' : '')
        , $runId ? [$runId] : []);

        if ($officialCitationRate && $officialCitationRate['official_rate'] < 30) {
            $alerts[] = [
                'level' => 'warning',
                'type' => 'low_official_sources',
                'message' => "🟡 WARNING: Official sources cited only " . round($officialCitationRate['official_rate'], 1) . "% of the time",
            ];
        }

        return $alerts;
    }

    /**
     * Generate recommendations
     */
    public function generateRecommendations(?int $runId = null): array
    {
        $recommendations = [];

        // Sources needing optimization
        $needOptimization = Source::getNeedingOptimization();
        if (count($needOptimization) > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'source_optimization',
                'message' => count($needOptimization) . " sources need E-E-A-T optimization",
                'action' => 'Review and optimize source metadata and content',
            ];
        }

        // Topics with low completeness
        $incompleteTopics = Connection::query('
            SELECT t.name, AVG(lr.completeness_score) as avg_score
            FROM topics t
            JOIN llm_responses lr ON t.id = lr.topic_id
            ' . ($runId ? 'WHERE lr.monitoring_run_id = ?' : '') . '
            GROUP BY t.id
            HAVING AVG(lr.completeness_score) < 3.5
            LIMIT 5
        ', $runId ? [$runId] : []);

        if (count($incompleteTopics) > 0) {
            $topicList = implode(', ', array_column($incompleteTopics, 'name'));
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'content_improvement',
                'message' => "Improve content completeness for topics: $topicList",
                'action' => 'Create comprehensive content covering all key aspects',
            ];
        }

        // Check for outdated content
        $lowFreshness = Connection::query('
            SELECT domain, freshness_score
            FROM sources
            WHERE freshness_score < 5 AND citation_count > 3
            ORDER BY citation_count DESC
            LIMIT 5
        ');

        if (count($lowFreshness) > 0) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'content_freshness',
                'message' => "Update outdated content on frequently cited sources",
                'action' => 'Review and refresh content with recent data and information',
            ];
        }

        return $recommendations;
    }

    /**
     * Get comparison data (before/after)
     */
    public function getComparisonData(int $beforeRunId, int $afterRunId): array
    {
        $before = [
            'scores' => $this->getOverallScores($beforeRunId),
            'topics' => $this->getTopicPerformanceSummary($beforeRunId),
        ];

        $after = [
            'scores' => $this->getOverallScores($afterRunId),
            'topics' => $this->getTopicPerformanceSummary($afterRunId),
        ];

        return [
            'before' => $before,
            'after' => $after,
            'improvements' => $this->calculateImprovements($before, $after),
        ];
    }

    /**
     * Calculate improvements between two runs
     */
    private function calculateImprovements(array $before, array $after): array
    {
        $improvements = [];

        // Score improvements
        foreach (['avg_sentiment', 'avg_completeness', 'avg_correctness'] as $metric) {
            if (isset($before['scores'][$metric]) && isset($after['scores'][$metric])) {
                $diff = $after['scores'][$metric] - $before['scores'][$metric];
                $improvements[$metric] = [
                    'change' => $diff,
                    'percentage' => $before['scores'][$metric] > 0 ? ($diff / $before['scores'][$metric]) * 100 : 0,
                ];
            }
        }

        return $improvements;
    }

    /**
     * Get empty overview (when no data available)
     */
    private function getEmptyOverview(): array
    {
        return [
            'run' => null,
            'overall_scores' => [],
            'top_sources' => [],
            'topic_performance' => [],
            'llm_comparison' => [],
            'alerts' => [],
            'recommendations' => [
                [
                    'priority' => 'high',
                    'category' => 'data_collection',
                    'message' => 'No data available. Run data collection first.',
                    'action' => 'Start a monitoring run to collect data from LLM systems',
                ]
            ],
        ];
    }
}
