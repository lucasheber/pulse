<?php

declare(strict_types=1);

namespace HyperfTest\Unit\Exception;

use App\Exception\Handler\AppExceptionHandler;
use App\Middleware\CorrelationIdMiddleware;
use Hyperf\Context\Context;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\HttpMessage\Server\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AppExceptionHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        Context::destroy(CorrelationIdMiddleware::CONTEXT_KEY);
        parent::tearDown();
    }

    public function testLogsErrorAndReturnsJson500WithCorrelationId(): void
    {
        Context::set(CorrelationIdMiddleware::CONTEXT_KEY, 'corr-test-001');

        $logger = $this->createMock(StdoutLoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(self::stringContains('[corr-test-001] Boom!'));

        $handler = new AppExceptionHandler($logger);
        $response = $handler->handle(new RuntimeException('Boom!'), new Response());

        self::assertTrue($handler->isValid(new RuntimeException('Boom!')));
        self::assertSame(500, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));

        /** @var array<string, string> $body */
        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('internal_server_error', $body['error']);
        self::assertSame('corr-test-001', $body['correlation_id']);
    }
}
