<?php

namespace App\Services;

use App\Models\MonitoringRun;
use App\Database\Connection;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReportService
{
    private AnalyticsService $analytics;

    public function __construct()
    {
        $this->analytics = new AnalyticsService();
    }

    /**
     * Generate PDF report
     */
    public function generatePDFReport(int $runId, string $reportType = 'weekly'): string
    {
        $data = $this->prepareReportData($runId, $reportType);
        $html = $this->generateReportHTML($data, $reportType);

        // Configure Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Save PDF
        $reportsPath = config('app.paths.reports');
        if (!is_dir($reportsPath)) {
            mkdir($reportsPath, 0755, true);
        }

        $filename = sprintf(
            '%s/%s_report_%s_%s.pdf',
            $reportsPath,
            $reportType,
            date('Y-m-d'),
            $runId
        );

        file_put_contents($filename, $dompdf->output());

        // Save report record to database
        $this->saveReportRecord($reportType, $filename, $runId, $data);

        return $filename;
    }

    /**
     * Prepare report data
     */
    private function prepareReportData(int $runId, string $reportType): array
    {
        $run = MonitoringRun::getWithStats($runId);

        return [
            'run' => $run,
            'report_type' => $reportType,
            'generated_at' => date('Y-m-d H:i:s'),
            'overall_scores' => $this->analytics->getOverallScores($runId),
            'eeat_scores' => $this->analytics->getOverallEEATScore(),
            'topic_performance' => $this->analytics->getTopicPerformanceSummary($runId),
            'llm_comparison' => $this->analytics->getLLMComparison($runId),
            'top_sources' => $this->analytics->getSourceCitationDistribution($runId, 15),
            'alerts' => $this->analytics->generateAlerts($runId),
            'recommendations' => $this->analytics->generateRecommendations($runId),
        ];
    }

    /**
     * Generate HTML report
     */
    private function generateReportHTML(array $data, string $reportType): string
    {
        $title = $reportType === 'weekly' ? 'Weekly Report' : 'Analytics Report';

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?= $title ?> - SGEO Analytics</title>
            <style>
                body {
                    font-family: 'DejaVu Sans', sans-serif;
                    font-size: 11pt;
                    line-height: 1.6;
                    color: #333;
                }
                h1 {
                    color: #2c3e50;
                    border-bottom: 3px solid #3498db;
                    padding-bottom: 10px;
                }
                h2 {
                    color: #34495e;
                    border-bottom: 2px solid #95a5a6;
                    padding-bottom: 5px;
                    margin-top: 30px;
                }
                h3 {
                    color: #7f8c8d;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 15px 0;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #3498db;
                    color: white;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .score-high {
                    color: #27ae60;
                    font-weight: bold;
                }
                .score-medium {
                    color: #f39c12;
                    font-weight: bold;
                }
                .score-low {
                    color: #e74c3c;
                    font-weight: bold;
                }
                .summary-box {
                    background-color: #ecf0f1;
                    border-left: 4px solid #3498db;
                    padding: 15px;
                    margin: 15px 0;
                }
                .alert {
                    background-color: #fff3cd;
                    border-left: 4px solid #ffc107;
                    padding: 10px;
                    margin: 10px 0;
                }
                .recommendation {
                    background-color: #d1ecf1;
                    border-left: 4px solid #17a2b8;
                    padding: 10px;
                    margin: 10px 0;
                }
            </style>
        </head>
        <body>
            <h1><?= $title ?> - SGEO Analytics Dashboard</h1>

            <div class="summary-box">
                <p><strong>Generated:</strong> <?= $data['generated_at'] ?></p>
                <p><strong>Monitoring Run:</strong> #<?= $data['run']['id'] ?? 'N/A' ?></p>
                <p><strong>Run Date:</strong> <?= $data['run']['run_date'] ?? 'N/A' ?></p>
                <p><strong>Total Queries:</strong> <?= $data['run']['total_queries'] ?? 0 ?></p>
            </div>

            <h2>Executive Summary</h2>
            <?php if (!empty($data['overall_scores'])): ?>
            <table>
                <tr>
                    <th>Metric</th>
                    <th>Average Score</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td>Sentiment</td>
                    <td><?= round($data['overall_scores']['avg_sentiment'] ?? 0, 2) ?> / 5.0</td>
                    <td><?= $this->getScoreClass($data['overall_scores']['avg_sentiment'] ?? 0) ?></td>
                </tr>
                <tr>
                    <td>Completeness</td>
                    <td><?= round($data['overall_scores']['avg_completeness'] ?? 0, 2) ?> / 5.0</td>
                    <td><?= $this->getScoreClass($data['overall_scores']['avg_completeness'] ?? 0) ?></td>
                </tr>
                <tr>
                    <td>Correctness</td>
                    <td><?= round($data['overall_scores']['avg_correctness'] ?? 0, 2) ?> / 5.0</td>
                    <td><?= $this->getScoreClass($data['overall_scores']['avg_correctness'] ?? 0) ?></td>
                </tr>
            </table>
            <?php endif; ?>

            <?php if (!empty($data['eeat_scores'])): ?>
            <h2>Overall E-E-A-T Score</h2>
            <table>
                <tr>
                    <th>Metric</th>
                    <th>Score</th>
                </tr>
                <tr>
                    <td>Expertise</td>
                    <td><?= round($data['eeat_scores']['avg_expertise'] ?? 0, 2) ?> / 10</td>
                </tr>
                <tr>
                    <td>Authoritativeness</td>
                    <td><?= round($data['eeat_scores']['avg_authoritativeness'] ?? 0, 2) ?> / 10</td>
                </tr>
                <tr>
                    <td>Trustworthiness</td>
                    <td><?= round($data['eeat_scores']['avg_trustworthiness'] ?? 0, 2) ?> / 10</td>
                </tr>
                <tr>
                    <td>Experience</td>
                    <td><?= round($data['eeat_scores']['avg_experience'] ?? 0, 2) ?> / 10</td>
                </tr>
                <tr>
                    <td><strong>Overall E-E-A-T</strong></td>
                    <td><strong><?= round($data['eeat_scores']['avg_overall'] ?? 0, 2) ?> / 100</strong></td>
                </tr>
            </table>
            <?php endif; ?>

            <h2>Top Cited Sources</h2>
            <?php if (!empty($data['top_sources'])): ?>
            <table>
                <tr>
                    <th>Domain</th>
                    <th>Type</th>
                    <th>Official</th>
                    <th>E-E-A-T Score</th>
                    <th>Citations</th>
                </tr>
                <?php foreach (array_slice($data['top_sources'], 0, 10) as $source): ?>
                <tr>
                    <td><?= htmlspecialchars($source['domain']) ?></td>
                    <td><?= htmlspecialchars($source['source_type']) ?></td>
                    <td><?= $source['is_official'] ? '✓' : '—' ?></td>
                    <td><?= round($source['overall_eeat_score'] ?? 0, 1) ?></td>
                    <td><?= $source['citation_count'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php else: ?>
            <p>No source data available.</p>
            <?php endif; ?>

            <h2>Topic Performance</h2>
            <?php if (!empty($data['topic_performance'])): ?>
            <table>
                <tr>
                    <th>Topic</th>
                    <th>Importance</th>
                    <th>Sentiment</th>
                    <th>Completeness</th>
                    <th>Correctness</th>
                </tr>
                <?php foreach (array_slice($data['topic_performance'], 0, 20) as $topic): ?>
                <tr>
                    <td><?= htmlspecialchars($topic['name']) ?></td>
                    <td><?= $topic['strategic_importance'] ?>/10</td>
                    <td><?= round($topic['avg_sentiment'] ?? 0, 2) ?></td>
                    <td><?= round($topic['avg_completeness'] ?? 0, 2) ?></td>
                    <td><?= round($topic['avg_correctness'] ?? 0, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php else: ?>
            <p>No topic performance data available.</p>
            <?php endif; ?>

            <h2>LLM System Comparison</h2>
            <?php if (!empty($data['llm_comparison'])): ?>
            <table>
                <tr>
                    <th>LLM System</th>
                    <th>Provider</th>
                    <th>Responses</th>
                    <th>Avg Sentiment</th>
                    <th>Avg Completeness</th>
                    <th>Avg Correctness</th>
                </tr>
                <?php foreach ($data['llm_comparison'] as $llm): ?>
                <tr>
                    <td><?= htmlspecialchars($llm['name']) ?></td>
                    <td><?= htmlspecialchars($llm['provider']) ?></td>
                    <td><?= $llm['response_count'] ?></td>
                    <td><?= round($llm['avg_sentiment'] ?? 0, 2) ?></td>
                    <td><?= round($llm['avg_completeness'] ?? 0, 2) ?></td>
                    <td><?= round($llm['avg_correctness'] ?? 0, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php else: ?>
            <p>No LLM comparison data available.</p>
            <?php endif; ?>

            <?php if (!empty($data['alerts'])): ?>
            <h2>Alerts</h2>
            <?php foreach ($data['alerts'] as $alert): ?>
            <div class="alert">
                <?= htmlspecialchars($alert['message']) ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($data['recommendations'])): ?>
            <h2>Recommendations</h2>
            <?php foreach ($data['recommendations'] as $rec): ?>
            <div class="recommendation">
                <strong>[<?= strtoupper($rec['priority']) ?>]</strong> <?= htmlspecialchars($rec['message']) ?><br>
                <em>Action: <?= htmlspecialchars($rec['action']) ?></em>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Get score class for display
     */
    private function getScoreClass(float $score): string
    {
        if ($score >= 4) {
            return '<span class="score-high">🟢 High</span>';
        } elseif ($score >= 3) {
            return '<span class="score-medium">🟡 Medium</span>';
        } else {
            return '<span class="score-low">🔴 Low</span>';
        }
    }

    /**
     * Save report record to database
     */
    private function saveReportRecord(string $reportType, string $filePath, int $runId, array $data): void
    {
        $run = $data['run'] ?? [];

        Connection::insert('reports', [
            'report_type' => $reportType,
            'title' => ucfirst($reportType) . ' Report - ' . date('Y-m-d'),
            'period_start' => $run['run_date'] ?? date('Y-m-d'),
            'period_end' => date('Y-m-d'),
            'summary' => $this->generateSummary($data),
            'metrics' => json_encode($data['overall_scores'] ?? []),
            'recommendations' => json_encode($data['recommendations'] ?? []),
            'file_path' => $filePath,
            'generated_by' => 1, // TODO: Get actual user ID
        ]);
    }

    /**
     * Generate text summary
     */
    private function generateSummary(array $data): string
    {
        $scores = $data['overall_scores'] ?? [];

        return sprintf(
            "Report generated for monitoring run #%s. Average scores: Sentiment %.2f, Completeness %.2f, Correctness %.2f. %d alerts, %d recommendations.",
            $data['run']['id'] ?? 'N/A',
            $scores['avg_sentiment'] ?? 0,
            $scores['avg_completeness'] ?? 0,
            $scores['avg_correctness'] ?? 0,
            count($data['alerts'] ?? []),
            count($data['recommendations'] ?? [])
        );
    }
}
