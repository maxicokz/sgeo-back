<?php

namespace App\Models;

use App\Database\Connection;

class Source
{
    /**
     * Get all sources
     */
    public static function all(bool $officialOnly = false): array
    {
        $sql = 'SELECT * FROM sources';

        if ($officialOnly) {
            $sql .= ' WHERE is_official = true';
        }

        $sql .= ' ORDER BY citation_count DESC, overall_eeat_score DESC';

        return Connection::query($sql);
    }

    /**
     * Get source by ID
     */
    public static function find(int $id): ?array
    {
        return Connection::queryOne('SELECT * FROM sources WHERE id = ?', [$id]);
    }

    /**
     * Get source by domain
     */
    public static function findByDomain(string $domain): ?array
    {
        return Connection::queryOne('SELECT * FROM sources WHERE domain = ?', [$domain]);
    }

    /**
     * Get top cited sources
     */
    public static function getTopCited(int $limit = 10, ?int $runId = null): array
    {
        $sql = '
            SELECT
                s.*,
                COUNT(sa.id) as citation_count_period
            FROM sources s
            LEFT JOIN source_analytics sa ON s.id = sa.source_id
        ';

        $params = [];

        if ($runId) {
            $sql .= ' AND sa.monitoring_run_id = ?';
            $params[] = $runId;
        }

        $sql .= '
            GROUP BY s.id
            ORDER BY citation_count_period DESC, s.overall_eeat_score DESC
            LIMIT ?
        ';

        $params[] = $limit;

        return Connection::query($sql, $params);
    }

    /**
     * Get sources by E-E-A-T score range
     */
    public static function getByEEATRange(float $minScore, float $maxScore): array
    {
        return Connection::query(
            'SELECT * FROM sources WHERE overall_eeat_score BETWEEN ? AND ? ORDER BY overall_eeat_score DESC',
            [$minScore, $maxScore]
        );
    }

    /**
     * Get sources needing optimization
     */
    public static function getNeedingOptimization(): array
    {
        return Connection::query(
            "SELECT * FROM sources
             WHERE optimization_status IN ('pending', 'in_progress')
             ORDER BY optimization_priority DESC, overall_eeat_score ASC"
        );
    }

    /**
     * Calculate and update E-E-A-T score
     */
    public static function calculateEEATScore(int $id): bool
    {
        $source = self::find($id);
        if (!$source) {
            return false;
        }

        $eeatScore = calculate_eeat_score(
            $source['expertise_score'] ?? 0,
            $source['authoritativeness_score'] ?? 0,
            $source['trustworthiness_score'] ?? 0,
            $source['experience_score'] ?? 0
        );

        return Connection::update('sources', ['overall_eeat_score' => $eeatScore], 'id = ?', [$id]);
    }

    /**
     * Update source metrics
     */
    public static function updateMetrics(int $id, array $metrics): bool
    {
        $data = [
            'expertise_score' => $metrics['expertise'] ?? null,
            'authoritativeness_score' => $metrics['authoritativeness'] ?? null,
            'trustworthiness_score' => $metrics['trustworthiness'] ?? null,
            'experience_score' => $metrics['experience'] ?? null,
            'authorship_clarity' => $metrics['authorship_clarity'] ?? null,
            'content_accuracy' => $metrics['content_accuracy'] ?? null,
            'reputation_score' => $metrics['reputation_score'] ?? null,
            'security_score' => $metrics['security_score'] ?? null,
            'freshness_score' => $metrics['freshness_score'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Remove null values
        $data = array_filter($data, fn($value) => $value !== null);

        $result = Connection::update('sources', $data, 'id = ?', [$id]);

        if ($result) {
            self::calculateEEATScore($id);
        }

        return $result;
    }

    /**
     * Increment citation count
     */
    public static function incrementCitation(int $id): bool
    {
        return Connection::execute(
            'UPDATE sources SET citation_count = citation_count + 1, last_cited_at = NOW() WHERE id = ?',
            [$id]
        );
    }

    /**
     * Create new source
     */
    public static function create(array $data): int
    {
        // Calculate E-E-A-T if scores provided
        if (isset($data['expertise_score'], $data['authoritativeness_score'], $data['trustworthiness_score'], $data['experience_score'])) {
            $data['overall_eeat_score'] = calculate_eeat_score(
                $data['expertise_score'],
                $data['authoritativeness_score'],
                $data['trustworthiness_score'],
                $data['experience_score']
            );
        }

        return Connection::insert('sources', $data);
    }

    /**
     * Update source
     */
    public static function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return Connection::update('sources', $data, 'id = ?', [$id]);
    }

    /**
     * Delete source
     */
    public static function delete(int $id): bool
    {
        return Connection::delete('sources', 'id = ?', [$id]);
    }

    /**
     * Get source analytics history
     */
    public static function getAnalyticsHistory(int $sourceId, int $limit = 30): array
    {
        return Connection::query(
            'SELECT sa.*, mr.run_date
             FROM source_analytics sa
             JOIN monitoring_runs mr ON sa.monitoring_run_id = mr.id
             WHERE sa.source_id = ?
             ORDER BY mr.run_date DESC
             LIMIT ?',
            [$sourceId, $limit]
        );
    }
}
