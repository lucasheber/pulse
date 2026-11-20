<?php

declare(strict_types=1);

use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Log\LogLevel;

use function Hyperf\Support\env;

return [
    'app_name' => env('APP_NAME', 'bff-gateway'),
    'app_env' => env('APP_ENV', 'dev'),
    'scan_cacheable' => env('SCAN_CACHEABLE', false),
    'services' => [
        'finance_url' => rtrim((string) env('FINANCE_SERVICE_URL', 'http://finance-service:9501'), '/'),
        'planner_url' => rtrim((string) env('PLANNER_SERVICE_URL', 'http://planner-service:9501'), '/'),
    ],
    'cors' => [
        'allowed_origin' => (string) env('CORS_ALLOWED_ORIGIN', 'http://localhost:5173'),
    ],
    'jwt' => [
        'secret' => (string) env('JWT_SECRET', ''),
        'ttl_seconds' => 86400,
    ],
    StdoutLoggerInterface::class => [
        'log_level' => [
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::EMERGENCY,
            LogLevel::ERROR,
            LogLevel::INFO,
            LogLevel::NOTICE,
            LogLevel::WARNING,
        ],
    ],
];
