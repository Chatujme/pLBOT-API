<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;

/**
 * Service pro získávání obrázků lišek
 */
final class FoxService
{
    private const URL_FOX_API = 'https://randomfox.ca/floof/';
    private const CACHE_EXPIRATION = '1 hour';

    public function __construct(
        private readonly HttpClientService $httpClient,
        private readonly Cache $cache
    ) {
    }

    /**
     * Získá náhodný obrázek lišky
     *
     * @return array{data: array<string, mixed>}
     */
    public function getRandomFox(): array
    {
        try {
            $data = $this->httpClient->getJson(self::URL_FOX_API);

            if (!isset($data['image'])) {
                throw new \RuntimeException('Nepodařilo se získat obrázek lišky');
            }

            $result = ['data' => [
                'image_url' => $data['image'],
                'link' => $data['link'] ?? $data['image'],
            ]];

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat fox image: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Získá více náhodných obrázků lišek
     *
     * @param int $count Počet obrázků (1-10)
     * @return array{data: array<string, mixed>}
     */
    public function getMultipleFoxes(int $count = 5): array
    {
        if ($count < 1 || $count > 10) {
            throw new \RuntimeException('Počet obrázků musí být mezi 1 a 10');
        }

        try {
            $foxes = [];
            for ($i = 0; $i < $count; $i++) {
                $data = $this->httpClient->getJson(self::URL_FOX_API);

                if (isset($data['image'])) {
                    $foxes[] = [
                        'image_url' => $data['image'],
                        'link' => $data['link'] ?? $data['image'],
                    ];
                }

                // Malá pauza mezi požadavky
                if ($i < $count - 1) {
                    usleep(100000); // 0.1 sekundy
                }
            }

            $result = ['data' => [
                'foxes' => $foxes,
                'count' => count($foxes),
            ]];

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat fox images: {$e->getMessage()}", 0, $e);
        }
    }
}
