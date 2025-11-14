<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;

/**
 * Service pro zkracování URL pomocí is.gd a TinyURL
 */
final class UrlShortenerService
{
    private const ISGD_API = 'https://is.gd/create.php';
    private const TINYURL_API = 'https://tinyurl.com/api-create.php';
    private const CACHE_EXPIRATION = '1 month'; // Zkrácené URLs se nemění

    public function __construct(
        private readonly HttpClientService $httpClient,
        private readonly Cache $cache
    ) {
    }

    /**
     * Zkrátí URL pomocí is.gd služby
     *
     * @param string $url URL ke zkrácení
     * @param string|null $customAlias Vlastní alias (volitelné)
     * @return array{data: array<string, string>}
     */
    public function shortenWithIsGd(string $url, ?string $customAlias = null): array
    {
        if (!$this->isValidUrl($url)) {
            throw new \RuntimeException('Neplatná URL adresa');
        }

        $cacheKey = 'url_isgd_' . md5($url . ($customAlias ?? ''));

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $params = [
                'format' => 'simple',
                'url' => $url,
            ];

            if ($customAlias !== null) {
                $params['shorturl'] = $customAlias;
            }

            $apiUrl = self::ISGD_API . '?' . http_build_query($params);
            $shortUrl = trim($this->httpClient->get($apiUrl));

            // Kontrola chyb (is.gd vrací chyby jako text začínající "Error")
            if (str_starts_with($shortUrl, 'Error')) {
                throw new \RuntimeException($shortUrl);
            }

            $result = [
                'data' => [
                    'original_url' => $url,
                    'short_url' => $shortUrl,
                    'service' => 'is.gd',
                    'custom_alias' => $customAlias,
                ],
            ];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se zkrátit URL: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Zkrátí URL pomocí TinyURL služby
     *
     * @param string $url URL ke zkrácení
     * @return array{data: array<string, string>}
     */
    public function shortenWithTinyUrl(string $url): array
    {
        if (!$this->isValidUrl($url)) {
            throw new \RuntimeException('Neplatná URL adresa');
        }

        $cacheKey = 'url_tinyurl_' . md5($url);

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $apiUrl = self::TINYURL_API . '?url=' . urlencode($url);
            $shortUrl = trim($this->httpClient->get($apiUrl));

            if (empty($shortUrl) || str_starts_with($shortUrl, 'Error')) {
                throw new \RuntimeException('TinyURL služba vrátila chybu');
            }

            $result = [
                'data' => [
                    'original_url' => $url,
                    'short_url' => $shortUrl,
                    'service' => 'tinyurl.com',
                ],
            ];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se zkrátit URL: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Zkrátí URL automaticky (vybere nejlepší službu)
     *
     * @param string $url URL ke zkrácení
     * @param string|null $customAlias Vlastní alias (pouze is.gd)
     * @return array{data: array<string, string>}
     */
    public function shorten(string $url, ?string $customAlias = null): array
    {
        // Pokud je zadán vlastní alias, použijeme is.gd (TinyURL ho nepodporuje)
        if ($customAlias !== null) {
            return $this->shortenWithIsGd($url, $customAlias);
        }

        // Jinak zkusíme is.gd, při selhání TinyURL
        try {
            return $this->shortenWithIsGd($url);
        } catch (\Exception $e) {
            return $this->shortenWithTinyUrl($url);
        }
    }

    /**
     * Získá statistiky zkrácené URL (pouze is.gd)
     *
     * @param string $shortUrl Zkrácená URL
     * @return array{data: array<string, mixed>}
     */
    public function getStats(string $shortUrl): array
    {
        if (!str_contains($shortUrl, 'is.gd/')) {
            throw new \RuntimeException('Statistiky jsou dostupné pouze pro is.gd odkazy');
        }

        try {
            // Extrakce kódu z URL
            $code = basename(parse_url($shortUrl, PHP_URL_PATH));

            $apiUrl = "https://is.gd/stats.php?url={$code}&format=json";
            $data = $this->httpClient->getJson($apiUrl);

            if (isset($data['error'])) {
                throw new \RuntimeException($data['error']);
            }

            return [
                'data' => [
                    'short_url' => $shortUrl,
                    'clicks' => $data['clicks'] ?? 0,
                    'created' => $data['created'] ?? null,
                    'service' => 'is.gd',
                ],
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat statistiky: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Validuje URL
     */
    private function isValidUrl(string $url): bool
    {
        if (strlen($url) > 2048) {
            return false; // URL je příliš dlouhá
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
