<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Utils\Strings;

/**
 * Service pro získávání horoskopů
 */
final class HoroskopyService
{
    private Cache $cache;

    private const URL_HOROSKOPY = 'https://www.horoskopy.cz/%s';
    private const CACHE_EXPIRATION = '1 day';

    private const ZNAMENI_MAP = [
        'beran' => 'beran',
        'byik' => 'byk',
        'byk' => 'byk',
        'blizenci' => 'blizenci',
        'rak' => 'rak',
        'lev' => 'lev',
        'panna' => 'panna',
        'vahy' => 'vahy',
        'stir' => 'stir',
        'štir' => 'stir',
        'strelec' => 'strelec',
        'kozoroh' => 'kozoroh',
        'vodnar' => 'vodnar',
        'vodnář' => 'vodnar',
        'ryby' => 'ryby',
    ];

    public function __construct(
        private readonly HttpClientService $httpClient,
        Storage $storage
    ) {
        $this->cache = new Cache($storage, self::class);
    }

    /**
     * Získá horoskop pro zadané znamení
     *
     * @return array{data: array<string, string>}
     * @throws \Exception
     */
    public function getHoroskop(string $znameni): array
    {
        $znameni = $this->normalizeZnameni($znameni);

        if (!isset(self::ZNAMENI_MAP[$znameni])) {
            return [
                'data' => [
                    'message' => 'Neznámé znamení zvěrokruhu',
                ],
            ];
        }

        $cacheKey = $this->getCacheKey($znameni);

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $url = sprintf(self::URL_HOROSKOPY, self::ZNAMENI_MAP[$znameni]);
            $html = $this->httpClient->get($url);
            $result = $this->parseHoroskop($html, $znameni);

            if (isset($result['data']['znameni'])) {
                $this->cache->save($cacheKey, $result, [
                    Cache::EXPIRE => self::CACHE_EXPIRATION,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            return [
                'data' => [
                    'message' => "Nepodařilo se načíst horoskop: {$e->getMessage()}",
                ],
            ];
        }
    }

    /**
     * Parsuje HTML stránku s horoskopem pomocí DOMDocument
     *
     * @return array{data: array<string, string>}
     */
    private function parseHoroskop(string $html, string $znameni): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        // Získáme h1 nadpis pro název znamení
        $h1Nodes = $xpath->query('//h1');
        $znameniNazev = $h1Nodes->length > 0 ? trim($h1Nodes->item(0)->textContent) : ucfirst($znameni);

        // Datum horoskopu
        $dateNodes = $xpath->query('//div[contains(@class, "date")]');
        $datum = $dateNodes->length > 0 ? trim($dateNodes->item(0)->textContent) : date('d.m.Y');

        // Pokusíme se najít jednotlivé sekce
        $sections = [
            'horoskop' => $this->extractSection($xpath, 'h2', 'p', 0),
            'laska-a-pratelstvi' => $this->extractSectionByHeading($xpath, 'Láska'),
            'penize-a-prace' => $this->extractSectionByHeading($xpath, 'Peníze'),
            'rodina-a-vztahy' => $this->extractSectionByHeading($xpath, 'Rodina'),
            'zdravi-a-kondice' => $this->extractSectionByHeading($xpath, 'Zdraví'),
            'vhodne-aktivity-na-dnes' => $this->extractSectionByHeading($xpath, 'Aktivity'),
        ];

        // Pokud se nepodařilo najít strukturovaná data, zkusíme regex fallback
        if (empty($sections['horoskop'])) {
            return $this->parseHoroskopRegex($html);
        }

        return [
            'data' => [
                'znameni' => $znameniNazev,
                'datum' => $datum,
                ...$sections,
            ],
        ];
    }

    /**
     * Extrahuje text sekce na základě h2 nadpisu
     */
    private function extractSectionByHeading(DOMXPath $xpath, string $heading): string
    {
        // Hledáme h2 nebo div obsahující klíčové slovo
        $query = "//h2[contains(text(), '{$heading}')]/following-sibling::p[1] | " .
                 "//div[contains(text(), '{$heading}')]/following-sibling::p[1]";

        $nodes = $xpath->query($query);

        if ($nodes && $nodes->length > 0) {
            return trim($nodes->item(0)->textContent);
        }

        return '';
    }

    /**
     * Extrahuje sekci na základě pozice
     */
    private function extractSection(DOMXPath $xpath, string $headerTag, string $contentTag, int $index): string
    {
        $query = "//{$headerTag}[{$index}]/following-sibling::{$contentTag}[1]";
        $nodes = $xpath->query($query);

        if ($nodes && $nodes->length > 0) {
            return trim($nodes->item(0)->textContent);
        }

        // Fallback - prostě první p element
        $pNodes = $xpath->query("//p");
        if ($pNodes && $pNodes->length > $index) {
            return trim($pNodes->item($index)->textContent);
        }

        return '';
    }

    /**
     * Fallback regex parser
     *
     * @return array{data: array<string, string>}
     */
    private function parseHoroskopRegex(string $html): array
    {
        $pattern = "#<h1>(.*?)</h1>.*?<div.*?date\">(.*?)</div>.*?<h2>(.*?)</h2>.*?<p>\s*(.*?)\s*</p>.*?" .
                   "<div.*?>(.*?)</div>.*?<p>(.*?)</p>.*?" .
                   "<div.*?>(.*?)</div>.*?<p>(.*?)</p>.*?" .
                   "<div.*?>(.*?)</div>.*?<p>(.*?)</p>.*?" .
                   "<div.*?>(.*?)</div>.*?<p>(.*?)</p>.*?" .
                   "<div.*?>(.*?)</div>.*?<p>(.*?)</p>#is";

        if (preg_match($pattern, $html, $matches)) {
            return [
                'data' => [
                    'znameni' => $matches[1],
                    'datum' => trim($matches[2]),
                    'horoskop' => $matches[4],
                    'laska-a-pratelstvi' => $matches[6] ?? '',
                    'penize-a-prace' => $matches[8] ?? '',
                    'rodina-a-vztahy' => $matches[10] ?? '',
                    'zdravi-a-kondice' => $matches[12] ?? '',
                    'vhodne-aktivity-na-dnes' => $matches[14] ?? '',
                ],
            ];
        }

        return [
            'data' => [
                'message' => 'Nepodařilo se naparsovat horoskop',
            ],
        ];
    }

    /**
     * Normalizuje název znamení
     */
    private function normalizeZnameni(string $znameni): string
    {
        return Strings::webalize($znameni);
    }

    private function getCacheKey(string $znameni): string
    {
        return 'horoskop_' . date('Y-m-d') . '_' . $znameni;
    }
}
