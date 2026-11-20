<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use App\Middleware\CorrelationIdMiddleware;
use Hyperf\Context\Context;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AppExceptionHandler extends ExceptionHandler
{
    public function __construct(protected StdoutLoggerInterface $logger)
    {
    }

    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $correlationId = (string) Context::get(CorrelationIdMiddleware::CONTEXT_KEY, '');

        $this->logger->error(sprintf(
            '[%s] %s in %s:%d',
            $correlationId,
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine()
        ));

        $payload = json_encode([
            'error' => 'internal_server_error',
            'message' => 'Unexpected internal error.',
            'correlation_id' => $correlationId,
        ], JSON_THROW_ON_ERROR);

        $this->stopPropagation();

        return $response
            ->withStatus(500)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new SwooleStream($payload));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
