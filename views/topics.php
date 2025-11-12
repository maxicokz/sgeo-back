<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topics - SGEO Analytics</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/partials/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Priority Topics</h1>
            <p class="subtitle">20 strategic topics about Kazakhstan monitored across LLM systems</p>
        </div>

        <?php if (!empty($topics)): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Topic Name (RU)</th>
                    <th>Topic Name (EN)</th>
                    <th>Strategic Importance</th>
                    <th>Status</th>
                    <th>Actions</th>
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
                        <a href="/topic?id=<?= $topic['id'] ?>" class="btn btn-sm btn-primary">View Details</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="no-data">No topics available.</p>
        <?php endif; ?>
    </div>
</body>
</html>
