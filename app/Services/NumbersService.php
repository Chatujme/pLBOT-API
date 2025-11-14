<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;
use Nette\Caching\Storage;

/**
 * Service pro získávání zajímavostí o číslech z NumbersAPI
 */
final class NumbersService
{
    private Cache $cache;

    private const URL_NUMBERS_API = 'http://numbersapi.com';
    private const CACHE_EXPIRATION = '1 day';

    private const TYPES = ['trivia', 'math', 'date', 'year'];

    public function __construct(
        private readonly HttpClientService $httpClient,
        Storage $storage
    ) {
        $this->cache = new Cache($storage, self::class);
    }

    /**
     * Získá zajímavost o čísle
     *
     * @param int|string $number Číslo nebo 'random'
     * @param string $type Typ (trivia, math, date, year)
     * @return array{data: array<string, mixed>}
     */
    public function getNumberFact(int|string $number, string $type = 'trivia'): array
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new \RuntimeException("Neplatný typ. Použijte: " . implode(', ', self::TYPES));
        }

        $cacheKey = 'number_fact_' . $number . '_' . $type;

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $url = self::URL_NUMBERS_API . '/' . $number . '/' . $type . '?json';

            $data = $this->httpClient->getJson($url);

            if (!isset($data['text'])) {
                throw new \RuntimeException('Nepodařilo se získat zajímavost o čísle');
            }

            $result = ['data' => [
                'text' => $data['text'],
                'number' => $data['number'] ?? $number,
                'type' => $data['type'] ?? $type,
                'found' => $data['found'] ?? true,
            ]];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat number fact: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Získá náhodnou zajímavost
     *
     * @param string $type Typ (trivia, math, year)
     * @return array{data: array<string, mixed>}
     */
    public function getRandomFact(string $type = 'trivia'): array
    {
        return $this->getNumberFact('random', $type);
    }

    /**
     * Získá zajímavost o dnešním datu
     *
     * @return array{data: array<string, mixed>}
     */
    public function getTodayFact(): array
    {
        $month = (int) date('n');
        $day = (int) date('j');

        $cacheKey = 'number_today_' . date('md');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $url = self::URL_NUMBERS_API . '/' . $month . '/' . $day . '/date?json';

            $data = $this->httpClient->getJson($url);

            if (!isset($data['text'])) {
                throw new \RuntimeException('Nepodařilo se získat fact o dnešním dni');
            }

            $result = ['data' => [
                'text' => $data['text'],
                'date' => date('Y-m-d'),
                'type' => 'date',
            ]];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => '1 day',
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat today fact: {$e->getMessage()}", 0, $e);
        }
    }
}
