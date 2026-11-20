<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\CorrelationIdMiddleware;
use DateTimeImmutable;
use DateTimeZone;
use Hyperf\Context\Context;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Throwable;

use function Hyperf\Config\config;

#[Controller]
class HealthController
{
    #[GetMapping(path: '/health')]
    public function index(): array
    {
        $dbStatus = 'ok';
        try {
            Db::select('SELECT 1');
        } catch (Throwable) {
            $dbStatus = 'error';
        }

        return [
            'service' => (string) config('app_name', 'bff-gateway'),
            'status' => $dbStatus === 'ok' ? 'ok' : 'degraded',
            'dependencies' => [
                'mysql' => $dbStatus,
            ],
            'correlation_id' => (string) Context::get(CorrelationIdMiddleware::CONTEXT_KEY, ''),
            'timestamp' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM),
        ];
    }
}
