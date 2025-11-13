<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;

/**
 * Service pro získávání trivia otázek z Open Trivia Database
 */
final class TriviaService
{
    private const URL_TRIVIA_API = 'https://opentdb.com/api.php';
    private const URL_CATEGORIES = 'https://opentdb.com/api_category.php';
    private const CACHE_EXPIRATION = '1 hour';

    private const DIFFICULTIES = ['easy', 'medium', 'hard'];
    private const TYPES = ['multiple', 'boolean'];

    public function __construct(
        private readonly HttpClientService $httpClient,
        private readonly Cache $cache
    ) {
    }

    /**
     * Získá trivia otázky
     *
     * @param int $amount Počet otázek (1-50)
     * @param int|null $category ID kategorie
     * @param string|null $difficulty Obtížnost (easy, medium, hard)
     * @param string|null $type Typ (multiple, boolean)
     * @return array{data: array<string, mixed>}
     */
    public function getQuestions(
        int $amount = 10,
        ?int $category = null,
        ?string $difficulty = null,
        ?string $type = null
    ): array {
        if ($amount < 1 || $amount > 50) {
            throw new \RuntimeException('Počet otázek musí být mezi 1 a 50');
        }

        if ($difficulty !== null && !in_array($difficulty, self::DIFFICULTIES, true)) {
            throw new \RuntimeException("Neplatná obtížnost. Použijte: " . implode(', ', self::DIFFICULTIES));
        }

        if ($type !== null && !in_array($type, self::TYPES, true)) {
            throw new \RuntimeException("Neplatný typ. Použijte: " . implode(', ', self::TYPES));
        }

        try {
            $params = ['amount' => $amount];

            if ($category !== null) {
                $params['category'] = $category;
            }
            if ($difficulty !== null) {
                $params['difficulty'] = $difficulty;
            }
            if ($type !== null) {
                $params['type'] = $type;
            }

            $url = self::URL_TRIVIA_API . '?' . http_build_query($params);

            $data = $this->httpClient->getJson($url);

            if (!isset($data['results']) || $data['response_code'] !== 0) {
                throw new \RuntimeException('Nepodařilo se získat trivia otázky');
            }

            $questions = [];
            foreach ($data['results'] as $item) {
                $answers = array_merge(
                    [$item['correct_answer']],
                    $item['incorrect_answers']
                );
                shuffle($answers);

                $questions[] = [
                    'category' => html_entity_decode($item['category'], ENT_QUOTES | ENT_HTML5),
                    'type' => $item['type'],
                    'difficulty' => $item['difficulty'],
                    'question' => html_entity_decode($item['question'], ENT_QUOTES | ENT_HTML5),
                    'correct_answer' => html_entity_decode($item['correct_answer'], ENT_QUOTES | ENT_HTML5),
                    'answers' => array_map(fn($a) => html_entity_decode($a, ENT_QUOTES | ENT_HTML5), $answers),
                ];
            }

            return ['data' => [
                'questions' => $questions,
                'count' => count($questions),
            ]];
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat trivia otázky: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Získá seznam kategorií
     *
     * @return array{data: array<string, mixed>}
     */
    public function getCategories(): array
    {
        $cacheKey = 'trivia_categories';

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $data = $this->httpClient->getJson(self::URL_CATEGORIES);

            if (!isset($data['trivia_categories'])) {
                throw new \RuntimeException('Nepodařilo se získat kategorie');
            }

            $categories = [];
            foreach ($data['trivia_categories'] as $cat) {
                $categories[] = [
                    'id' => $cat['id'],
                    'name' => $cat['name'],
                ];
            }

            $result = ['data' => [
                'categories' => $categories,
                'count' => count($categories),
            ]];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => '1 week',
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat kategorie: {$e->getMessage()}", 0, $e);
        }
    }
}
