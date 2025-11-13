<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Nette\Caching\Cache;
use Nette\Utils\Strings;

/**
 * Service pro získávání informací o svátkách pomocí JSON API
 */
final class SvatkyService
{
    // Změněno z HTML parsingu na JSON API
    private const URL_SVATKY_API = 'https://svatkyapi.cz/api/day';
    private const CACHE_EXPIRATION = '1 day';

    public function __construct(
        private readonly HttpClientService $httpClient,
        private readonly Cache $cache
    ) {
    }

    /**
     * Získá svátek pro daný den pomocí JSON API
     *
     * @param string|null $den Den (predevcirem, vcera, dnes, zitra) nebo null pro všechny
     * @return array{data: string|array<string, string>}
     * @throws \Exception
     */
    public function getSvatek(?string $den = null): array
    {
        $cacheKey = $this->getCacheKey($den);

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $result = match ($den) {
                'predevcirem', 'předevčírem' => ['data' => $this->getSvatekForOffset(-2)],
                'vcera', 'včera' => ['data' => $this->getSvatekForOffset(-1)],
                'dnes' => ['data' => $this->getSvatekForOffset(0)],
                'zitra', 'zítra' => ['data' => $this->getSvatekForOffset(1)],
                default => ['data' => [
                    'predevcirem' => $this->getSvatekForOffset(-2),
                    'vcera' => $this->getSvatekForOffset(-1),
                    'dnes' => $this->getSvatekForOffset(0),
                    'zitra' => $this->getSvatekForOffset(1),
                ]],
            };

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat data o svátkách: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Získá svátek pro den s daným offsetem od dneška
     */
    private function getSvatekForOffset(int $dayOffset): string
    {
        $date = new \DateTime();
        if ($dayOffset !== 0) {
            $date->modify("{$dayOffset} days");
        }
        $dateStr = $date->format('Y-m-d'); // Format: YYYY-MM-DD

        $url = self::URL_SVATKY_API . '/' . $dateStr;

        try {
            $data = $this->httpClient->getJson($url);

            if (isset($data['name']) && !empty($data['name'])) {
                return $data['name'];
            }

            return 'Neznámý';
        } catch (\Exception $e) {
            // Fallback - vrátíme chybu nebo prázdnou hodnotu
            return 'N/A';
        }
    }

    private function getCacheKey(?string $den): string
    {
        return 'svatky_' . date('Y-m-d') . '_' . ($den ?? 'all');
    }
}
