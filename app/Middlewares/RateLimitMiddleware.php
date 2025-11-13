<?php

declare(strict_types=1);

namespace App\Middlewares;

use Nette\Caching\Cache;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Contributte\Psr7\Psr7ResponseFactory;

/**
 * Rate limiting middleware - omezuje počet požadavků na API
 *
 * Limity:
 * - 100 požadavků za minutu na IP adresu
 * - Vrací HTTP 429 při překročení limitu
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    private const RATE_LIMIT = 100; // počet požadavků
    private const RATE_WINDOW = 60; // časové okno v sekundách (1 minuta)

    public function __construct(
        private readonly Cache $cache,
        private readonly Psr7ResponseFactory $responseFactory
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Získání IP adresy klienta
        $clientIp = $this->getClientIp($request);

        // Klíč pro cache
        $cacheKey = 'rate_limit_' . md5($clientIp);

        // Načtení aktuálního stavu z cache
        $requestData = $this->cache->load($cacheKey);

        if ($requestData === null) {
            // První požadavek v tomto časovém okně
            $requestData = [
                'count' => 1,
                'reset_time' => time() + self::RATE_WINDOW,
            ];

            $this->cache->save($cacheKey, $requestData, [
                Cache::EXPIRE => self::RATE_WINDOW,
            ]);
        } else {
            // Kontrola, zda jsme ještě v časovém okně
            if (time() > $requestData['reset_time']) {
                // Časové okno vypršelo, resetujeme počítadlo
                $requestData = [
                    'count' => 1,
                    'reset_time' => time() + self::RATE_WINDOW,
                ];
            } else {
                // Jsme stále v časovém okně, inkrementujeme počítadlo
                $requestData['count']++;

                // Kontrola překročení limitu
                if ($requestData['count'] > self::RATE_LIMIT) {
                    return $this->createRateLimitResponse($requestData['reset_time']);
                }
            }

            $this->cache->save($cacheKey, $requestData, [
                Cache::EXPIRE => self::RATE_WINDOW,
            ]);
        }

        // Přidání hlaviček s informacemi o rate limitu
        $response = $handler->handle($request);
        $response = $response
            ->withHeader('X-RateLimit-Limit', (string) self::RATE_LIMIT)
            ->withHeader('X-RateLimit-Remaining', (string) max(0, self::RATE_LIMIT - $requestData['count']))
            ->withHeader('X-RateLimit-Reset', (string) $requestData['reset_time']);

        return $response;
    }

    /**
     * Získá IP adresu klienta (včetně podpory pro proxy)
     */
    private function getClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();

        // Kontrola hlaviček pro proxy
        if (isset($serverParams['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $serverParams['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        if (isset($serverParams['HTTP_X_REAL_IP'])) {
            return $serverParams['HTTP_X_REAL_IP'];
        }

        return $serverParams['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Vytvoří odpověď s HTTP 429 Too Many Requests
     */
    private function createRateLimitResponse(int $resetTime): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();

        $body = json_encode([
            'error' => [
                'message' => 'Rate limit exceeded. Please try again later.',
                'code' => 429,
                'reset_time' => $resetTime,
                'reset_in_seconds' => max(0, $resetTime - time()),
            ],
        ], JSON_THROW_ON_ERROR);

        $response->getBody()->write($body);

        return $response
            ->withStatus(429)
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->withHeader('X-RateLimit-Limit', (string) self::RATE_LIMIT)
            ->withHeader('X-RateLimit-Remaining', '0')
            ->withHeader('X-RateLimit-Reset', (string) $resetTime)
            ->withHeader('Retry-After', (string) max(0, $resetTime - time()));
    }
}
