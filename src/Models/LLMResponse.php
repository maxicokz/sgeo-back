<?php

namespace App\Models;

use App\Database\Connection;

class LLMResponse
{
    /**
     * Get all responses
     */
    public static function all(int $limit = 100): array
    {
        return Connection::query(
            'SELECT lr.*, t.name as topic_name, ls.name as llm_name
             FROM llm_responses lr
             JOIN topics t ON lr.topic_id = t.id
             JOIN llm_systems ls ON lr.llm_system_id = ls.id
             ORDER BY lr.created_at DESC
             LIMIT ?',
            [$limit]
        );
    }

    /**
     * Get response by ID
     */
    public static function find(int $id): ?array
    {
        return Connection::queryOne(
            'SELECT lr.*, t.name as topic_name, ls.name as llm_name, ls.provider
             FROM llm_responses lr
             JOIN topics t ON lr.topic_id = t.id
             JOIN llm_systems ls ON lr.llm_system_id = ls.id
             WHERE lr.id = ?',
            [$id]
        );
    }

    /**
     * Get responses by monitoring run
     */
    public static function getByRun(int $runId): array
    {
        return Connection::query(
            'SELECT lr.*, t.name as topic_name, ls.name as llm_name
             FROM llm_responses lr
             JOIN topics t ON lr.topic_id = t.id
             JOIN llm_systems ls ON lr.llm_system_id = ls.id
             WHERE lr.monitoring_run_id = ?
             ORDER BY t.name, ls.name',
            [$runId]
        );
    }

    /**
     * Get responses by topic
     */
    public static function getByTopic(int $topicId, ?int $runId = null): array
    {
        $sql = '
            SELECT lr.*, ls.name as llm_name, ls.provider
            FROM llm_responses lr
            JOIN llm_systems ls ON lr.llm_system_id = ls.id
            WHERE lr.topic_id = ?
        ';

        $params = [$topicId];

        if ($runId) {
            $sql .= ' AND lr.monitoring_run_id = ?';
            $params[] = $runId;
        }

        $sql .= ' ORDER BY lr.created_at DESC';

        return Connection::query($sql, $params);
    }

    /**
     * Get responses by LLM system
     */
    public static function getByLLM(int $llmId, ?int $runId = null): array
    {
        $sql = '
            SELECT lr.*, t.name as topic_name
            FROM llm_responses lr
            JOIN topics t ON lr.topic_id = t.id
            WHERE lr.llm_system_id = ?
        ';

        $params = [$llmId];

        if ($runId) {
            $sql .= ' AND lr.monitoring_run_id = ?';
            $params[] = $runId;
        }

        $sql .= ' ORDER BY lr.created_at DESC';

        return Connection::query($sql, $params);
    }

    /**
     * Get average scores
     */
    public static function getAverageScores(?int $runId = null, ?int $topicId = null, ?int $llmId = null): array
    {
        $sql = '
            SELECT
                AVG(sentiment_score) as avg_sentiment,
                AVG(completeness_score) as avg_completeness,
                AVG(correctness_score) as avg_correctness,
                COUNT(*) as total_responses
            FROM llm_responses
            WHERE 1=1
        ';

        $params = [];

        if ($runId) {
            $sql .= ' AND monitoring_run_id = ?';
            $params[] = $runId;
        }

        if ($topicId) {
            $sql .= ' AND topic_id = ?';
            $params[] = $topicId;
        }

        if ($llmId) {
            $sql .= ' AND llm_system_id = ?';
            $params[] = $llmId;
        }

        return Connection::queryOne($sql, $params) ?? [];
    }

    /**
     * Get responses with low scores
     */
    public static function getLowScoreResponses(float $threshold = 3.0, ?int $runId = null): array
    {
        $sql = '
            SELECT lr.*, t.name as topic_name, ls.name as llm_name
            FROM llm_responses lr
            JOIN topics t ON lr.topic_id = t.id
            JOIN llm_systems ls ON lr.llm_system_id = ls.id
            WHERE (
                lr.sentiment_score < ? OR
                lr.completeness_score < ? OR
                lr.correctness_score < ?
            )
        ';

        $params = [$threshold, $threshold, $threshold];

        if ($runId) {
            $sql .= ' AND lr.monitoring_run_id = ?';
            $params[] = $runId;
        }

        $sql .= ' ORDER BY lr.sentiment_score ASC, lr.correctness_score ASC';

        return Connection::query($sql, $params);
    }

    /**
     * Create new response
     */
    public static function create(array $data): int
    {
        // Process cited sources
        if (isset($data['cited_sources']) && is_array($data['cited_sources'])) {
            $data['cited_sources'] = json_encode($data['cited_sources']);
        }

        // Process metadata
        if (isset($data['response_metadata']) && is_array($data['response_metadata'])) {
            $data['response_metadata'] = json_encode($data['response_metadata']);
        }

        return Connection::insert('llm_responses', $data);
    }

    /**
     * Update response
     */
    public static function update(int $id, array $data): bool
    {
        // Process JSON fields
        if (isset($data['cited_sources']) && is_array($data['cited_sources'])) {
            $data['cited_sources'] = json_encode($data['cited_sources']);
        }

        if (isset($data['response_metadata']) && is_array($data['response_metadata'])) {
            $data['response_metadata'] = json_encode($data['response_metadata']);
        }

        return Connection::update('llm_responses', $data, 'id = ?', [$id]);
    }

    /**
     * Delete response
     */
    public static function delete(int $id): bool
    {
        return Connection::delete('llm_responses', 'id = ?', [$id]);
    }

    /**
     * Extract and process cited sources from response
     */
    public static function extractSources(int $responseId): array
    {
        $response = self::find($responseId);
        if (!$response || !$response['cited_sources']) {
            return [];
        }

        $citedSources = json_decode($response['cited_sources'], true);
        if (!is_array($citedSources)) {
            return [];
        }

        $sources = [];
        foreach ($citedSources as $source) {
            if (isset($source['domain'])) {
                $sources[] = $source['domain'];
            }
        }

        return $sources;
    }
}
