<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;

/**
 * Service pro získávání obrázků psů
 */
final class DogService
{
    private const URL_DOG_API = 'https://dog.ceo/api/breeds/image/random';
    private const URL_DOG_BREEDS = 'https://dog.ceo/api/breeds/list/all';
    private const CACHE_EXPIRATION = '1 hour';

    public function __construct(
        private readonly HttpClientService $httpClient,
        private readonly Cache $cache
    ) {
    }

    /**
     * Získá náhodný obrázek psa
     *
     * @param string|null $breed Plemeno psa (např. husky, beagle)
     * @return array{data: array<string, string>}
     */
    public function getRandomDog(?string $breed = null): array
    {
        try {
            $url = self::URL_DOG_API;

            if ($breed !== null) {
                $url = "https://dog.ceo/api/breed/{$breed}/images/random";
            }

            $data = $this->httpClient->getJson($url);

            if ($data['status'] !== 'success') {
                throw new \RuntimeException($data['message'] ?? 'Nepodařilo se získat obrázek psa');
            }

            $result = ['data' => [
                'image_url' => $data['message'],
                'breed' => $breed ?? 'random',
            ]];

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat dog image: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Získá seznam všech plemen
     *
     * @return array{data: array<string, mixed>}
     */
    public function getBreeds(): array
    {
        $cacheKey = 'dog_breeds';

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $data = $this->httpClient->getJson(self::URL_DOG_BREEDS);

            if ($data['status'] !== 'success') {
                throw new \RuntimeException('Nepodařilo se získat seznam plemen');
            }

            $result = ['data' => $data['message']];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => '1 week',
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat breeds: {$e->getMessage()}", 0, $e);
        }
    }
}
