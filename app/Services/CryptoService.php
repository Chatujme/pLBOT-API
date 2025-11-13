<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;

/**
 * Service pro získávání cen kryptměn z CoinGecko API
 */
final class CryptoService
{
    private const URL_COINGECKO = 'https://api.coingecko.com/api/v3';
    private const CACHE_EXPIRATION = '5 minutes';

    private const SUPPORTED_CURRENCIES = ['usd', 'eur', 'czk', 'gbp', 'btc'];
    private const POPULAR_COINS = ['bitcoin', 'ethereum', 'cardano', 'ripple', 'solana', 'polkadot', 'dogecoin', 'litecoin'];

    public function __construct(
        private readonly HttpClientService $httpClient,
        private readonly Cache $cache
    ) {
    }

    /**
     * Získá aktuální cenu kryptměny
     *
     * @param string $coinId ID kryptoměny (např. bitcoin, ethereum)
     * @param string $currency Měna (usd, eur, czk)
     * @return array{data: array<string, mixed>}
     */
    public function getCoinPrice(string $coinId, string $currency = 'usd'): array
    {
        $currency = strtolower($currency);

        if (!in_array($currency, self::SUPPORTED_CURRENCIES, true)) {
            throw new \RuntimeException("Nepodporovaná měna. Použijte: " . implode(', ', self::SUPPORTED_CURRENCIES));
        }

        $cacheKey = 'crypto_price_' . $coinId . '_' . $currency . '_' . date('YmdHi');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $url = self::URL_COINGECKO . '/simple/price?ids=' . $coinId . '&vs_currencies=' . $currency . '&include_24hr_change=true&include_market_cap=true';

            $data = $this->httpClient->getJson($url);

            if (!isset($data[$coinId])) {
                throw new \RuntimeException("Kryptoměna '{$coinId}' nebyla nalezena");
            }

            $coinData = $data[$coinId];

            $result = ['data' => [
                'coin' => $coinId,
                'currency' => strtoupper($currency),
                'price' => $coinData[$currency] ?? null,
                'market_cap' => $coinData[$currency . '_market_cap'] ?? null,
                'change_24h' => $coinData[$currency . '_24h_change'] ?? null,
                'timestamp' => date('Y-m-d H:i:s'),
            ]];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat cenu kryptoměny: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Získá ceny více kryptoměn najednou
     *
     * @param array<string> $coinIds ID kryptoměn
     * @param string $currency Měna
     * @return array{data: array<string, mixed>}
     */
    public function getMultiplePrices(array $coinIds, string $currency = 'usd'): array
    {
        $currency = strtolower($currency);

        if (!in_array($currency, self::SUPPORTED_CURRENCIES, true)) {
            throw new \RuntimeException("Nepodporovaná měna. Použijte: " . implode(', ', self::SUPPORTED_CURRENCIES));
        }

        $cacheKey = 'crypto_prices_' . md5(implode(',', $coinIds)) . '_' . $currency . '_' . date('YmdHi');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $url = self::URL_COINGECKO . '/simple/price?ids=' . implode(',', $coinIds) . '&vs_currencies=' . $currency . '&include_24hr_change=true';

            $data = $this->httpClient->getJson($url);

            $coins = [];
            foreach ($coinIds as $coinId) {
                if (isset($data[$coinId])) {
                    $coins[$coinId] = [
                        'price' => $data[$coinId][$currency] ?? null,
                        'change_24h' => $data[$coinId][$currency . '_24h_change'] ?? null,
                    ];
                }
            }

            $result = ['data' => [
                'currency' => strtoupper($currency),
                'coins' => $coins,
                'timestamp' => date('Y-m-d H:i:s'),
            ]];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat ceny kryptoměn: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Získá ceny populárních kryptoměn
     *
     * @param string $currency Měna
     * @return array{data: array<string, mixed>}
     */
    public function getPopularCoins(string $currency = 'usd'): array
    {
        return $this->getMultiplePrices(self::POPULAR_COINS, $currency);
    }
}
