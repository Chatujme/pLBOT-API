<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Nette\Caching\Cache;
use Nette\Caching\Storage;

/**
 * Service pro získávání informací o místnostech z Chatujme.cz
 */
final class MistnostService
{
    private Cache $cache;

    private const URL_MISTNOST = 'http://chat.chatujme.cz/room-info?room_id=%s';
    private const CACHE_EXPIRATION = '5 minutes';

    public function __construct(
        private readonly HttpClientService $httpClient,
        Storage $storage
    ) {
        $this->cache = new Cache($storage, self::class);
    }

    /**
     * Získá informace o místnosti
     *
     * @return array{data: array<string, mixed>, cached?: bool}
     */
    public function getMistnost(string $id): array
    {
        $cacheKey = $this->getCacheKey($id);

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            $cached['cached'] = true;
            return $cached;
        }

        try {
            $url = sprintf(self::URL_MISTNOST, $id);
            $html = $this->httpClient->get($url);

            // Kontrola, zda místnost existuje
            if (preg_match('#>Redirect<#', $html)) {
                return [
                    'data' => [
                        'message' => "Místnost {$id} nebyla nalezena",
                        'code' => 404,
                    ],
                ];
            }

            $result = $this->parseMistnost($html);

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            return [
                'data' => [
                    'message' => "Nepodařilo se načíst informace o místnosti: {$e->getMessage()}",
                    'code' => 500,
                ],
            ];
        }
    }

    /**
     * Parsuje HTML stránku s informacemi o místnosti
     *
     * @return array{data: array<string, mixed>}
     */
    private function parseMistnost(string $html): array
    {
        // Odstraníme newliny pro snazší parsování
        $html = str_replace("\n", '', $html);

        $data = [];

        // Popis
        if (preg_match('#<td>Popis</td>\s*<td>(.*?)</td>#i', $html, $matches)) {
            $data['popis'] = $matches[1];
        }

        // Název místnosti
        if (preg_match('#Místnost: <strong>(.+?)\s*<br>#i', $html, $matches)) {
            $data['mistnost'] = $matches[1];
        }

        // Stálí správci
        if (preg_match('#<td>Stálý správce</td>\s*<td>(.*?)</td>#i', $html, $matches)) {
            preg_match_all('#<a target="_blank" href="http://profil.chatujme.cz/(.*?)">#', $matches[1], $ssMatches);
            $data['ss'] = $ssMatches[1];
        } else {
            $data['ss'] = [];
        }

        // Celkový čas místnosti
        if (preg_match('#<td>Celkový čas místnosti</td>\s*<td>(.+?) hod</td>#i', $html, $matches)) {
            $data['celkovy-cas'] = str_replace(',', '', $matches[1]);
        }

        // Aktuální den a prochatováno
        preg_match_all('#<td class="activeDay">(.+?)</td>#i', $html, $matches);
        if (isset($matches[1][0])) {
            $data['aktualni-den'] = $matches[1][0];
        }
        if (isset($matches[1][1])) {
            $data['aktualne-prochatovano'] = $matches[1][1];
        }

        // Web místnosti
        $data['web'] = '';
        if (preg_match('#<td>Web místnosti</td>.*?href="([^"]*)"#i', $html, $matches)) {
            $data['web'] = $matches[1];
        }

        // Limit místnosti
        $data['limit'] = ['mistnost-limit' => false];
        if (preg_match('#<td>Kategorie :</td>.*?<strong>\(.+glyphicon-(ok|warning)-sign.*?limit (\d+) hod.*?</strong></td>#i', $html, $matches)) {
            $data['limit'] = [
                'mistnost-limit' => true,
                'splneny-limit' => $matches[1] === 'ok',
                'limit-hodin' => $matches[2],
            ];
        }

        // Datum založení
        if (preg_match('#<td>Založeno</td>.*?<strong>\s*\|\s*([^\(]*)\s*\(#i', $html, $matches)) {
            $data['zalozeno'] = $matches[1];
        }

        return ['data' => $data];
    }

    private function getCacheKey(string $id): string
    {
        return 'mistnost_' . date('Y-m-d_H:i') . '_' . $id;
    }
}
