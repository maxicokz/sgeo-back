#!/usr/bin/env php
<?php
/**
 * Batch Data Collection Script for Plesk Cron Jobs
 * Скрипт для пакетного сбора данных через Cron (Plesk-совместимый)
 *
 * Использование в Plesk Cron:
 *   Команда: /usr/bin/php /var/www/vhosts/maxico.kz/httpdocs/cli/collect-batch-cron.php
 *   Расписание: Каждые 15 минут
 *
 * Или локально:
 *   php cli/collect-batch-cron.php [--batch-size=2] [--run-id=123]
 *
 * Особенности:
 *   - Обрабатывает только 2 топика за раз (по умолчанию)
 *   - Автоматически продолжает предыдущий незавершенный запуск
 *   - Безопасен для shared hosting (не превысит лимиты времени)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Connection;
use App\Services\DataCollectionService;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

Connection::init(require __DIR__ . '/../config/database.php');

// Parse command line options
$options = getopt('', ['batch-size:', 'run-id:', 'help']);

if (isset($options['help'])) {
    echo <<<HELP
SGEO Analytics - Пакетный сбор данных для Plesk Cron

Использование:
  php cli/collect-batch-cron.php [опции]

Опции:
  --batch-size=N    Количество топиков за раз (default: 2, max: 5)
  --run-id=N        ID существующего запуска для продолжения
  --help            Показать эту справку

Пример настройки Cron в Plesk:
  Команда: /usr/bin/php /var/www/vhosts/maxico.kz/httpdocs/cli/collect-batch-cron.php
  Расписание: */15 * * * * (каждые 15 минут)

Скрипт автоматически:
  - Создает новый запуск если нет активных
  - Продолжает существующий незавершенный запуск
  - Собирает данные по частям (избегает таймаутов)
  - Завершается когда все топики обработаны

HELP;
    exit(0);
}

function logOutput(string $message): void
{
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message" . PHP_EOL;
}

try {
    // Get batch size
    $batchSize = isset($options['batch-size']) ? (int)$options['batch-size'] : 2;
    if ($batchSize < 1) $batchSize = 1;
    if ($batchSize > 5) $batchSize = 5; // Safety limit for shared hosting

    // Get run ID if specified
    $runId = isset($options['run-id']) ? (int)$options['run-id'] : null;

    // If no run ID specified, check for incomplete runs
    if ($runId === null) {
        $db = Connection::getInstance();
        $incompleteRuns = $db->query(
            "SELECT id FROM monitoring_runs WHERE status = 'in_progress' ORDER BY created_at DESC LIMIT 1"
        );

        if (!empty($incompleteRuns)) {
            $runId = $incompleteRuns[0]['id'];
            logOutput("📋 Найден незавершенный запуск #$runId, продолжаем...");
        }
    }

    logOutput("🚀 Запуск пакетного сбора данных (размер пакета: $batchSize)");

    $collectionService = new DataCollectionService();
    $result = $collectionService->runBatchCollection($batchSize, $runId);

    $progress = round(($result['processed'] / $result['total']) * 100, 1);

    logOutput("📊 Прогресс: {$result['processed']}/{$result['total']} топиков ($progress%)");

    if ($result['completed']) {
        logOutput("✅ Сбор данных завершен! Run ID: #{$result['run_id']}");
        exit(0);
    } else {
        logOutput("⏳ Обработано {$result['processed']} топиков, осталось: {$result['remaining']}");
        logOutput("💡 Запустите скрипт снова чтобы продолжить или настройте Cron");
        exit(0);
    }

} catch (\Exception $e) {
    logOutput("❌ ОШИБКА: " . $e->getMessage());
    error_log("Batch cron collection error: " . $e->getMessage());
    exit(1);
}
