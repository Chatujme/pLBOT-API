<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;
use Nette\Caching\Storage;

/**
 * Service pro získávání nápadů na aktivity z Bored API
 */
final class BoredService
{
    private Cache $cache;

    private const URL_BORED_API = 'https://www.boredapi.com/api/activity';
    private const CACHE_EXPIRATION = '1 hour';

    private const TYPES = ['education', 'recreational', 'social', 'diy', 'charity', 'cooking', 'relaxation', 'music', 'busywork'];

    public function __construct(
        private readonly HttpClientService $httpClient,
        Storage $storage
    ) {
        $this->cache = new Cache($storage, self::class);
    }

    /**
     * Získá náhodnou aktivitu
     *
     * @param string|null $type Typ aktivity
     * @param int|null $participants Počet účastníků
     * @return array{data: array<string, mixed>}
     */
    public function getRandomActivity(?string $type = null, ?int $participants = null): array
    {
        try {
            $url = self::URL_BORED_API;
            $params = [];

            if ($type !== null) {
                if (!in_array($type, self::TYPES, true)) {
                    throw new \RuntimeException("Neplatný typ. Použijte: " . implode(', ', self::TYPES));
                }
                $params['type'] = $type;
            }

            if ($participants !== null) {
                if ($participants < 1 || $participants > 10) {
                    throw new \RuntimeException('Počet účastníků musí být mezi 1 a 10');
                }
                $params['participants'] = $participants;
            }

            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }

            $data = $this->httpClient->getJson($url);

            if (!isset($data['activity'])) {
                throw new \RuntimeException('Nepodařilo se získat aktivitu');
            }

            $result = ['data' => [
                'activity' => $data['activity'],
                'type' => $data['type'] ?? 'unknown',
                'participants' => $data['participants'] ?? 1,
                'price' => $data['price'] ?? 0, // 0-1 (0 = free, 1 = expensive)
                'link' => $data['link'] ?? '',
                'key' => $data['key'] ?? '',
                'accessibility' => $data['accessibility'] ?? 0, // 0-1 (0 = most accessible)
            ]];

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat aktivitu: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Získá aktivitu podle klíče
     *
     * @param string $key Unikátní klíč aktivity
     * @return array{data: array<string, mixed>}
     */
    public function getActivityByKey(string $key): array
    {
        $cacheKey = 'bored_activity_' . $key;

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $url = self::URL_BORED_API . '?key=' . urlencode($key);

            $data = $this->httpClient->getJson($url);

            if (!isset($data['activity'])) {
                throw new \RuntimeException("Aktivita s klíčem '{$key}' nebyla nalezena");
            }

            $result = ['data' => [
                'activity' => $data['activity'],
                'type' => $data['type'] ?? 'unknown',
                'participants' => $data['participants'] ?? 1,
                'price' => $data['price'] ?? 0,
                'link' => $data['link'] ?? '',
                'key' => $data['key'] ?? '',
                'accessibility' => $data['accessibility'] ?? 0,
            ]];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => '1 week',
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat aktivitu: {$e->getMessage()}", 0, $e);
        }
    }
}
