<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - SGEO Analytics</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/partials/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Generated Reports</h1>
            <button class="btn btn-primary" onclick="generateNewReport()">Generate New Report</button>
        </div>

        <?php if (!empty($reports)): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Report Type</th>
                    <th>Title</th>
                    <th>Period</th>
                    <th>Generated At</th>
                    <th>Actions</th>
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
                        <a href="<?= $report['file_path'] ?>" class="btn btn-sm btn-primary" download>Download PDF</a>
                        <?php else: ?>
                        <span class="text-muted">File not found</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="no-data">No reports generated yet.</p>
        <?php endif; ?>
    </div>

    <script>
        function generateNewReport() {
            // TODO: Show modal to select run and report type
            alert('Report generation feature - coming soon');
        }
    </script>
</body>
</html>
