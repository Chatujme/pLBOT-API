<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service pro získávání zajímavostí o kočkách
 */
final class CatFactsService
{
    private const URL_CAT_FACTS = 'https://catfact.ninja/fact';

    public function __construct(
        private readonly HttpClientService $httpClient
    ) {
    }

    /**
     * Získá náhodnou zajímavost o kočkách
     *
     * @return array{data: array<string, string>}
     */
    public function getRandomFact(): array
    {
        try {
            $data = $this->httpClient->getJson(self::URL_CAT_FACTS);

            if (!isset($data['fact'])) {
                throw new \RuntimeException('Nepodařilo se získat cat fact');
            }

            $result = ['data' => [
                'fact' => $data['fact'],
                'length' => $data['length'] ?? strlen($data['fact']),
            ]];

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat cat fact: {$e->getMessage()}", 0, $e);
        }
    }
}
