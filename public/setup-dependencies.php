<?php
/**
 * SGEO Analytics - Composer Dependencies Installer
 * For shared hosting without shell/SSH access
 */

set_time_limit(300); // 5 minutes
ini_set('memory_limit', '512M');

$step = $_GET['step'] ?? 1;
$error = null;
$success = null;

// Check if vendor already exists
if (is_dir(__DIR__ . '/../vendor') && $step == 1) {
    header('Location: install.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 2) {
    try {
        $vendorPath = __DIR__ . '/../vendor';

        // Check if composer.json exists
        if (!file_exists(__DIR__ . '/../composer.json')) {
            throw new Exception('composer.json not found');
        }

        // Try to run composer install if available
        $composerPhar = __DIR__ . '/../composer.phar';
        $phpBinary = PHP_BINARY ?: 'php';

        if (file_exists($composerPhar)) {
            // Try to run composer
            $output = [];
            $returnVar = 0;

            $cmd = escapeshellcmd($phpBinary) . ' ' . escapeshellarg($composerPhar) . ' install --no-dev --optimize-autoloader --working-dir=' . escapeshellarg(dirname($composerPhar)) . ' 2>&1';
            exec($cmd, $output, $returnVar);

            if ($returnVar === 0 && is_dir($vendorPath)) {
                $success = 'Dependencies installed successfully via Composer!';
                $step = 3;
            } else {
                throw new Exception('Composer install failed. Please download pre-packaged version.');
            }
        } else {
            throw new Exception('Composer not found. Please use Method 2 below.');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Dependencies - SGEO Analytics</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .installer {
            background: white;
            border-radius: 8px;
            padding: 40px;
            max-width: 700px;
            width: 100%;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1 { color: #2c3e50; margin-bottom: 10px; }
        h2 { color: #2c3e50; margin-bottom: 20px; font-size: 20px; }
        .subtitle { color: #7f8c8d; margin-bottom: 30px; }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .info-box {
            background: #fff3cd;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }
        .method-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 2px solid #dee2e6;
        }
        .method-box h3 {
            color: #495057;
            margin-bottom: 15px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            margin-top: 10px;
        }
        .btn:hover { background: #c0392b; }
        .btn-success {
            background: #27ae60;
        }
        .btn-success:hover {
            background: #229954;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            margin: 10px 0;
        }
        ol, ul {
            margin-left: 20px;
            margin-top: 10px;
        }
        li {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="installer">
        <h1>⚠️ Missing Dependencies</h1>
        <p class="subtitle">Composer vendor/ directory not found</p>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <strong>Error:</strong> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>Success!</strong> <?= htmlspecialchars($success) ?>
            <br><br>
            <a href="install.php" class="btn btn-success">Continue to Installation →</a>
        </div>
        <?php endif; ?>

        <?php if ($step == 1): ?>

        <div class="alert alert-warning">
            <strong>Dependencies Missing!</strong><br>
            The <code>vendor/</code> directory with Composer dependencies is not found. You need to install dependencies before proceeding.
        </div>

        <div class="method-box">
            <h3>📦 Method 1: Download Pre-packaged Version (Recommended)</h3>
            <p><strong>This is the easiest method for shared hosting.</strong></p>
            <ol>
                <li>Download the full package with dependencies:
                    <br><code>sgeo-analytics-with-vendor.zip</code>
                </li>
                <li>Extract it on your computer</li>
                <li>Upload <strong>ALL files including vendor/ folder</strong> via FTP</li>
                <li>Make sure the structure is:
                    <pre>/public_html/
├── public/
├── src/
├── vendor/  ← This folder must be present!
├── composer.json
└── ...</pre>
                </li>
                <li>Refresh this page</li>
            </ol>
        </div>

        <div class="method-box">
            <h3>🔧 Method 2: Install Locally with Composer</h3>
            <p>If you have Composer installed on your computer:</p>
            <ol>
                <li>Download all project files to your computer</li>
                <li>Open terminal/command prompt in the project folder</li>
                <li>Run:
                    <pre>composer install --no-dev --optimize-autoloader</pre>
                </li>
                <li>Upload everything (including the new <code>vendor/</code> folder) via FTP</li>
                <li>Refresh this page</li>
            </ol>

            <p><strong>Don't have Composer?</strong> Download it from <a href="https://getcomposer.org" target="_blank">getcomposer.org</a></p>
        </div>

        <div class="method-box">
            <h3>🌐 Method 3: Use GitHub Release</h3>
            <ol>
                <li>Go to: <a href="https://github.com/maxicokz/sgeo-back/releases" target="_blank">GitHub Releases</a></li>
                <li>Download the latest <code>sgeo-analytics-full.zip</code></li>
                <li>Extract and upload all files via FTP</li>
                <li>Refresh this page</li>
            </ol>
        </div>

        <?php if (function_exists('exec')): ?>
        <div class="method-box">
            <h3>⚡ Method 4: Auto-install (If Composer Available)</h3>
            <p>Try to install dependencies automatically via web interface:</p>
            <form method="POST">
                <input type="hidden" name="step" value="2">
                <button type="submit" class="btn">Try Auto-Install</button>
            </form>
            <p style="margin-top: 10px; font-size: 12px; color: #7f8c8d;">
                Note: This requires composer.phar file and exec() function enabled
            </p>
        </div>
        <?php endif; ?>

        <div class="info-box">
            <strong>💡 Need Help?</strong><br>
            Contact your hosting support or check the SHARED_HOSTING_INSTALL.md file for detailed instructions.
        </div>

        <a href="#" onclick="location.reload()" class="btn btn-success">↻ Check Again</a>

        <?php endif; ?>
    </div>
</body>
</html>
