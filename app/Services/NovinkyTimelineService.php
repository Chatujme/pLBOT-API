<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;
use Nette\Caching\Storage;

/**
 * Service for fetching and parsing news from Novinky.cz "Stalo se" timeline
 */
final class NovinkyTimelineService
{
    private Cache $cache;

    private const CACHE_EXPIRATION = '5 minutes';
    private const BASE_URL = 'https://www.novinky.cz';
    private const TIMELINE_URL = 'https://www.novinky.cz/sekce/stalo-se-154';

    public function __construct(
        private readonly HttpClientService $httpClient,
        Storage $storage
    ) {
        $this->cache = new Cache($storage, self::class);
    }

    /**
     * Get latest news from "Stalo se" timeline
     *
     * @param int $limit Maximum number of articles (1-50)
     * @return array{source: string, section: string, count: int, articles: array}
     * @throws \RuntimeException
     */
    public function getTimeline(int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $cacheKey = "timeline_{$limit}";

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $html = $this->httpClient->get(self::TIMELINE_URL, [
                'timeout' => 15,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ]);

            $articles = $this->parseHtmlForArticles($html, $limit);

            $result = [
                'source' => 'Novinky.cz',
                'section' => 'Stalo se',
                'count' => count($articles),
                'articles' => $articles,
            ];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se načíst timeline: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Parse HTML and extract articles from embedded JSON data
     */
    private function parseHtmlForArticles(string $html, int $limit): array
    {
        $articles = [];
        $seen = [];

        // Find all article slugs (long slugs with section prefix)
        $slugPattern = '/"slug":"((?:internet-a-pc|domaci|zahranicni|ekonomika|kultura|sport|krimi)-[a-z0-9-]{20,})"/';
        preg_match_all($slugPattern, $html, $slugMatches);
        $articleSlugs = array_unique($slugMatches[1] ?? []);

        // Find all titles with UIDs (actual article titles, not image captions)
        $titlePattern = '/"title":"([^"]{20,150})","uid":(\d+)/';
        preg_match_all($titlePattern, $html, $titleMatches, PREG_SET_ORDER);

        // Find all dates
        $datePattern = '/"dateOfPublication":"(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})"/';
        preg_match_all($datePattern, $html, $dateMatches);
        $dates = $dateMatches[1] ?? [];

        // Match articles: titles and dates appear in order
        $dateIndex = 0;
        foreach ($titleMatches as $match) {
            if (count($articles) >= $limit) {
                break;
            }

            $title = $match[1];

            // Skip template placeholders
            if (str_contains($title, '@') || str_contains($title, '||')) {
                continue;
            }

            // Skip duplicates
            $key = md5($title);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            // Find matching slug for this title
            $slug = $this->findMatchingSlug($title, $articleSlugs);
            $date = $dates[$dateIndex] ?? date('Y-m-d H:i:s');
            $dateIndex++;

            $articles[] = [
                'title' => $this->cleanText($title),
                'url' => $slug ? self::BASE_URL . '/' . $slug : self::TIMELINE_URL,
                'perex' => '',
                'published' => $date,
                'section' => $slug ? $this->extractSection($slug) : 'Stalo se',
            ];
        }

        // Fallback if no articles found
        if (empty($articles)) {
            $articles = $this->parseFallback($html, $limit);
        }

        return $articles;
    }

    /**
     * Find matching slug for a title based on keywords
     */
    private function findMatchingSlug(string $title, array $slugs): ?string
    {
        $titleLower = mb_strtolower($title);
        $titleWords = preg_split('/\s+/', $titleLower);

        foreach ($slugs as $slug) {
            $matchCount = 0;
            foreach ($titleWords as $word) {
                if (mb_strlen($word) > 3) {
                    $wordNormalized = $this->normalizeForSlug($word);
                    if (str_contains($slug, $wordNormalized)) {
                        $matchCount++;
                    }
                }
            }
            // If at least 2 significant words match, consider it a match
            if ($matchCount >= 2) {
                return $slug;
            }
        }
        return null;
    }

    /**
     * Normalize text for slug matching (remove diacritics)
     */
    private function normalizeForSlug(string $text): string
    {
        $map = [
            'á' => 'a', 'č' => 'c', 'ď' => 'd', 'é' => 'e', 'ě' => 'e',
            'í' => 'i', 'ň' => 'n', 'ó' => 'o', 'ř' => 'r', 'š' => 's',
            'ť' => 't', 'ú' => 'u', 'ů' => 'u', 'ý' => 'y', 'ž' => 'z',
        ];
        return strtr(mb_strtolower($text), $map);
    }

    /**
     * Fallback parsing method
     */
    private function parseFallback(string $html, int $limit): array
    {
        $articles = [];
        $seen = [];

        // Find titles with dates
        preg_match_all('/"title":"([^"]{15,})"[^}]*?"dateOfPublication":"([^"]+)"/', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (count($articles) >= $limit) {
                break;
            }

            $title = $match[1];
            $date = $match[2];

            // Skip templates
            if (str_contains($title, '@') || str_contains($title, '||')) {
                continue;
            }

            // Skip duplicates by title
            $key = md5($title);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $articles[] = [
                'title' => $this->cleanText($title),
                'url' => self::TIMELINE_URL,
                'perex' => '',
                'published' => $date,
                'section' => 'Stalo se',
            ];
        }

        return $articles;
    }

    /**
     * Extract section name from slug
     */
    private function extractSection(string $slug): string
    {
        $parts = explode('-', $slug);
        if (!empty($parts[0])) {
            $section = ucfirst($parts[0]);
            $sectionMap = [
                'Domaci' => 'Domácí',
                'Zahranicni' => 'Zahraniční',
                'Ekonomika' => 'Ekonomika',
                'Sport' => 'Sport',
                'Kultura' => 'Kultura',
            ];
            return $sectionMap[$section] ?? $section;
        }
        return 'Stalo se';
    }

    /**
     * Clean text from HTML entities and special characters
     */
    private function cleanText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}
