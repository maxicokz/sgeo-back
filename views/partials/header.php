<header class="main-header">
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="/">SGEO Analytics</a>
        </div>
        <ul class="navbar-menu">
            <li><a href="/">Dashboard</a></li>
            <li><a href="/topics">Topics</a></li>
            <li><a href="/sources">Sources</a></li>
            <li><a href="/reports">Reports</a></li>
        </ul>
        <div class="navbar-user">
            <?php session_start_safe(); ?>
            <span>Welcome, <?= sanitize($_SESSION['username'] ?? 'User') ?></span>
            <a href="/logout" class="btn btn-sm">Logout</a>
        </div>
    </nav>
</header>
