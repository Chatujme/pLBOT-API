<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service pro získávání inspirativních citátů
 */
final class QuotesService
{
    private const URL_QUOTES_API = 'https://api.quotable.io/quotes/random';

    private const TAGS = ['wisdom', 'inspirational', 'success', 'life', 'happiness', 'motivational', 'friendship', 'love'];

    public function __construct(
        private readonly HttpClientService $httpClient
    ) {
    }

    /**
     * Získá náhodný citát
     *
     * @param string|null $tag Téma citátu (wisdom, inspirational, success, life, atd.)
     * @return array{data: array<string, mixed>}
     */
    public function getRandomQuote(?string $tag = null): array
    {
        try {
            $url = self::URL_QUOTES_API;

            if ($tag !== null) {
                $url .= '?tags=' . urlencode($tag);
            }

            $data = $this->httpClient->getJson($url);

            // API vrací pole citátů
            if (!is_array($data) || empty($data)) {
                throw new \RuntimeException('Nepodařilo se získat citát');
            }

            $quote = $data[0];

            $result = ['data' => [
                'quote' => $quote['content'] ?? 'N/A',
                'author' => $quote['author'] ?? 'Unknown',
                'tags' => $quote['tags'] ?? [],
                'length' => $quote['length'] ?? strlen($quote['content'] ?? ''),
            ]];

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat citát: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Získá více náhodných citátů
     *
     * @param int $limit Počet citátů (1-50)
     * @return array{data: array<string, mixed>}
     */
    public function getMultipleQuotes(int $limit = 5): array
    {
        if ($limit < 1 || $limit > 50) {
            throw new \RuntimeException('Limit musí být mezi 1 a 50');
        }

        try {
            $url = self::URL_QUOTES_API . '?limit=' . $limit;

            $data = $this->httpClient->getJson($url);

            if (!is_array($data)) {
                throw new \RuntimeException('Nepodařilo se získat citáty');
            }

            $quotes = [];
            foreach ($data as $quote) {
                $quotes[] = [
                    'quote' => $quote['content'] ?? 'N/A',
                    'author' => $quote['author'] ?? 'Unknown',
                    'tags' => $quote['tags'] ?? [],
                ];
            }

            return ['data' => [
                'quotes' => $quotes,
                'count' => count($quotes),
            ]];
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat citáty: {$e->getMessage()}", 0, $e);
        }
    }
}
