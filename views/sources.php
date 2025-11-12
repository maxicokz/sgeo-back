<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sources - SGEO Analytics</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/partials/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Source Analysis</h1>
            <div class="page-actions">
                <label class="filter-checkbox">
                    <input type="checkbox" id="officialFilter" <?= $official_only ? 'checked' : '' ?> onchange="toggleOfficialFilter()">
                    Show Official Sources Only
                </label>
            </div>
        </div>

        <?php if (!empty($sources)): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Domain</th>
                    <th>Type</th>
                    <th>Official</th>
                    <th>E-E-A-T Score</th>
                    <th>Health Index</th>
                    <th>Citations</th>
                    <th>Optimization Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sources as $source): ?>
                <tr>
                    <td>
                        <strong><?= sanitize($source['domain']) ?></strong>
                        <?php if ($source['title']): ?>
                        <br><small><?= sanitize($source['title']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= sanitize($source['source_type']) ?></td>
                    <td><?= $source['is_official'] ? '✓ Official' : '—' ?></td>
                    <td>
                        <div class="score-display">
                            <strong><?= round($source['overall_eeat_score'] ?? 0, 1) ?></strong> / 100
                            <?php
                            $eeatScore = $source['overall_eeat_score'] ?? 0;
                            if ($eeatScore >= 80) echo '<span class="badge badge-success">🟢</span>';
                            elseif ($eeatScore >= 50) echo '<span class="badge badge-warning">🟡</span>';
                            else echo '<span class="badge badge-danger">🔴</span>';
                            ?>
                        </div>
                    </td>
                    <td><?= round($source['health_index'] ?? 0, 1) ?> / 100</td>
                    <td><?= $source['citation_count'] ?></td>
                    <td><span class="status-badge status-<?= $source['optimization_status'] ?>"><?= ucfirst($source['optimization_status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="no-data">No sources available.</p>
        <?php endif; ?>
    </div>

    <script>
        function toggleOfficialFilter() {
            const checked = document.getElementById('officialFilter').checked;
            window.location.href = '/sources' + (checked ? '?official=1' : '');
        }
    </script>
</body>
</html>
