<?php

namespace App\Models;

use App\Database\Connection;

class MonitoringRun
{
    /**
     * Get all monitoring runs
     */
    public static function all(int $limit = 50): array
    {
        return Connection::query(
            'SELECT * FROM monitoring_runs ORDER BY run_date DESC LIMIT ?',
            [$limit]
        );
    }

    /**
     * Get monitoring run by ID
     */
    public static function find(int $id): ?array
    {
        return Connection::queryOne('SELECT * FROM monitoring_runs WHERE id = ?', [$id]);
    }

    /**
     * Get latest monitoring run
     */
    public static function getLatest(): ?array
    {
        return Connection::queryOne(
            'SELECT * FROM monitoring_runs ORDER BY run_date DESC LIMIT 1'
        );
    }

    /**
     * Get monitoring run with statistics
     */
    public static function getWithStats(int $id): ?array
    {
        return Connection::queryOne(
            'SELECT * FROM v_latest_monitoring_summary WHERE id = ?',
            [$id]
        );
    }

    /**
     * Create new monitoring run
     */
    public static function create(int $totalQueries = 0, string $notes = ''): int
    {
        return Connection::insert('monitoring_runs', [
            'status' => 'running',
            'total_queries' => $totalQueries,
            'completed_queries' => 0,
            'failed_queries' => 0,
            'notes' => $notes,
        ]);
    }

    /**
     * Update monitoring run
     */
    public static function update(int $id, array $data): bool
    {
        return Connection::update('monitoring_runs', $data, 'id = ?', [$id]);
    }

    /**
     * Increment completed queries
     */
    public static function incrementCompleted(int $id): bool
    {
        return Connection::execute(
            'UPDATE monitoring_runs SET completed_queries = completed_queries + 1 WHERE id = ?',
            [$id]
        );
    }

    /**
     * Increment failed queries
     */
    public static function incrementFailed(int $id): bool
    {
        return Connection::execute(
            'UPDATE monitoring_runs SET failed_queries = failed_queries + 1 WHERE id = ?',
            [$id]
        );
    }

    /**
     * Mark run as completed
     */
    public static function markCompleted(int $id): bool
    {
        return self::update($id, [
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Mark run as failed
     */
    public static function markFailed(int $id, string $reason = ''): bool
    {
        return self::update($id, [
            'status' => 'failed',
            'notes' => $reason,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get run progress percentage
     */
    public static function getProgress(int $id): float
    {
        $run = self::find($id);
        if (!$run || $run['total_queries'] == 0) {
            return 0;
        }

        return round(($run['completed_queries'] / $run['total_queries']) * 100, 2);
    }

    /**
     * Delete monitoring run
     */
    public static function delete(int $id): bool
    {
        return Connection::delete('monitoring_runs', 'id = ?', [$id]);
    }
}
