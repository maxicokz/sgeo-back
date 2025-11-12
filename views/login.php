<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - SGEO Analytics</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <h1>Панель SGEO Analytics</h1>
            <p class="subtitle">Мониторинг представления Казахстана в AI системах</p>

            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php
                switch ($_GET['error']) {
                    case 'empty_fields':
                        echo 'Пожалуйста, заполните все поля';
                        break;
                    case 'invalid_credentials':
                        echo 'Неверное имя пользователя или пароль';
                        break;
                    default:
                        echo 'Произошла ошибка';
                }
                ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="/login">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div class="form-group">
                    <label for="username">Имя пользователя или Email</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Войти</button>
            </form>

            <p class="help-text">Данные по умолчанию: admin / admin123</p>
        </div>
    </div>
</body>
</html>
