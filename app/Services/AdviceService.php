<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;
use Nette\Caching\Storage;

/**
 * Service pro získávání náhodných rad
 */
final class AdviceService
{
    private Cache $cache;

    private const URL_ADVICE_API = 'https://api.adviceslip.com/advice';
    private const CACHE_EXPIRATION = '1 hour';

    public function __construct(
        private readonly HttpClientService $httpClient,
        Storage $storage
    ) {
        $this->cache = new Cache($storage, self::class);
    }

    /**
     * Získá náhodnou radu
     *
     * @return array{data: array<string, mixed>}
     */
    public function getRandomAdvice(): array
    {
        try {
            $data = $this->httpClient->getJson(self::URL_ADVICE_API);

            if (!isset($data['slip']['advice'])) {
                throw new \RuntimeException('Nepodařilo se získat radu');
            }

            $slip = $data['slip'];

            $result = ['data' => [
                'id' => $slip['id'] ?? null,
                'advice' => $slip['advice'],
            ]];

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat advice: {$e->getMessage()}", 0, $e);
        }
    }
}
