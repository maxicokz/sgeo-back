<?php

namespace App\Controllers;

use App\Services\DataCollectionService;
use App\Services\AnalyticsService;
use App\Services\ReportService;
use App\Models\MonitoringRun;
use App\Models\Topic;
use App\Models\Source;

class ApiController
{
    /**
     * Start data collection
     */
    public function startCollection(): void
    {
        try {
            $topicIds = $_POST['topic_ids'] ?? [];
            $llmModels = $_POST['llm_models'] ?? [];

            $service = new DataCollectionService();
            $runId = $service->runCollection($topicIds, $llmModels);

            json_response([
                'success' => true,
                'run_id' => $runId,
                'message' => 'Data collection started successfully',
            ]);
        } catch (\Exception $e) {
            error_response($e->getMessage(), 500);
        }
    }

    /**
     * Get monitoring run status
     */
    public function getRunStatus(): void
    {
        $runId = $_GET['run_id'] ?? null;
        if (!$runId) {
            error_response('Run ID is required');
        }

        $run = MonitoringRun::getWithStats($runId);
        if (!$run) {
            error_response('Run not found', 404);
        }

        $progress = MonitoringRun::getProgress($runId);

        json_response([
            'success' => true,
            'run' => $run,
            'progress' => $progress,
        ]);
    }

    /**
     * Generate report
     */
    public function generateReport(): void
    {
        try {
            $runId = $_POST['run_id'] ?? null;
            $reportType = $_POST['report_type'] ?? 'weekly';

            if (!$runId) {
                error_response('Run ID is required');
            }

            $service = new ReportService();
            $filePath = $service->generatePDFReport($runId, $reportType);

            json_response([
                'success' => true,
                'file_path' => $filePath,
                'message' => 'Report generated successfully',
            ]);
        } catch (\Exception $e) {
            error_response($e->getMessage(), 500);
        }
    }

    /**
     * Get dashboard data (API endpoint)
     */
    public function getDashboardData(): void
    {
        try {
            $runId = $_GET['run_id'] ?? null;

            $analytics = new AnalyticsService();
            $data = $analytics->getDashboardOverview($runId);

            json_response([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            error_response($e->getMessage(), 500);
        }
    }

    /**
     * Get topics
     */
    public function getTopics(): void
    {
        try {
            $topics = Topic::all();

            json_response([
                'success' => true,
                'topics' => $topics,
            ]);
        } catch (\Exception $e) {
            error_response($e->getMessage(), 500);
        }
    }

    /**
     * Get topic performance
     */
    public function getTopicPerformance(): void
    {
        try {
            $topicId = $_GET['topic_id'] ?? null;
            $runId = $_GET['run_id'] ?? null;

            if (!$topicId) {
                error_response('Topic ID is required');
            }

            $performance = Topic::getPerformance($topicId, $runId);
            $responsesByLLM = Topic::getResponsesByLLM($topicId, $runId);

            json_response([
                'success' => true,
                'performance' => $performance,
                'responses_by_llm' => $responsesByLLM,
            ]);
        } catch (\Exception $e) {
            error_response($e->getMessage(), 500);
        }
    }

    /**
     * Get sources
     */
    public function getSources(): void
    {
        try {
            $officialOnly = isset($_GET['official']) && $_GET['official'] === '1';
            $sources = Source::all($officialOnly);

            json_response([
                'success' => true,
                'sources' => $sources,
            ]);
        } catch (\Exception $e) {
            error_response($e->getMessage(), 500);
        }
    }

    /**
     * Update source E-E-A-T metrics
     */
    public function updateSourceMetrics(): void
    {
        try {
            $sourceId = $_POST['source_id'] ?? null;
            $metrics = $_POST['metrics'] ?? [];

            if (!$sourceId) {
                error_response('Source ID is required');
            }

            $result = Source::updateMetrics($sourceId, $metrics);

            json_response([
                'success' => $result,
                'message' => $result ? 'Metrics updated successfully' : 'Failed to update metrics',
            ]);
        } catch (\Exception $e) {
            error_response($e->getMessage(), 500);
        }
    }
}
