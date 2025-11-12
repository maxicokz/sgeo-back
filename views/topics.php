<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Топики - SGEO Analytics</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/partials/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>📋 Приоритетные топики</h1>
            <p class="subtitle">20 стратегических тем о Казахстане, отслеживаемых в LLM системах</p>
        </div>

        <?php if (!empty($topics)): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Название топика (RU)</th>
                    <th>Название топика (EN)</th>
                    <th>Стратегическая важность</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topics as $topic): ?>
                <tr>
                    <td><strong><?= sanitize($topic['name_ru']) ?></strong></td>
                    <td><?= sanitize($topic['name_en'] ?? $topic['name']) ?></td>
                    <td>
                        <div class="importance-bar">
                            <div class="importance-fill" style="width: <?= $topic['strategic_importance'] * 10 ?>%"></div>
                            <span><?= $topic['strategic_importance'] ?> / 10</span>
                        </div>
                    </td>
                    <td><span class="status-badge status-<?= $topic['status'] ?>"><?= ucfirst($topic['status']) ?></span></td>
                    <td>
                        <a href="/topic?id=<?= $topic['id'] ?>" class="btn btn-sm btn-primary">Подробнее</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="no-data">📭 Нет доступных топиков.</p>
        <?php endif; ?>
    </div>
</body>
</html>
