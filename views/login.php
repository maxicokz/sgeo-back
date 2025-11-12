<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SGEO Analytics</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <h1>SGEO Analytics Dashboard</h1>
            <p class="subtitle">Kazakhstan AI Representation Monitoring</p>

            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php
                switch ($_GET['error']) {
                    case 'empty_fields':
                        echo 'Please fill in all fields';
                        break;
                    case 'invalid_credentials':
                        echo 'Invalid username or password';
                        break;
                    default:
                        echo 'An error occurred';
                }
                ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="/login">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <p class="help-text">Default credentials: admin / admin123</p>
        </div>
    </div>
</body>
</html>
