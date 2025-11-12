<?php

namespace App\Models;

use App\Database\Connection;

class Topic
{
    /**
     * Get all topics
     */
    public static function all(): array
    {
        return Connection::query('SELECT * FROM topics ORDER BY strategic_importance DESC, name');
    }

    /**
     * Get topic by ID
     */
    public static function find(int $id): ?array
    {
        return Connection::queryOne('SELECT * FROM topics WHERE id = ?', [$id]);
    }

    /**
     * Get topic performance
     */
    public static function getPerformance(int $topicId, ?int $runId = null): array
    {
        $sql = '
            SELECT
                t.*,
                COUNT(DISTINCT lr.id) as response_count,
                AVG(lr.sentiment_score) as avg_sentiment,
                AVG(lr.completeness_score) as avg_completeness,
                AVG(lr.correctness_score) as avg_correctness,
                COUNT(DISTINCT CASE WHEN lr.sentiment_score >= 4 THEN lr.id END) as positive_count
            FROM topics t
            LEFT JOIN llm_responses lr ON t.id = lr.topic_id
        ';

        $params = [$topicId];

        if ($runId) {
            $sql .= ' AND lr.monitoring_run_id = ?';
            $params[] = $runId;
        }

        $sql .= ' WHERE t.id = ? GROUP BY t.id';

        return Connection::queryOne($sql, $params) ?? [];
    }

    /**
     * Get topic responses by LLM
     */
    public static function getResponsesByLLM(int $topicId, ?int $runId = null): array
    {
        $sql = '
            SELECT
                ls.name as llm_name,
                ls.provider,
                AVG(lr.sentiment_score) as avg_sentiment,
                AVG(lr.completeness_score) as avg_completeness,
                AVG(lr.correctness_score) as avg_correctness,
                COUNT(lr.id) as response_count
            FROM llm_systems ls
            LEFT JOIN llm_responses lr ON ls.id = lr.llm_system_id AND lr.topic_id = ?
        ';

        $params = [$topicId];

        if ($runId) {
            $sql .= ' AND lr.monitoring_run_id = ?';
            $params[] = $runId;
        }

        $sql .= ' GROUP BY ls.id, ls.name, ls.provider ORDER BY ls.name';

        return Connection::query($sql, $params);
    }

    /**
     * Get top cited sources for topic
     */
    public static function getTopSources(int $topicId, int $limit = 10): array
    {
        $sql = '
            SELECT
                s.domain,
                s.title,
                s.overall_eeat_score,
                COUNT(*) as citation_count
            FROM sources s
            JOIN source_analytics sa ON s.id = sa.source_id
            WHERE sa.topic_id = ?
            GROUP BY s.id, s.domain, s.title, s.overall_eeat_score
            ORDER BY citation_count DESC
            LIMIT ?
        ';

        return Connection::query($sql, [$topicId, $limit]);
    }

    /**
     * Create new topic
     */
    public static function create(array $data): int
    {
        return Connection::insert('topics', $data);
    }

    /**
     * Update topic
     */
    public static function update(int $id, array $data): bool
    {
        return Connection::update('topics', $data, 'id = ?', [$id]);
    }

    /**
     * Delete topic
     */
    public static function delete(int $id): bool
    {
        return Connection::delete('topics', 'id = ?', [$id]);
    }
}
