<header class="main-header">
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="/">SGEO Analytics</a>
        </div>
        <ul class="navbar-menu">
            <li><a href="/">Панель</a></li>
            <li><a href="/topics">Топики</a></li>
            <li><a href="/sources">Источники</a></li>
            <li><a href="/reports">Отчеты</a></li>
        </ul>
        <div class="navbar-user">
            <?php session_start_safe(); ?>
            <span>Добро пожаловать, <?= sanitize($_SESSION['username'] ?? 'Пользователь') ?></span>
            <a href="/logout" class="btn btn-sm">Выход</a>
        </div>
    </nav>
</header>
