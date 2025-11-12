#!/usr/bin/env php
<?php

/**
 * CLI Script: Data Collection
 * Usage: php cli/collect.php [topic_ids] [llm_models]
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Connection;
use App\Services\DataCollectionService;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize database connection
Connection::init(require __DIR__ . '/../config/database.php');

echo "SGEO Analytics - Data Collection\n";
echo "=================================\n\n";

// Parse arguments
$topicIds = [];
$llmModels = [];

if ($argc > 1) {
    $topicIds = array_map('intval', explode(',', $argv[1]));
    echo "Collecting for specific topics: " . implode(', ', $topicIds) . "\n";
}

if ($argc > 2) {
    $llmModels = explode(',', $argv[2]);
    echo "Using specific LLM models: " . implode(', ', $llmModels) . "\n";
}

try {
    $service = new DataCollectionService();

    echo "\nStarting data collection...\n";
    $runId = $service->runCollection($topicIds, $llmModels);

    echo "\n✅ Data collection completed successfully!\n";
    echo "Run ID: $runId\n";

    exit(0);
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
