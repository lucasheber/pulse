<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Middleware;

use App\Middleware\CorrelationIdMiddleware;
use Hyperf\Context\Context;
use Hyperf\HttpMessage\Server\Request;
use Hyperf\HttpMessage\Server\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CorrelationIdMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        Context::destroy(CorrelationIdMiddleware::CONTEXT_KEY);
        parent::tearDown();
    }

    public function testGeneratesCorrelationIdWhenHeaderIsMissing(): void
    {
        $middleware = new CorrelationIdMiddleware();
        $request = new Request('GET', '/health');

        $handler = new class () implements RequestHandlerInterface {
            public string $capturedRequestId = '';

            public string $capturedContextId = '';

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->capturedRequestId = $request->getHeaderLine(CorrelationIdMiddleware::HEADER);
                $this->capturedContextId = (string) Context::get(CorrelationIdMiddleware::CONTEXT_KEY, '');

                return new Response();
            }
        };

        $response = $middleware->process($request, $handler);
        $responseId = $response->getHeaderLine(CorrelationIdMiddleware::HEADER);

        self::assertNotSame('', $responseId);
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $responseId,
        );
        self::assertSame($responseId, $handler->capturedRequestId);
        self::assertSame($responseId, $handler->capturedContextId);
    }

    public function testPropagatesExistingCorrelationIdHeader(): void
    {
        $existingId = 'trace-abc-123';
        $middleware = new CorrelationIdMiddleware();
        $request = new Request('GET', '/health')
            ->withHeader(CorrelationIdMiddleware::HEADER, $existingId);

        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response()->withHeader(
                    'X-Seen',
                    $request->getHeaderLine(CorrelationIdMiddleware::HEADER),
                );
            }
        };

        $response = $middleware->process($request, $handler);

        self::assertSame($existingId, $response->getHeaderLine(CorrelationIdMiddleware::HEADER));
        self::assertSame($existingId, $response->getHeaderLine('X-Seen'));
        self::assertSame($existingId, Context::get(CorrelationIdMiddleware::CONTEXT_KEY));
    }
}
