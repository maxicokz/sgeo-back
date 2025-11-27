<?php

return [
    'name' => $_ENV['APP_NAME'] ?? 'SGEO Analytics Dashboard',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',

    'openrouter' => [
        'api_key' => $_ENV['OPENROUTER_API_KEY'] ?? '',
        'api_url' => $_ENV['OPENROUTER_API_URL'] ?? 'https://openrouter.ai/api/v1',
    ],

    'models' => [
        'gpt4' => $_ENV['MODEL_GPT4'] ?? 'openai/gpt-4-turbo-preview',
        'gpt35' => $_ENV['MODEL_GPT35'] ?? 'openai/gpt-3.5-turbo',
        'claude' => $_ENV['MODEL_CLAUDE'] ?? 'anthropic/claude-3-opus',
        'gemini' => $_ENV['MODEL_GEMINI'] ?? 'google/gemini-pro',
        'perplexity' => $_ENV['MODEL_PERPLEXITY'] ?? 'perplexity/pplx-70b-online',
        'max_tokens' => (int)($_ENV['MODEL_MAX_TOKENS'] ?? 4096),
    ],

    'session' => [
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 7200),
        'secure' => filter_var($_ENV['SESSION_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ],

    'paths' => [
        'reports' => $_ENV['REPORTS_PATH'] ?? './reports',
        'temp' => $_ENV['TEMP_PATH'] ?? './temp',
        'logs' => './logs',
    ],

    'monitoring' => [
        'enabled' => filter_var($_ENV['MONITORING_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'log_level' => $_ENV['LOG_LEVEL'] ?? 'info',
    ],
];
