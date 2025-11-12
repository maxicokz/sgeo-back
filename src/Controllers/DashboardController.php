<?php

namespace App\Controllers;

use App\Services\AnalyticsService;
use App\Models\Topic;
use App\Models\MonitoringRun;

class DashboardController
{
    private AnalyticsService $analytics;

    public function __construct()
    {
        $this->analytics = new AnalyticsService();
    }

    /**
     * Show main dashboard
     */
    public function index(): void
    {
        $runId = $_GET['run_id'] ?? null;
        $data = $this->analytics->getDashboardOverview($runId);

        $runs = MonitoringRun::all(10);

        echo view('dashboard', [
            'data' => $data,
            'runs' => $runs,
            'current_run_id' => $runId,
        ]);
    }

    /**
     * Show topics page
     */
    public function topics(): void
    {
        $topics = Topic::all();
        $runId = $_GET['run_id'] ?? null;

        echo view('topics', [
            'topics' => $topics,
            'run_id' => $runId,
        ]);
    }

    /**
     * Show topic details
     */
    public function topicDetails(): void
    {
        $topicId = $_GET['id'] ?? null;
        if (!$topicId) {
            redirect('/topics');
            return;
        }

        $topic = Topic::find($topicId);
        if (!$topic) {
            redirect('/topics');
            return;
        }

        $runId = $_GET['run_id'] ?? null;
        $performance = Topic::getPerformance($topicId, $runId);
        $responsesByLLM = Topic::getResponsesByLLM($topicId, $runId);
        $topSources = Topic::getTopSources($topicId);

        echo view('topic_details', [
            'topic' => $topic,
            'performance' => $performance,
            'responses_by_llm' => $responsesByLLM,
            'top_sources' => $topSources,
        ]);
    }

    /**
     * Show sources page
     */
    public function sources(): void
    {
        $officialOnly = isset($_GET['official']) && $_GET['official'] === '1';
        $sources = \App\Models\Source::all($officialOnly);

        echo view('sources', [
            'sources' => $sources,
            'official_only' => $officialOnly,
        ]);
    }

    /**
     * Show reports page
     */
    public function reports(): void
    {
        $reports = \App\Database\Connection::query(
            'SELECT * FROM reports ORDER BY generated_at DESC LIMIT 50'
        );

        echo view('reports', [
            'reports' => $reports,
        ]);
    }
}
