<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;
use Nette\Caching\Storage;

/**
 * Service pro sledování polohy ISS (International Space Station)
 */
final class ISSService
{
    private Cache $cache;

    private const URL_ISS_POSITION = 'http://api.open-notify.org/iss-now.json';
    private const URL_ISS_PASS = 'http://api.open-notify.org/iss-pass.json';
    private const URL_ASTRONAUTS = 'http://api.open-notify.org/astros.json';
    private const CACHE_EXPIRATION = '1 minute';

    public function __construct(
        private readonly HttpClientService $httpClient,
        Storage $storage
    ) {
        $this->cache = new Cache($storage, self::class);
    }

    /**
     * Získá aktuální polohu ISS
     *
     * @return array{data: array<string, mixed>}
     */
    public function getCurrentPosition(): array
    {
        $cacheKey = 'iss_position_' . date('YmdHi');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $data = $this->httpClient->getJson(self::URL_ISS_POSITION);

            if (!isset($data['iss_position'])) {
                throw new \RuntimeException('Nepodařilo se získat polohu ISS');
            }

            $position = $data['iss_position'];

            $result = ['data' => [
                'latitude' => (float) ($position['latitude'] ?? 0),
                'longitude' => (float) ($position['longitude'] ?? 0),
                'timestamp' => $data['timestamp'] ?? time(),
                'datetime' => date('Y-m-d H:i:s', $data['timestamp'] ?? time()),
            ]];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat polohu ISS: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Získá časy přeletů ISS nad danou polohou
     *
     * @param float $latitude Zeměpisná šířka
     * @param float $longitude Zeměpisná délka
     * @param int $passes Počet přeletů (výchozí 5)
     * @return array{data: array<string, mixed>}
     */
    public function getPassTimes(float $latitude, float $longitude, int $passes = 5): array
    {
        if ($latitude < -90 || $latitude > 90) {
            throw new \RuntimeException('Zeměpisná šířka musí být mezi -90 a 90');
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new \RuntimeException('Zeměpisná délka musí být mezi -180 a 180');
        }

        if ($passes < 1 || $passes > 100) {
            throw new \RuntimeException('Počet přeletů musí být mezi 1 a 100');
        }

        $cacheKey = 'iss_pass_' . md5("{$latitude}_{$longitude}_{$passes}") . '_' . date('YmdH');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $url = self::URL_ISS_PASS . '?lat=' . $latitude . '&lon=' . $longitude . '&n=' . $passes;

            $data = $this->httpClient->getJson($url);

            if (!isset($data['response'])) {
                throw new \RuntimeException('Nepodařilo se získat časy přeletů ISS');
            }

            $passesData = [];
            foreach ($data['response'] as $pass) {
                $passesData[] = [
                    'risetime' => $pass['risetime'] ?? 0,
                    'datetime' => date('Y-m-d H:i:s', $pass['risetime'] ?? 0),
                    'duration' => $pass['duration'] ?? 0,
                ];
            }

            $result = ['data' => [
                'location' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ],
                'passes' => $passesData,
                'count' => count($passesData),
            ]];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => '1 hour',
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat časy přeletů: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Získá seznam astronautů aktuálně ve vesmíru
     *
     * @return array{data: array<string, mixed>}
     */
    public function getAstronauts(): array
    {
        $cacheKey = 'iss_astronauts_' . date('YmdH');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $data = $this->httpClient->getJson(self::URL_ASTRONAUTS);

            if (!isset($data['people'])) {
                throw new \RuntimeException('Nepodařilo se získat seznam astronautů');
            }

            $astronauts = [];
            foreach ($data['people'] as $person) {
                $astronauts[] = [
                    'name' => $person['name'] ?? 'Unknown',
                    'craft' => $person['craft'] ?? 'Unknown',
                ];
            }

            $result = ['data' => [
                'number' => $data['number'] ?? count($astronauts),
                'people' => $astronauts,
            ]];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => '1 hour',
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat seznam astronautů: {$e->getMessage()}", 0, $e);
        }
    }
}
