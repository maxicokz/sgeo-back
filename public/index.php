<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Connection;
use App\Controllers\DashboardController;
use App\Controllers\ApiController;
use App\Controllers\AuthController;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize database connection
Connection::init(require __DIR__ . '/../config/database.php');

// Simple router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Authentication controller
$authController = new AuthController();

// Public routes (no auth required)
$publicRoutes = ['/login', '/api/health'];

if (!in_array($uri, $publicRoutes)) {
    $authController->requireAuth();
}

// Route handling
try {
    switch ($uri) {
        // Auth routes
        case '/login':
            if ($method === 'GET') {
                $authController->showLogin();
            } elseif ($method === 'POST') {
                $authController->login();
            }
            break;

        case '/logout':
            $authController->logout();
            break;

        // Dashboard routes
        case '/':
        case '/dashboard':
            $controller = new DashboardController();
            $controller->index();
            break;

        case '/topics':
            $controller = new DashboardController();
            $controller->topics();
            break;

        case '/topic':
            $controller = new DashboardController();
            $controller->topicDetails();
            break;

        case '/sources':
            $controller = new DashboardController();
            $controller->sources();
            break;

        case '/reports':
            $controller = new DashboardController();
            $controller->reports();
            break;

        // API routes
        case '/api/collection/start':
            $controller = new ApiController();
            $controller->startCollection();
            break;

        case '/api/run/status':
            $controller = new ApiController();
            $controller->getRunStatus();
            break;

        case '/api/report/generate':
            $controller = new ApiController();
            $controller->generateReport();
            break;

        case '/api/dashboard':
            $controller = new ApiController();
            $controller->getDashboardData();
            break;

        case '/api/topics':
            $controller = new ApiController();
            $controller->getTopics();
            break;

        case '/api/topic/performance':
            $controller = new ApiController();
            $controller->getTopicPerformance();
            break;

        case '/api/sources':
            $controller = new ApiController();
            $controller->getSources();
            break;

        case '/api/source/metrics':
            $controller = new ApiController();
            $controller->updateSourceMetrics();
            break;

        case '/api/health':
            json_response(['status' => 'ok', 'timestamp' => time()]);
            break;

        default:
            http_response_code(404);
            echo '404 - Page Not Found';
            break;
    }
} catch (\Exception $e) {
    if (config('app.debug')) {
        dd($e);
    } else {
        log_message('Application error: ' . $e->getMessage(), 'error');
        http_response_code(500);
        echo '500 - Internal Server Error';
    }
}
