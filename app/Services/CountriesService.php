<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;
use Nette\Caching\Storage;

/**
 * Service pro získávání informací o zemích z REST Countries API
 */
final class CountriesService
{
    private Cache $cache;

    private const URL_COUNTRIES_API = 'https://restcountries.com/v3.1';
    private const CACHE_EXPIRATION = '1 week';

    public function __construct(
        private readonly HttpClientService $httpClient,
        Storage $storage
    ) {
        $this->cache = new Cache($storage, self::class);
    }

    /**
     * Získá informace o zemi podle kódu nebo názvu
     *
     * @param string $identifier Kód země (CZ, US) nebo název (Czechia, Germany)
     * @return array{data: array<string, mixed>}
     */
    public function getCountry(string $identifier): array
    {
        $cacheKey = 'country_' . strtolower($identifier);

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            // Zkusit nejprve jako kód země
            $url = self::URL_COUNTRIES_API . '/alpha/' . $identifier;

            try {
                $data = $this->httpClient->getJson($url);
            } catch (\Exception $e) {
                // Pokud selže, zkusit jako název
                $url = self::URL_COUNTRIES_API . '/name/' . urlencode($identifier);
                $data = $this->httpClient->getJson($url);
            }

            if (!is_array($data) || empty($data)) {
                throw new \RuntimeException("Země '{$identifier}' nebyla nalezena");
            }

            $country = $data[0];

            $result = ['data' => [
                'name' => $country['name']['common'] ?? 'N/A',
                'official_name' => $country['name']['official'] ?? 'N/A',
                'code' => $country['cca2'] ?? 'N/A',
                'code_alpha3' => $country['cca3'] ?? 'N/A',
                'capital' => $country['capital'][0] ?? 'N/A',
                'region' => $country['region'] ?? 'N/A',
                'subregion' => $country['subregion'] ?? 'N/A',
                'population' => $country['population'] ?? 0,
                'area' => $country['area'] ?? 0,
                'languages' => $country['languages'] ?? [],
                'currencies' => $country['currencies'] ?? [],
                'timezones' => $country['timezones'] ?? [],
                'flag' => $country['flag'] ?? '',
                'maps' => $country['maps']['googleMaps'] ?? null,
            ]];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat informace o zemi: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Získá všechny země v regionu
     *
     * @param string $region Region (europe, asia, africa, americas, oceania)
     * @return array{data: array<string, mixed>}
     */
    public function getCountriesByRegion(string $region): array
    {
        $cacheKey = 'countries_region_' . strtolower($region);

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $url = self::URL_COUNTRIES_API . '/region/' . urlencode($region);

            $data = $this->httpClient->getJson($url);

            if (!is_array($data)) {
                throw new \RuntimeException("Nepodařilo se získat země v regionu '{$region}'");
            }

            $countries = [];
            foreach ($data as $country) {
                $countries[] = [
                    'name' => $country['name']['common'] ?? 'N/A',
                    'code' => $country['cca2'] ?? 'N/A',
                    'capital' => $country['capital'][0] ?? 'N/A',
                    'population' => $country['population'] ?? 0,
                    'flag' => $country['flag'] ?? '',
                ];
            }

            $result = ['data' => [
                'region' => ucfirst($region),
                'countries' => $countries,
                'count' => count($countries),
            ]];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat země v regionu: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Získá všechny země
     *
     * @return array{data: array<string, mixed>}
     */
    public function getAllCountries(): array
    {
        $cacheKey = 'countries_all';

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $url = self::URL_COUNTRIES_API . '/all';

            $data = $this->httpClient->getJson($url);

            if (!is_array($data)) {
                throw new \RuntimeException('Nepodařilo se získat seznam zemí');
            }

            $countries = [];
            foreach ($data as $country) {
                $countries[] = [
                    'name' => $country['name']['common'] ?? 'N/A',
                    'code' => $country['cca2'] ?? 'N/A',
                    'region' => $country['region'] ?? 'N/A',
                ];
            }

            // Seřadit podle názvu
            usort($countries, fn($a, $b) => strcmp($a['name'], $b['name']));

            $result = ['data' => [
                'countries' => $countries,
                'count' => count($countries),
            ]];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat seznam zemí: {$e->getMessage()}", 0, $e);
        }
    }
}
