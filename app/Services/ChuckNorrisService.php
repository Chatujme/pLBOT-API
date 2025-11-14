<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;
use Nette\Caching\Storage;

/**
 * Service pro získávání vtipů o Chucku Norrisovi
 */
final class ChuckNorrisService
{
    private Cache $cache;

    private const URL_CHUCK_API = 'https://api.chucknorris.io/jokes/random';
    private const URL_CATEGORIES = 'https://api.chucknorris.io/jokes/categories';
    private const CACHE_EXPIRATION = '1 hour';

    public function __construct(
        private readonly HttpClientService $httpClient,
        Storage $storage
    ) {
        $this->cache = new Cache($storage, self::class);
    }

    /**
     * Získá náhodný Chuck Norris vtip
     *
     * @param string|null $category Kategorie vtipu
     * @return array{data: array<string, mixed>}
     */
    public function getRandomJoke(?string $category = null): array
    {
        try {
            $url = self::URL_CHUCK_API;

            if ($category !== null) {
                $url .= '?category=' . urlencode($category);
            }

            $data = $this->httpClient->getJson($url);

            if (!isset($data['value'])) {
                throw new \RuntimeException('Nepodařilo se získat Chuck Norris vtip');
            }

            $result = ['data' => [
                'joke' => $data['value'],
                'id' => $data['id'] ?? null,
                'url' => $data['url'] ?? null,
                'categories' => $data['categories'] ?? [],
            ]];

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat Chuck Norris vtip: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Získá seznam dostupných kategorií
     *
     * @return array{data: array<string, mixed>}
     */
    public function getCategories(): array
    {
        $cacheKey = 'chuck_categories';

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $data = $this->httpClient->getJson(self::URL_CATEGORIES);

            if (!is_array($data)) {
                throw new \RuntimeException('Nepodařilo se získat kategorie');
            }

            $result = ['data' => [
                'categories' => $data,
                'count' => count($data),
            ]];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => '1 week',
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat kategorie: {$e->getMessage()}", 0, $e);
        }
    }
}
