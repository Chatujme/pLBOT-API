<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;
use SimpleXMLElement;

/**
 * Service pro získávání českých zpráv z RSS feedů
 */
final class NewsRssService
{
    private const CACHE_EXPIRATION = '15 minutes';

    /**
     * Dostupné RSS zdroje českých zpravodajství
     */
    private const RSS_SOURCES = [
        'ct24' => [
            'name' => 'ČT24',
            'url' => 'https://ct24.ceskatelevize.cz/rss/tema/hlavni-zpravy-84313',
            'description' => 'Zpravodajství České televize',
        ],
        'novinky' => [
            'name' => 'Novinky.cz',
            'url' => 'https://www.novinky.cz/rss',
            'description' => 'Zpravodajský portál Novinky.cz (Seznam)',
        ],
        'aktualne' => [
            'name' => 'Aktuálně.cz',
            'url' => 'https://www.aktualne.cz/rss/',
            'description' => 'Zpravodajský portál Aktuálně.cz',
        ],
        'blesk' => [
            'name' => 'Blesk.cz',
            'url' => 'https://www.blesk.cz/rss',
            'description' => 'Zpravodajský portál Blesk.cz',
        ],
    ];

    public function __construct(
        private readonly HttpClientService $httpClient,
        private readonly Cache $cache
    ) {
    }

    /**
     * Získá seznam všech dostupných RSS zdrojů
     *
     * @return array{sources: array<string, array{name: string, description: string}>}
     */
    public function getAllSources(): array
    {
        $sources = [];
        foreach (self::RSS_SOURCES as $key => $source) {
            $sources[$key] = [
                'name' => $source['name'],
                'description' => $source['description'],
            ];
        }

        return ['sources' => $sources];
    }

    /**
     * Získá poslední zprávy ze specifikovaného zdroje
     *
     * @param string $source Identifikátor zdroje (ct24, novinky, aktualne, blesk)
     * @param int $limit Maximální počet zpráv (1-50)
     * @return array{source: string, count: int, articles: array<array{title: string, link: string, description: string, pubDate: string, author?: string}>}
     * @throws \RuntimeException
     */
    public function getLatestNews(string $source, int $limit = 10): array
    {
        // Validace zdroje
        if (!isset(self::RSS_SOURCES[$source])) {
            throw new \RuntimeException("Neznámý zdroj: {$source}. Dostupné zdroje: " . implode(', ', array_keys(self::RSS_SOURCES)));
        }

        // Validace limitu
        $limit = max(1, min(50, $limit));

        $cacheKey = $this->getCacheKey($source, $limit);

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $sourceInfo = self::RSS_SOURCES[$source];
            $rssContent = $this->httpClient->get($sourceInfo['url'], [
                'timeout' => 15,
                'user_agent' => 'Mozilla/5.0 (compatible; pLBOT-API/2.0)',
            ]);

            $articles = $this->parseRssFeed($rssContent, $limit);

            $result = [
                'source' => $sourceInfo['name'],
                'count' => count($articles),
                'articles' => $articles,
            ];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se načíst RSS feed ze zdroje {$source}: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Vyhledá zprávy podle klíčového slova v titulcích
     *
     * @param string $query Vyhledávací dotaz
     * @param string $source Zdroj ('all' pro všechny zdroje nebo konkrétní zdroj)
     * @return array{query: string, source: string, count: int, articles: array<array{title: string, link: string, description: string, pubDate: string, author?: string, source: string}>}
     * @throws \RuntimeException
     */
    public function searchNews(string $query, string $source = 'all'): array
    {
        if (empty(trim($query))) {
            throw new \RuntimeException("Vyhledávací dotaz nesmí být prázdný");
        }

        $cacheKey = $this->getSearchCacheKey($query, $source);

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $allArticles = [];
        $sourcesToSearch = $source === 'all' ? array_keys(self::RSS_SOURCES) : [$source];

        // Validace zdroje pokud není 'all'
        if ($source !== 'all' && !isset(self::RSS_SOURCES[$source])) {
            throw new \RuntimeException("Neznámý zdroj: {$source}. Dostupné zdroje: " . implode(', ', array_keys(self::RSS_SOURCES)));
        }

        foreach ($sourcesToSearch as $sourceKey) {
            try {
                $sourceInfo = self::RSS_SOURCES[$sourceKey];
                $rssContent = $this->httpClient->get($sourceInfo['url'], [
                    'timeout' => 15,
                    'user_agent' => 'Mozilla/5.0 (compatible; pLBOT-API/2.0)',
                ]);

                $articles = $this->parseRssFeed($rssContent, 50); // Načteme více pro lepší výsledky vyhledávání

                // Filtrujeme články podle dotazu v titulku nebo popisu
                foreach ($articles as $article) {
                    $searchIn = mb_strtolower($article['title'] . ' ' . $article['description']);
                    $queryLower = mb_strtolower($query);

                    if (str_contains($searchIn, $queryLower)) {
                        $article['source'] = $sourceInfo['name'];
                        $allArticles[] = $article;
                    }
                }
            } catch (\Exception $e) {
                // Pokračujeme i když jeden zdroj selže
                continue;
            }
        }

        // Seřadíme podle data (nejnovější první)
        usort($allArticles, function ($a, $b) {
            return strtotime($b['pubDate']) <=> strtotime($a['pubDate']);
        });

        $result = [
            'query' => $query,
            'source' => $source === 'all' ? 'Všechny zdroje' : self::RSS_SOURCES[$source]['name'],
            'count' => count($allArticles),
            'articles' => $allArticles,
        ];

        $this->cache->save($cacheKey, $result, [
            Cache::EXPIRE => self::CACHE_EXPIRATION,
        ]);

        return $result;
    }

    /**
     * Parsuje RSS feed a extrahuje články
     *
     * @return array<array{title: string, link: string, description: string, pubDate: string, author?: string}>
     * @throws \RuntimeException
     */
    private function parseRssFeed(string $rssContent, int $limit): array
    {
        // Potlačíme varování při parsování XML
        libxml_use_internal_errors(true);

        try {
            $xml = new SimpleXMLElement($rssContent);
            $articles = [];
            $count = 0;

            // Iterujeme přes položky (items)
            foreach ($xml->channel->item as $item) {
                if ($count >= $limit) {
                    break;
                }

                // Extrahujeme základní informace
                $title = (string) $item->title;
                $link = (string) $item->link;
                $description = (string) $item->description;
                $pubDate = (string) $item->pubDate;

                // Pokusíme se extrahovat autora (může být v různých formátech)
                $author = null;
                if (isset($item->author) && !empty((string) $item->author)) {
                    $author = (string) $item->author;
                } elseif (isset($item->children('dc', true)->creator)) {
                    $author = (string) $item->children('dc', true)->creator;
                }

                // Vyčistíme HTML tagy z popisu a CDATA
                $description = strip_tags($description);
                $description = trim($description);
                if (strlen($description) > 500) {
                    $description = substr($description, 0, 497) . '...';
                }

                // Vyčistíme CDATA z titulku
                $title = strip_tags($title);
                $title = trim($title);

                $article = [
                    'title' => $title,
                    'link' => $link,
                    'description' => $description,
                    'pubDate' => $pubDate,
                ];

                if ($author !== null) {
                    $article['author'] = $author;
                }

                $articles[] = $article;
                $count++;
            }

            libxml_clear_errors();
            return $articles;
        } catch (\Exception $e) {
            libxml_clear_errors();
            throw new \RuntimeException("Chyba při parsování RSS feedu: {$e->getMessage()}", 0, $e);
        }
    }

    private function getCacheKey(string $source, int $limit): string
    {
        return "news_rss_{$source}_{$limit}";
    }

    private function getSearchCacheKey(string $query, string $source): string
    {
        $queryHash = md5($query);
        return "news_rss_search_{$queryHash}_{$source}";
    }
}
