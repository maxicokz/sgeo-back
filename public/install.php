<?php
/**
 * SGEO Analytics Web Installer
 * For shared hosting without shell access
 */

// Disable error display in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

$step = $_GET['step'] ?? 1;
$error = null;
$success = null;

// Check if already installed
if (file_exists(__DIR__ . '/.installed') && $step == 1) {
    header('Location: index.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 2:
            // Save configuration
            $config = [
                'DB_HOST' => $_POST['db_host'] ?? '',
                'DB_PORT' => $_POST['db_port'] ?? '5432',
                'DB_NAME' => $_POST['db_name'] ?? '',
                'DB_USER' => $_POST['db_user'] ?? '',
                'DB_PASSWORD' => $_POST['db_password'] ?? '',
                'OPENROUTER_API_KEY' => $_POST['openrouter_key'] ?? '',
                'APP_URL' => $_POST['app_url'] ?? '',
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
            ];

            // Test database connection
            try {
                $dsn = sprintf(
                    'pgsql:host=%s;port=%s;dbname=%s',
                    $config['DB_HOST'],
                    $config['DB_PORT'],
                    $config['DB_NAME']
                );
                $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASSWORD']);

                // Save .env file
                $envContent = '';
                foreach ($config as $key => $value) {
                    $envContent .= "$key=$value\n";
                }

                if (file_put_contents(__DIR__ . '/.env', $envContent)) {
                    $success = 'Configuration saved successfully!';
                    $step = 3;
                } else {
                    $error = 'Failed to write .env file. Check directory permissions.';
                }
            } catch (PDOException $e) {
                $error = 'Database connection failed: ' . $e->getMessage();
            }
            break;

        case 3:
            // Import database schema
            if (!file_exists(__DIR__ . '/.env')) {
                $error = 'Configuration not found. Please complete step 2 first.';
                break;
            }

            // Load configuration
            $envFile = file_get_contents(__DIR__ . '/.env');
            $envLines = explode("\n", $envFile);
            $config = [];
            foreach ($envLines as $line) {
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $config[$key] = trim($value);
                }
            }

            try {
                $dsn = sprintf(
                    'pgsql:host=%s;port=%s;dbname=%s',
                    $config['DB_HOST'],
                    $config['DB_PORT'],
                    $config['DB_NAME']
                );
                $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASSWORD']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Read and execute SQL file
                $sql = file_get_contents(__DIR__ . '/../database/schema.sql');

                // Execute SQL (split by semicolon)
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $pdo->exec($statement);
                    }
                }

                $success = 'Database schema imported successfully!';
                $step = 4;
            } catch (PDOException $e) {
                $error = 'Failed to import database: ' . $e->getMessage();
            }
            break;

        case 4:
            // Create directories and set permissions
            $dirs = ['logs', 'reports', 'temp', 'cache'];
            $created = [];
            $failed = [];

            foreach ($dirs as $dir) {
                $path = __DIR__ . '/../' . $dir;
                if (!is_dir($path)) {
                    if (mkdir($path, 0755, true)) {
                        $created[] = $dir;
                    } else {
                        $failed[] = $dir;
                    }
                } else {
                    $created[] = $dir . ' (already exists)';
                }
            }

            if (empty($failed)) {
                // Mark as installed
                file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
                $success = 'Installation completed successfully!';
                $step = 5;
            } else {
                $error = 'Failed to create directories: ' . implode(', ', $failed);
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGEO Analytics - Web Installer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #3498db, #2c3e50);
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
            max-width: 600px;
            width: 100%;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #7f8c8d;
            margin-bottom: 30px;
        }
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            background: #ecf0f1;
            color: #7f8c8d;
            border-radius: 4px;
            margin: 0 5px;
            font-size: 12px;
        }
        .step.active {
            background: #3498db;
            color: white;
            font-weight: bold;
        }
        .step.completed {
            background: #27ae60;
            color: white;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        input[type="text"],
        input[type="password"],
        input[type="url"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .help-text {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
        }
        .btn:hover {
            background: #2980b9;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .info-box {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        .success-box {
            text-align: center;
            padding: 40px 20px;
        }
        .success-icon {
            font-size: 64px;
            color: #27ae60;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="installer">
        <h1>SGEO Analytics</h1>
        <p class="subtitle">Web Installer for Shared Hosting</p>

        <div class="steps">
            <div class="step <?= $step >= 1 ? 'completed' : '' ?>">1. Welcome</div>
            <div class="step <?= $step == 2 ? 'active' : ($step > 2 ? 'completed' : '') ?>">2. Config</div>
            <div class="step <?= $step == 3 ? 'active' : ($step > 3 ? 'completed' : '') ?>">3. Database</div>
            <div class="step <?= $step == 4 ? 'active' : ($step > 4 ? 'completed' : '') ?>">4. Setup</div>
            <div class="step <?= $step == 5 ? 'active' : '' ?>">5. Complete</div>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <strong>Error:</strong> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>Success!</strong> <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <div class="info-box">
                <h3>Welcome to SGEO Analytics Installer</h3>
                <p>This wizard will help you install the application on shared hosting without shell access.</p>
                <br>
                <p><strong>Before you begin, make sure you have:</strong></p>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>PostgreSQL database created</li>
                    <li>Database credentials (host, username, password)</li>
                    <li>OpenRouter API key (get it from openrouter.ai)</li>
                    <li>All files uploaded via FTP</li>
                    <li>Composer dependencies installed (vendor/ folder)</li>
                </ul>
            </div>
            <form method="GET">
                <input type="hidden" name="step" value="2">
                <button type="submit" class="btn">Start Installation →</button>
            </form>

        <?php elseif ($step == 2): ?>
            <h2>Step 2: Configuration</h2>
            <p style="margin-bottom: 20px;">Enter your database and application settings:</p>

            <form method="POST">
                <div class="form-group">
                    <label>Database Host</label>
                    <input type="text" name="db_host" value="localhost" required>
                    <p class="help-text">Usually "localhost" or provided by your hosting</p>
                </div>

                <div class="form-group">
                    <label>Database Port</label>
                    <input type="text" name="db_port" value="5432" required>
                    <p class="help-text">Default PostgreSQL port is 5432</p>
                </div>

                <div class="form-group">
                    <label>Database Name</label>
                    <input type="text" name="db_name" required>
                </div>

                <div class="form-group">
                    <label>Database Username</label>
                    <input type="text" name="db_user" required>
                </div>

                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" name="db_password" required>
                </div>

                <div class="form-group">
                    <label>OpenRouter API Key</label>
                    <input type="text" name="openrouter_key" required>
                    <p class="help-text">Get your API key from <a href="https://openrouter.ai" target="_blank">openrouter.ai</a></p>
                </div>

                <div class="form-group">
                    <label>Application URL</label>
                    <input type="url" name="app_url" value="<?= 'http://' . $_SERVER['HTTP_HOST'] ?>" required>
                    <p class="help-text">Full URL to your application</p>
                </div>

                <button type="submit" class="btn">Test & Save Configuration →</button>
            </form>

        <?php elseif ($step == 3): ?>
            <h2>Step 3: Database Setup</h2>
            <p style="margin-bottom: 20px;">Click the button below to import the database schema:</p>

            <div class="info-box">
                <strong>Note:</strong> This will create all necessary tables, views, and insert default data including the admin user.
            </div>

            <form method="POST">
                <button type="submit" class="btn">Import Database Schema →</button>
            </form>

        <?php elseif ($step == 4): ?>
            <h2>Step 4: Finalize Setup</h2>
            <p style="margin-bottom: 20px;">Create required directories and complete installation:</p>

            <form method="POST">
                <button type="submit" class="btn">Complete Installation →</button>
            </form>

        <?php elseif ($step == 5): ?>
            <div class="success-box">
                <div class="success-icon">✅</div>
                <h2>Installation Complete!</h2>
                <p style="margin: 20px 0;">SGEO Analytics has been successfully installed.</p>

                <div class="info-box" style="text-align: left;">
                    <strong>Default Login Credentials:</strong><br>
                    Username: <code>admin</code><br>
                    Password: <code>admin123</code><br>
                    <br>
                    <strong style="color: #e74c3c;">⚠️ IMPORTANT: Change the password immediately after login!</strong>
                </div>

                <a href="index.php" class="btn">Go to Dashboard →</a>

                <p style="margin-top: 20px; font-size: 12px; color: #7f8c8d;">
                    You can delete the install.php file for security.
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
