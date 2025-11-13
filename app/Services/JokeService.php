<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service pro získávání vtipů z JokeAPI
 */
final class JokeService
{
    private const URL_JOKE_API = 'https://v2.jokeapi.dev/joke';

    private const CATEGORIES = ['Programming', 'Misc', 'Dark', 'Pun', 'Spooky', 'Christmas'];
    private const TYPES = ['single', 'twopart'];

    public function __construct(
        private readonly HttpClientService $httpClient
    ) {
    }

    /**
     * Získá náhodný vtip
     *
     * @param string|null $category Kategorie vtipu (Programming, Misc, Dark, Pun, Spooky, Christmas)
     * @param bool $safe Pouze bezpečné vtipy (bez vulgárního obsahu)
     * @return array{data: array<string, mixed>}
     */
    public function getRandomJoke(?string $category = null, bool $safe = true): array
    {
        $category = $category ?? 'Any';

        if (!in_array($category, self::CATEGORIES, true) && $category !== 'Any') {
            throw new \RuntimeException("Neplatná kategorie. Použijte: " . implode(', ', self::CATEGORIES));
        }

        try {
            $url = self::URL_JOKE_API . '/' . $category;

            if ($safe) {
                $url .= '?safe-mode';
            }

            $data = $this->httpClient->getJson($url);

            if (isset($data['error']) && $data['error'] === true) {
                throw new \RuntimeException($data['message'] ?? 'Nepodařilo se získat vtip');
            }

            return ['data' => $this->formatJoke($data)];
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat vtip: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Formátuje vtip do čitelné podoby
     *
     * @param array<string, mixed> $jokeData
     * @return array<string, mixed>
     */
    private function formatJoke(array $jokeData): array
    {
        $type = $jokeData['type'] ?? 'single';
        $category = $jokeData['category'] ?? 'Unknown';

        if ($type === 'single') {
            return [
                'type' => 'single',
                'category' => $category,
                'joke' => $jokeData['joke'] ?? 'N/A',
            ];
        }

        // Two-part joke
        return [
            'type' => 'twopart',
            'category' => $category,
            'setup' => $jokeData['setup'] ?? 'N/A',
            'delivery' => $jokeData['delivery'] ?? 'N/A',
        ];
    }
}
