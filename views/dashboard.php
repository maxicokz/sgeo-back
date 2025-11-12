<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SGEO Analytics</title>
    <link rel="stylesheet" href="/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <?php include __DIR__ . '/partials/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>SGEO Analytics Dashboard</h1>
            <div class="page-actions">
                <button class="btn btn-primary" onclick="startCollection()">Start Data Collection</button>
                <button class="btn btn-secondary" onclick="generateReport()">Generate Report</button>
            </div>
        </div>

        <?php if ($data['run']): ?>
        <div class="info-box">
            <strong>Current Run:</strong> #<?= $data['run']['id'] ?> |
            <strong>Date:</strong> <?= format_date($data['run']['run_date']) ?> |
            <strong>Status:</strong> <span class="status-<?= $data['run']['status'] ?>"><?= ucfirst($data['run']['status']) ?></span> |
            <strong>Queries:</strong> <?= $data['run']['completed_queries'] ?> / <?= $data['run']['total_queries'] ?>
        </div>
        <?php endif; ?>

        <!-- Overall Scores Section -->
        <section class="dashboard-section">
            <h2>Overall Content Scores</h2>
            <?php if (!empty($data['overall_scores'])): ?>
            <div class="score-cards">
                <div class="score-card">
                    <h3>Sentiment</h3>
                    <div class="score-value"><?= round($data['overall_scores']['avg_sentiment'] ?? 0, 2) ?></div>
                    <div class="score-max">/ 5.0</div>
                    <?= get_status_badge($data['overall_scores']['avg_sentiment'] ?? 0) ?>
                </div>
                <div class="score-card">
                    <h3>Completeness</h3>
                    <div class="score-value"><?= round($data['overall_scores']['avg_completeness'] ?? 0, 2) ?></div>
                    <div class="score-max">/ 5.0</div>
                    <?= get_status_badge($data['overall_scores']['avg_completeness'] ?? 0) ?>
                </div>
                <div class="score-card">
                    <h3>Correctness</h3>
                    <div class="score-value"><?= round($data['overall_scores']['avg_correctness'] ?? 0, 2) ?></div>
                    <div class="score-max">/ 5.0</div>
                    <?= get_status_badge($data['overall_scores']['avg_correctness'] ?? 0) ?>
                </div>
                <div class="score-card">
                    <h3>Total Responses</h3>
                    <div class="score-value"><?= $data['overall_scores']['total_responses'] ?? 0 ?></div>
                    <div class="score-max">responses</div>
                </div>
            </div>
            <?php else: ?>
            <p class="no-data">No data available. Start a data collection run to see analytics.</p>
            <?php endif; ?>
        </section>

        <!-- Top Cited Sources -->
        <section class="dashboard-section">
            <h2>Top Cited Sources</h2>
            <?php if (!empty($data['top_sources'])): ?>
            <div class="chart-container">
                <canvas id="sourcesChart"></canvas>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Type</th>
                        <th>Official</th>
                        <th>E-E-A-T Score</th>
                        <th>Citations</th>
                        <th>Topics</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($data['top_sources'], 0, 10) as $source): ?>
                    <tr>
                        <td><strong><?= sanitize($source['domain']) ?></strong></td>
                        <td><?= sanitize($source['source_type']) ?></td>
                        <td><?= $source['is_official'] ? '✓ Yes' : '—' ?></td>
                        <td><?= round($source['overall_eeat_score'] ?? 0, 1) ?> / 100</td>
                        <td><?= $source['citation_count'] ?></td>
                        <td><?= $source['topics_covered'] ?? 0 ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <a href="/sources" class="btn btn-link">View All Sources →</a>
            <?php else: ?>
            <p class="no-data">No source data available.</p>
            <?php endif; ?>
        </section>

        <!-- LLM Comparison -->
        <section class="dashboard-section">
            <h2>LLM System Comparison</h2>
            <?php if (!empty($data['llm_comparison'])): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>LLM System</th>
                        <th>Provider</th>
                        <th>Responses</th>
                        <th>Sentiment</th>
                        <th>Completeness</th>
                        <th>Correctness</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['llm_comparison'] as $llm): ?>
                    <tr>
                        <td><strong><?= sanitize($llm['name']) ?></strong></td>
                        <td><?= sanitize($llm['provider']) ?></td>
                        <td><?= $llm['response_count'] ?></td>
                        <td><?= round($llm['avg_sentiment'] ?? 0, 2) ?></td>
                        <td><?= round($llm['avg_completeness'] ?? 0, 2) ?></td>
                        <td><?= round($llm['avg_correctness'] ?? 0, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="no-data">No LLM comparison data available.</p>
            <?php endif; ?>
        </section>

        <!-- Alerts -->
        <?php if (!empty($data['alerts'])): ?>
        <section class="dashboard-section">
            <h2>Alerts</h2>
            <div class="alerts-container">
                <?php foreach ($data['alerts'] as $alert): ?>
                <div class="alert alert-<?= $alert['level'] ?>">
                    <?= sanitize($alert['message']) ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Recommendations -->
        <?php if (!empty($data['recommendations'])): ?>
        <section class="dashboard-section">
            <h2>Recommendations</h2>
            <div class="recommendations-container">
                <?php foreach ($data['recommendations'] as $rec): ?>
                <div class="recommendation recommendation-<?= $rec['priority'] ?>">
                    <div class="recommendation-header">
                        <span class="priority-badge"><?= strtoupper($rec['priority']) ?></span>
                        <strong><?= sanitize($rec['message']) ?></strong>
                    </div>
                    <p class="recommendation-action">Action: <?= sanitize($rec['action']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <script src="/js/dashboard.js"></script>
    <script>
        // Chart for top sources
        <?php if (!empty($data['top_sources'])): ?>
        const sourcesData = <?= json_encode(array_slice($data['top_sources'], 0, 10)) ?>;
        const ctx = document.getElementById('sourcesChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: sourcesData.map(s => s.domain),
                datasets: [{
                    label: 'Citations',
                    data: sourcesData.map(s => s.citation_count),
                    backgroundColor: 'rgba(52, 152, 219, 0.6)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
