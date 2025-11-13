<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Nette\Caching\Cache;
use Nette\Utils\Strings;

/**
 * Service pro získávání informací o svátkách
 */
final class SvatkyService
{
    private const URL_SVATKY = 'https://svatky.pavucina.com/svatek-vcera-dnes-zitra.html';
    private const CACHE_EXPIRATION = '1 day';

    public function __construct(
        private readonly HttpClientService $httpClient,
        private readonly Cache $cache
    ) {
    }

    /**
     * Získá svátek pro daný den
     *
     * @param string|null $den Den (predevcirem, vcera, dnes, zitra) nebo null pro všechny
     * @return array{data: string|array<string, string>}
     * @throws \Exception
     */
    public function getSvatek(?string $den = null): array
    {
        $cacheKey = $this->getCacheKey($den);

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $html = $this->httpClient->get(self::URL_SVATKY);
        $result = match ($den) {
            'predevcirem', 'předevčírem' => ['data' => $this->parseSingleDay($html, 'Předevčírem')],
            'vcera', 'včera' => ['data' => $this->parseSingleDay($html, 'Včera')],
            'dnes' => ['data' => $this->parseSingleDay($html, 'Dnes')],
            'zitra', 'zítra' => ['data' => $this->parseSingleDay($html, 'Zítra')],
            default => ['data' => $this->parseAllDays($html)],
        };

        $this->cache->save($cacheKey, $result, [
            Cache::EXPIRE => self::CACHE_EXPIRATION,
        ]);

        return $result;
    }

    /**
     * Parsuje jméno pro jeden konkrétní den
     */
    private function parseSingleDay(string $html, string $dayLabel): string
    {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        // Hledáme td s textem dne a pak následující td s jménem
        $query = "//td[contains(text(), '{$dayLabel}')]/following-sibling::td[1]";
        $nodes = $xpath->query($query);

        if ($nodes && $nodes->length > 0) {
            $content = trim($nodes->item(0)->textContent);
            // Pokud je jméno v odkazu
            if (empty($content)) {
                $linkQuery = "//td[contains(text(), '{$dayLabel}')]/following-sibling::td[1]//a";
                $linkNodes = $xpath->query($linkQuery);
                if ($linkNodes && $linkNodes->length > 0) {
                    $content = trim($linkNodes->item(0)->textContent);
                }
            }
            return $content ?: 'Neznámý';
        }

        // Fallback na regex pokud DOM parser selže
        return $this->parseSingleDayRegex($html, $dayLabel);
    }

    /**
     * Fallback regex parser pro jeden den
     */
    private function parseSingleDayRegex(string $html, string $dayLabel): string
    {
        // Normalizujeme label (. může být nebo nemusí být v HTML)
        $pattern = '#<td[^>]*>' . preg_quote($dayLabel, '#') . '</td>\s*<td[^>]*>(?:<a[^>]*>)?([^<]+)#i';

        if (preg_match($pattern, $html, $matches)) {
            return trim($matches[1]);
        }

        // Alternativní pattern s diakritikou escapovanou
        $altPattern = '#<td[^>]*>P.edev..rem|V.era|Dnes|Z.tra</td>\s*.*?(?:<a[^>]*>)?([^<]+?)(?:</a>)?</td>#i';

        if (preg_match($altPattern, $html, $matches)) {
            return trim($matches[1]);
        }

        return 'Neznámý';
    }

    /**
     * Parsuje všechny dny najednou
     *
     * @return array<string, string>
     */
    private function parseAllDays(string $html): array
    {
        return [
            'predevcirem' => $this->parseSingleDay($html, 'Předevčírem'),
            'vcera' => $this->parseSingleDay($html, 'Včera'),
            'dnes' => $this->parseSingleDay($html, 'Dnes'),
            'zitra' => $this->parseSingleDay($html, 'Zítra'),
        ];
    }

    private function getCacheKey(?string $den): string
    {
        return 'svatky_' . date('Y-m-d') . '_' . ($den ?? 'all');
    }
}
