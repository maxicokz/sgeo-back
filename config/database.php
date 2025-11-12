<?php

return [
    // Database type: 'pgsql' or 'mysql'
    'driver' => $_ENV['DB_DRIVER'] ?? 'mysql',

    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'socket' => $_ENV['DB_SOCKET'] ?? '',
    'database' => $_ENV['DB_NAME'] ?? 'sgeo_analytics',
    'username' => $_ENV['DB_USER'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
