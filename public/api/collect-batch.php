<?php
/**
 * AJAX endpoint for batch data collection (Plesk-compatible)
 * Собирает данные по частям, чтобы избежать таймаутов
 *
 * Usage:
 *   POST /api/collect-batch.php
 *   Parameters:
 *     - run_id (optional): ID существующего запуска
 *     - batch_size (optional): Количество топиков за раз (default: 2)
 *     - csrf_token: CSRF токен
 *
 * Returns JSON:
 *   {
 *     "success": true,
 *     "run_id": 123,
 *     "completed": false,
 *     "processed": 4,
 *     "total": 20,
 *     "remaining": 16,
 *     "progress": 20,
 *     "message": "Обработано 4 из 20 топиков"
 *   }
 */

// Fix session path for Plesk
ini_set('session.save_path', '/tmp');

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Database\Connection;
use App\Services\DataCollectionService;

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Initialize database
Connection::init(require __DIR__ . '/../../config/database.php');

// Start session for auth check
session_start();

// Set JSON header
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Требуется авторизация',
    ]);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Метод не разрешен',
    ]);
    exit;
}

// Verify CSRF token
$csrfToken = $_POST['csrf_token'] ?? '';
if ($csrfToken !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Неверный CSRF токен',
    ]);
    exit;
}

try {
    // Get parameters
    $runId = isset($_POST['run_id']) && $_POST['run_id'] !== '' ? (int)$_POST['run_id'] : null;
    $batchSize = isset($_POST['batch_size']) ? (int)$_POST['batch_size'] : 2;

    // Validate batch size
    if ($batchSize < 1) $batchSize = 1;
    if ($batchSize > 5) $batchSize = 5; // Max 5 topics per batch for safety

    // Run batch collection
    $collectionService = new DataCollectionService();
    $result = $collectionService->runBatchCollection($batchSize, $runId);

    // Calculate progress percentage
    $progress = $result['total'] > 0
        ? round(($result['processed'] / $result['total']) * 100)
        : 0;

    // Build message
    if ($result['completed']) {
        $message = "✅ Сбор данных завершен! Обработано {$result['processed']} топиков.";
    } else {
        $message = "⏳ Обработано {$result['processed']} из {$result['total']} топиков. Осталось: {$result['remaining']}.";
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'run_id' => $result['run_id'],
        'completed' => $result['completed'],
        'processed' => $result['processed'],
        'total' => $result['total'],
        'remaining' => $result['remaining'],
        'progress' => $progress,
        'message' => $message,
    ]);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка сбора данных: ' . $e->getMessage(),
    ]);

    error_log("Batch collection error: " . $e->getMessage());
}
