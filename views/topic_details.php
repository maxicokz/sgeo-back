<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($topic['name']) ?> - SGEO Analytics</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/partials/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>📋 <?= sanitize($topic['name']) ?></h1>
            <p class="subtitle"><?= sanitize($topic['description'] ?? '') ?></p>
        </div>

        <!-- Topic Info -->
        <section class="dashboard-section">
            <h2>ℹ️ Информация о топике</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Название (RU):</strong>
                    <span><?= sanitize($topic['name_ru'] ?? $topic['name']) ?></span>
                </div>
                <div class="info-item">
                    <strong>Название (EN):</strong>
                    <span><?= sanitize($topic['name_en'] ?? $topic['name']) ?></span>
                </div>
                <div class="info-item">
                    <strong>Стратегическая важность:</strong>
                    <span><?= $topic['strategic_importance'] ?? 0 ?> / 10</span>
                </div>
                <div class="info-item">
                    <strong>Статус:</strong>
                    <span class="status-badge status-<?= $topic['status'] ?? 'active' ?>">
                        <?= ucfirst($topic['status'] ?? 'active') ?>
                    </span>
                </div>
            </div>
        </section>

        <!-- LLM Responses -->
        <?php if (!empty($responses)): ?>
        <section class="dashboard-section">
            <h2>🤖 Ответы LLM систем</h2>
            <div class="responses-container">
                <?php foreach ($responses as $response): ?>
                <div class="response-card">
                    <div class="response-header">
                        <h3><?= sanitize($response['llm_name'] ?? 'Unknown LLM') ?></h3>
                        <span class="response-date"><?= format_date($response['created_at'] ?? date('Y-m-d H:i:s')) ?></span>
                    </div>

                    <div class="response-scores">
                        <div class="score-item">
                            <span class="score-label">Тональность:</span>
                            <span class="score-value"><?= round($response['sentiment_score'] ?? 0, 1) ?> / 5</span>
                        </div>
                        <div class="score-item">
                            <span class="score-label">Полнота:</span>
                            <span class="score-value"><?= round($response['completeness_score'] ?? 0, 1) ?> / 5</span>
                        </div>
                        <div class="score-item">
                            <span class="score-label">Правильность:</span>
                            <span class="score-value"><?= round($response['correctness_score'] ?? 0, 1) ?> / 5</span>
                        </div>
                    </div>

                    <div class="response-text">
                        <p><?= nl2br(sanitize(substr($response['response_text'] ?? '', 0, 500))) ?></p>
                        <?php if (strlen($response['response_text'] ?? '') > 500): ?>
                        <button class="btn btn-sm" onclick="toggleFullText(<?= $response['id'] ?>)">
                            Показать полностью
                        </button>
                        <div id="full-text-<?= $response['id'] ?>" style="display: none;">
                            <p><?= nl2br(sanitize($response['response_text'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($response['cited_sources'])): ?>
                    <div class="response-sources">
                        <strong>Источники:</strong>
                        <?php
                        $sources = is_string($response['cited_sources'])
                            ? json_decode($response['cited_sources'], true)
                            : $response['cited_sources'];
                        ?>
                        <ul>
                            <?php foreach ($sources as $source): ?>
                            <li>
                                <a href="<?= sanitize($source['url'] ?? '#') ?>" target="_blank" rel="noopener">
                                    <?= sanitize($source['domain'] ?? $source['url'] ?? 'Unknown') ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php else: ?>
        <section class="dashboard-section">
            <p class="no-data">📭 Нет данных для этого топика. Запустите сбор данных.</p>
        </section>
        <?php endif; ?>

        <!-- Statistics -->
        <?php if (!empty($statistics)): ?>
        <section class="dashboard-section">
            <h2>📊 Статистика</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= $statistics['total_responses'] ?? 0 ?></div>
                    <div class="stat-label">Всего ответов</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= round($statistics['avg_sentiment'] ?? 0, 2) ?></div>
                    <div class="stat-label">Средняя тональность</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= round($statistics['avg_completeness'] ?? 0, 2) ?></div>
                    <div class="stat-label">Средняя полнота</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= round($statistics['avg_correctness'] ?? 0, 2) ?></div>
                    <div class="stat-label">Средняя правильность</div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <div class="page-actions">
            <a href="/topics" class="btn btn-secondary">← Назад к топикам</a>
        </div>
    </div>

    <script>
        function toggleFullText(responseId) {
            const element = document.getElementById('full-text-' + responseId);
            if (element) {
                element.style.display = element.style.display === 'none' ? 'block' : 'none';
            }
        }
    </script>

    <style>
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .info-item strong {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        .responses-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .response-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
        }

        .response-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .response-header h3 {
            margin: 0;
            color: #2c3e50;
        }

        .response-date {
            color: #999;
            font-size: 14px;
        }

        .response-scores {
            display: flex;
            gap: 20px;
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .score-item {
            flex: 1;
            text-align: center;
        }

        .score-label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .score-value {
            display: block;
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }

        .response-text {
            margin: 15px 0;
            line-height: 1.6;
        }

        .response-sources {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }

        .response-sources ul {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }

        .response-sources li {
            padding: 5px 0;
        }

        .response-sources a {
            color: #3498db;
            text-decoration: none;
        }

        .response-sources a:hover {
            text-decoration: underline;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .page-actions {
            margin-top: 30px;
            text-align: center;
        }
    </style>
</body>
</html>
