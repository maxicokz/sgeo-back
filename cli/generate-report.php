#!/usr/bin/env php
<?php

/**
 * CLI Script: Report Generation
 * Usage: php cli/generate-report.php [report_type] [run_id]
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Connection;
use App\Services\ReportService;
use App\Models\MonitoringRun;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize database connection
Connection::init(require __DIR__ . '/../config/database.php');

echo "SGEO Analytics - Report Generation\n";
echo "===================================\n\n";

// Parse arguments
$reportType = $argv[1] ?? 'weekly';
$runId = $argv[2] ?? null;

if (!$runId) {
    $latestRun = MonitoringRun::getLatest();
    if (!$latestRun) {
        echo "❌ No monitoring runs found. Please run data collection first.\n";
        exit(1);
    }
    $runId = $latestRun['id'];
    echo "Using latest monitoring run: #$runId\n";
}

echo "Report Type: $reportType\n";
echo "Run ID: $runId\n\n";

try {
    $service = new ReportService();

    echo "Generating report...\n";
    $filePath = $service->generatePDFReport($runId, $reportType);

    echo "\n✅ Report generated successfully!\n";
    echo "File: $filePath\n";

    exit(0);
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
