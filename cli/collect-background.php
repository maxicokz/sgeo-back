#!/usr/bin/env php
<?php
/**
 * Background Data Collection Script
 * Используйте этот скрипт для фонового сбора данных
 *
 * Использование:
 *   php cli/collect-background.php
 *   php cli/collect-background.php --topics=1,2,3
 *   nohup php cli/collect-background.php > /dev/null 2>&1 &
 */

// Загрузка autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Connection;
use App\Services\DataCollectionService;

// Загрузка переменных окружения
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Инициализация БД
Connection::init(require __DIR__ . '/../config/database.php');

// Функция для вывода с timestamp
function logOutput(string $message): void
{
    echo '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
}

// Парсинг аргументов командной строки
$options = getopt('', ['topics:', 'help']);

if (isset($options['help'])) {
    echo <<<HELP
SGEO Analytics - Фоновый сбор данных

Использование:
  php cli/collect-background.php [опции]

Опции:
  --topics=1,2,3    Собрать данные только для указанных топиков (по ID)
  --help            Показать эту справку

Примеры:
  php cli/collect-background.php
  php cli/collect-background.php --topics=1,2,3,4,5
  nohup php cli/collect-background.php > collection.log 2>&1 &

Для запуска в фоне (на сервере):
  nohup php cli/collect-background.php > /dev/null 2>&1 &

Проверить статус:
  ps aux | grep collect-background

HELP;
    exit(0);
}

try {
    logOutput("🚀 Запуск фонового сбора данных...");

    // Определяем топики для сбора
    $topicIds = [];
    if (isset($options['topics'])) {
        $topicIds = array_map('intval', explode(',', $options['topics']));
        logOutput("📋 Выбрано топиков: " . count($topicIds));
    } else {
        logOutput("📋 Сбор данных для всех топиков");
    }

    // Создаем сервис сбора данных
    $collectionService = new DataCollectionService();

    // Запускаем сбор
    logOutput("⏳ Начинается сбор данных...");
    $runId = $collectionService->runCollection($topicIds);

    logOutput("✅ Сбор данных завершен успешно!");
    logOutput("📊 ID запуска: #$runId");
    logOutput("🔗 Просмотр результатов: " . config('app.url') . "/");

} catch (\Exception $e) {
    logOutput("❌ ОШИБКА: " . $e->getMessage());
    logOutput("📍 Файл: " . $e->getFile() . ':' . $e->getLine());

    if (config('app.debug')) {
        logOutput("Stack trace:");
        logOutput($e->getTraceAsString());
    }

    exit(1);
}

logOutput("🏁 Работа завершена");
exit(0);
