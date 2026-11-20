<?php

declare(strict_types=1);

namespace App\Middleware;

use Hyperf\Context\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;

class CorrelationIdMiddleware implements MiddlewareInterface
{
    public const HEADER = 'X-Correlation-ID';
    public const CONTEXT_KEY = 'correlation_id';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $correlationId = trim($request->getHeaderLine(self::HEADER));
        if ($correlationId === '') {
            $correlationId = Uuid::uuid4()->toString();
        }

        // Coroutine-safe storage: never store request state on singleton properties.
        Context::set(self::CONTEXT_KEY, $correlationId);

        $response = $handler->handle($request->withHeader(self::HEADER, $correlationId));

        return $response->withHeader(self::HEADER, $correlationId);
    }
}
