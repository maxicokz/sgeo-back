<?php

namespace App\Services;

use App\Database\Connection;

class AuditService
{
    public function logLogin(int $userId, string $username, string $ip, string $userAgent): void
    {
        $this->log('login', $userId, [
            'username' => $username,
            'ip'       => $ip,
            'ua'       => substr($userAgent, 0, 500),
        ]);
    }

    public function logLogout(int $userId, string $username, string $ip): void
    {
        $this->log('logout', $userId, [
            'username' => $username,
            'ip'       => $ip,
        ]);
    }

    public function logLoginFailed(string $username, string $ip, string $userAgent): void
    {
        $this->log('login_failed', 0, [
            'username' => $username,
            'ip'       => $ip,
            'ua'       => substr($userAgent, 0, 500),
        ]);
    }

    public function logLoginBlocked(string $username, string $ip): void
    {
        $this->log('login_blocked', 0, [
            'username' => $username,
            'ip'       => $ip,
        ]);
    }

    public function logPasswordChanged(int $userId, string $ip): void
    {
        $this->log('password_changed', $userId, [
            'ip' => $ip,
        ]);
    }

    public function logReportGenerated(int $userId, int $runId, string $reportType): void
    {
        $this->log('report_generated', $userId, [
            'run_id'      => $runId,
            'report_type' => $reportType,
        ]);
    }

    public function logDataCollection(int $userId, array $topicIds): void
    {
        $this->log('data_collection', $userId, [
            'topic_ids' => $topicIds,
            'count'     => count($topicIds),
        ]);
    }

    public function logTopicViewed(int $userId, int $topicId): void
    {
        $this->log('topic_viewed', $userId, [
            'topic_id' => $topicId,
        ]);
    }

    public function logReportExported(int $userId, int $runId, string $format): void
    {
        $this->log('report_exported', $userId, [
            'run_id' => $runId,
            'format' => $format,
        ]);
    }

    public function logSettingsChanged(int $userId, string $setting): void
    {
        $this->log('settings_changed', $userId, [
            'setting' => $setting,
        ]);
    }

    /**
     * Core logging — INSERT INTO audit_logs.
     * Context is stored as JSON. Passwords and tokens are never included.
     */
    private function log(string $action, int $userId, array $context): void
    {
        // Sanitize: strip any accidental sensitive keys
        $forbidden = ['password', 'password_hash', 'token', 'secret', 'api_key', 'access_token'];
        foreach ($forbidden as $key) {
            unset($context[$key]);
        }

        $ip        = $_SERVER['REMOTE_ADDR'] ?? ($context['ip'] ?? null);
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? ($context['ua'] ?? null);

        try {
            Connection::insert('audit_logs', [
                'user_id'    => $userId > 0 ? $userId : null,
                'action'     => $action,
                'ip'         => $ip,
                'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
                'context'    => json_encode($context, JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Logging must never break the application flow
            error_log('[AuditService] Failed to write audit log: ' . $e->getMessage());
        }
    }
}
