<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчеты - SGEO Analytics</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/partials/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>📊 Сгенерированные отчеты</h1>
            <button class="btn btn-primary" onclick="generateNewReport()">Создать новый отчет</button>
        </div>

        <?php if (!empty($reports)): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Тип отчета</th>
                    <th>Название</th>
                    <th>Период</th>
                    <th>Создан</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                <tr>
                    <td><span class="badge"><?= ucfirst($report['report_type']) ?></span></td>
                    <td><strong><?= sanitize($report['title']) ?></strong></td>
                    <td><?= format_date($report['period_start'], 'Y-m-d') ?> — <?= format_date($report['period_end'], 'Y-m-d') ?></td>
                    <td><?= format_date($report['generated_at']) ?></td>
                    <td>
                        <?php if ($report['file_path'] && file_exists($report['file_path'])): ?>
                        <a href="<?= $report['file_path'] ?>" class="btn btn-sm btn-primary" download>Скачать PDF</a>
                        <?php else: ?>
                        <span class="text-muted">Файл не найден</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="no-data">📭 Отчеты еще не созданы.</p>
        <?php endif; ?>
    </div>

    <script>
        function generateNewReport() {
            // TODO: Show modal to select run and report type
            alert('Функция создания отчетов - скоро будет доступна');
        }
    </script>
</body>
</html>
