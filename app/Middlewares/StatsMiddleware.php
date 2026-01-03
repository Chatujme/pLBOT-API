<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Services\StatsService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware pro logování API requestů do statistik
 */
final class StatsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly StatsService $statsService
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $startTime = microtime(true);
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        // Get client info
        $ipAddress = $this->getClientIp($request);
        $userAgent = $request->getHeaderLine('User-Agent') ?: null;

        // Process request
        $response = $handler->handle($request);

        // Calculate response time in milliseconds
        $responseTime = (microtime(true) - $startTime) * 1000;

        // Log request asynchronously (non-blocking)
        try {
            $this->statsService->logRequest(
                $path,
                $method,
                $response->getStatusCode(),
                $responseTime,
                $ipAddress,
                $userAgent
            );
        } catch (\Exception $e) {
            // Ignore stats errors - they shouldn't affect API responses
        }

        return $response;
    }

    private function getClientIp(ServerRequestInterface $request): ?string
    {
        // Check for forwarded headers (proxy/load balancer)
        $headers = [
            'X-Forwarded-For',
            'X-Real-IP',
            'CF-Connecting-IP', // Cloudflare
            'True-Client-IP',
        ];

        foreach ($headers as $header) {
            $value = $request->getHeaderLine($header);
            if (!empty($value)) {
                // X-Forwarded-For can contain multiple IPs, get the first one
                $ips = explode(',', $value);
                return trim($ips[0]);
            }
        }

        // Fall back to server params
        $serverParams = $request->getServerParams();
        return $serverParams['REMOTE_ADDR'] ?? null;
    }
}
