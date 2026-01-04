<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service pro získávání inspirativních citátů
 * Primárně používá ZenQuotes API s fallbackem na DummyJSON
 */
final class QuotesService
{
    private const URL_ZENQUOTES = 'https://zenquotes.io/api/random';
    private const URL_ZENQUOTES_QUOTES = 'https://zenquotes.io/api/quotes';
    private const URL_DUMMYJSON = 'https://dummyjson.com/quotes/random';
    private const URL_DUMMYJSON_QUOTES = 'https://dummyjson.com/quotes';

    private const TAGS = ['wisdom', 'inspirational', 'success', 'life', 'happiness', 'motivational', 'friendship', 'love'];

    public function __construct(
        private readonly HttpClientService $httpClient
    ) {
    }

    /**
     * Získá náhodný citát
     *
     * @param string|null $tag Téma citátu (ignorováno - API nepodporuje)
     * @return array{data: array<string, mixed>}
     */
    public function getRandomQuote(?string $tag = null): array
    {
        // Try ZenQuotes first
        try {
            $data = $this->httpClient->getJson(self::URL_ZENQUOTES);

            if (is_array($data) && !empty($data) && isset($data[0]['q'])) {
                $quote = $data[0];
                return ['data' => [
                    'quote' => $quote['q'] ?? 'N/A',
                    'author' => $quote['a'] ?? 'Unknown',
                    'tags' => $tag ? [$tag] : [],
                    'length' => strlen($quote['q'] ?? ''),
                    'source' => 'zenquotes.io',
                ]];
            }
        } catch (\Exception $e) {
            // Fall through to DummyJSON
        }

        // Fallback to DummyJSON
        try {
            $data = $this->httpClient->getJson(self::URL_DUMMYJSON);

            if (is_array($data) && isset($data['quote'])) {
                return ['data' => [
                    'quote' => $data['quote'] ?? 'N/A',
                    'author' => $data['author'] ?? 'Unknown',
                    'tags' => $tag ? [$tag] : [],
                    'length' => strlen($data['quote'] ?? ''),
                    'source' => 'dummyjson.com',
                ]];
            }
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat citát: {$e->getMessage()}", 0, $e);
        }

        throw new \RuntimeException('Nepodařilo se získat citát z žádného API');
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

        // Try ZenQuotes first (returns 50 quotes)
        try {
            $data = $this->httpClient->getJson(self::URL_ZENQUOTES_QUOTES);

            if (is_array($data) && !empty($data)) {
                $quotes = [];
                $count = 0;
                foreach ($data as $quote) {
                    if ($count >= $limit) break;
                    if (isset($quote['q'])) {
                        $quotes[] = [
                            'quote' => $quote['q'] ?? 'N/A',
                            'author' => $quote['a'] ?? 'Unknown',
                            'tags' => [],
                        ];
                        $count++;
                    }
                }

                if (!empty($quotes)) {
                    return ['data' => [
                        'quotes' => $quotes,
                        'count' => count($quotes),
                        'source' => 'zenquotes.io',
                    ]];
                }
            }
        } catch (\Exception $e) {
            // Fall through to DummyJSON
        }

        // Fallback to DummyJSON
        try {
            $data = $this->httpClient->getJson(self::URL_DUMMYJSON_QUOTES . '?limit=' . $limit);

            if (is_array($data) && isset($data['quotes'])) {
                $quotes = [];
                foreach ($data['quotes'] as $quote) {
                    $quotes[] = [
                        'quote' => $quote['quote'] ?? 'N/A',
                        'author' => $quote['author'] ?? 'Unknown',
                        'tags' => [],
                    ];
                }

                return ['data' => [
                    'quotes' => $quotes,
                    'count' => count($quotes),
                    'source' => 'dummyjson.com',
                ]];
            }
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat citáty: {$e->getMessage()}", 0, $e);
        }

        throw new \RuntimeException('Nepodařilo se získat citáty z žádného API');
    }
}
