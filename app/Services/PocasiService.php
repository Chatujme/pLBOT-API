<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;
use Nette\Utils\Strings;

/**
 * Service pro získávání informací o počasí
 */
final class PocasiService
{
    private const URL_POCASI = 'https://pocasi-backend.centrum.cz/api/v2/widget/welcome/%s';
    private const CACHE_EXPIRATION = '2 hours'; // Počasí se aktualizuje častěji
    private const DEFAULT_CITY = 'praha';

    public function __construct(
        private readonly HttpClientService $httpClient,
        private readonly Cache $cache
    ) {
    }

    /**
     * Získá předpověď počasí
     *
     * @param string|null $den Den (dnes, zitra, pozitri) nebo null pro všechny
     * @param string|null $mesto Město pro předpověď
     * @return array{data: array<string, mixed>}
     * @throws \Exception
     */
    public function getPocasi(?string $den = null, ?string $mesto = null): array
    {
        $mesto = $this->normalizeCityName($mesto ?? self::DEFAULT_CITY);
        $cacheKey = $this->getCacheKey($den, $mesto);

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $data = $this->fetchWeatherData($mesto);

            $result = match ($den) {
                'dnes' => ['data' => $this->parseDay($data, 0)],
                'zitra' => ['data' => $this->parseDay($data, 1)],
                'pozitri' => ['data' => $this->parseDay($data, 2)],
                default => ['data' => [
                    'dnes' => $this->parseDay($data, 0),
                    'zitra' => $this->parseDay($data, 1),
                    'pozitri' => $this->parseDay($data, 2),
                ]],
            };

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat data o počasí: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Načte data o počasí z API
     *
     * @return array<string, mixed>
     * @throws \RuntimeException
     */
    private function fetchWeatherData(string $mesto): array
    {
        $url = sprintf(self::URL_POCASI, $mesto);
        $data = $this->httpClient->getJson($url);

        if (!is_array($data)) {
            throw new \RuntimeException("Neplatná odpověď z API počasí");
        }

        return $data;
    }

    /**
     * Parsuje data pro konkrétní den
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function parseDay(array $data, int $dayIndex): array
    {
        if (!isset($data['long_term_forecast']['forecasts'][$dayIndex])) {
            return [
                'error' => 'Data pro tento den nejsou k dispozici',
            ];
        }

        $forecast = $data['long_term_forecast']['forecasts'][$dayIndex];
        $welcome = $data['welcome'][0] ?? null;

        return [
            'datum' => $forecast['date'] ?? 'N/A',
            'predpoved' => $forecast['day_forecast'] ?? 'N/A',
            'nyni' => $welcome['actual']['temp'] ?? 'N/A',
            'den' => $forecast['temp_day'] ?? 'N/A',
            'noc' => $forecast['temp_night'] ?? 'N/A',
            'pro' => isset($welcome['place']['city']) ? "Pro {$welcome['place']['city']}" : 'N/A',
        ];
    }

    /**
     * Normalizuje název města do URL-friendly formátu
     */
    private function normalizeCityName(string $mesto): string
    {
        return Strings::webalize($mesto);
    }

    private function getCacheKey(?string $den, string $mesto): string
    {
        return 'pocasi_' . date('Y-m-d') . '_' . $mesto . '_' . ($den ?? 'all');
    }
}
