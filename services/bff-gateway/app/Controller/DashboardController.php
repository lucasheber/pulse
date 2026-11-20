<?php

declare(strict_types=1);

namespace App\Controller;

use App\Middleware\CorrelationIdMiddleware;
use DateTimeImmutable;
use DateTimeZone;
use Hyperf\Context\Context;
use Hyperf\Coroutine\Parallel;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Throwable;

use function Hyperf\Config\config;

#[Controller(prefix: '/api/dashboard')]
class DashboardController
{
    public function __construct(protected ClientFactory $clientFactory)
    {
    }

    /**
     * Starter API Composition endpoint demonstrating Swoole Parallel coroutines
     * with Correlation-ID propagation and graceful degradation.
     */
    #[GetMapping(path: 'today')]
    public function today(): array
    {
        // Capture correlation_id before spawning child coroutines (each coroutine has its own Context).
        $correlationId = (string) Context::get(CorrelationIdMiddleware::CONTEXT_KEY, '');
        $financeUrl = (string) config('services.finance_url');
        $plannerUrl = (string) config('services.planner_url');

        $parallel = new Parallel(2);

        $parallel->add(fn () => $this->fetchServiceHealth($financeUrl, $correlationId), 'finance');
        $parallel->add(fn () => $this->fetchServiceHealth($plannerUrl, $correlationId), 'planner');

        $results = $parallel->wait();

        return [
            'date' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d'),
            'correlation_id' => $correlationId,
            'services' => [
                'bff' => ['status' => 'ok'],
                'finance' => $results['finance'],
                'planner' => $results['planner'],
            ],
        ];
    }

    private function fetchServiceHealth(string $baseUrl, string $correlationId): array
    {
        try {
            $client = $this->clientFactory->create([
                'base_uri' => $baseUrl,
                'timeout' => 2.5,
            ]);

            $response = $client->get('/health', [
                'headers' => [
                    CorrelationIdMiddleware::HEADER => $correlationId,
                    'Accept' => 'application/json',
                ],
            ]);

            return [
                'available' => true,
                'data' => json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR),
            ];
        } catch (Throwable $e) {
            return [
                'available' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
