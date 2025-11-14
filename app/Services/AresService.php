<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;

/**
 * Service pro získávání informací o firmách z registru ARES
 *
 * ARES (Administrativní Registr Ekonomických Subjektů) poskytuje informace
 * o všech ekonomických subjektech registrovaných v České republice.
 */
final class AresService
{
    private const URL_ARES_API_BASE = 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty';
    private const URL_ARES_API_SEARCH = 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/vyhledat';
    private const CACHE_EXPIRATION = '1 month'; // Data o firmách se mění velmi zřídka

    public function __construct(
        private readonly HttpClientService $httpClient,
        private readonly Cache $cache
    ) {
    }

    /**
     * Vyhledá firmu podle IČO (identifikační číslo organizace)
     *
     * @param string $ico IČO firmy (8 číslic)
     * @return array{data: array<string, mixed>}
     * @throws \RuntimeException
     */
    public function getFirmaByIco(string $ico): array
    {
        // Normalizujeme IČO - odstraníme mezery a nuly na začátku
        $ico = ltrim(str_replace(' ', '', $ico), '0');

        if (empty($ico) || !ctype_digit($ico)) {
            throw new \RuntimeException('IČO musí obsahovat pouze číslice');
        }

        // Doplníme IČO na 8 číslic
        $ico = str_pad($ico, 8, '0', STR_PAD_LEFT);

        $cacheKey = 'ares_ico_' . $ico;

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            // Nové API používá IČO v cestě, ne jako query parametr
            $url = self::URL_ARES_API_BASE . '/' . $ico;
            $data = $this->httpClient->getJson($url);

            if (empty($data)) {
                throw new \RuntimeException("Firma s IČO {$ico} nebyla nalezena");
            }

            $result = ['data' => $this->formatSubjektData($data)];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat data z ARES: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Vyhledá firmy podle názvu
     *
     * @param string $nazev Název firmy nebo jeho část
     * @param int $limit Maximální počet výsledků (výchozí 10)
     * @return array{data: array<int, array<string, mixed>>}
     * @throws \RuntimeException
     */
    public function vyhledatFirmy(string $nazev, int $limit = 10): array
    {
        if (empty($nazev) || strlen($nazev) < 3) {
            throw new \RuntimeException('Název musí obsahovat alespoň 3 znaky');
        }

        $cacheKey = 'ares_nazev_' . md5($nazev) . '_' . $limit;

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            // Pro vyhledávání podle názvu musíme použít POST endpoint
            $postData = [
                'obchodniJmeno' => $nazev,
                'start' => 0,
                'pocet' => $limit,
            ];

            $data = $this->httpClient->postJson(self::URL_ARES_API_SEARCH, $postData);

            if (!isset($data['ekonomickeSubjekty']) || empty($data['ekonomickeSubjekty'])) {
                return ['data' => []];
            }

            $subjekty = array_slice($data['ekonomickeSubjekty'], 0, $limit);
            $result = ['data' => array_map([$this, 'formatSubjektData'], $subjekty)];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se vyhledat firmy: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Formátuje data o ekonomickém subjektu do čitelné podoby
     *
     * @param array<string, mixed> $subjekt
     * @return array<string, mixed>
     */
    private function formatSubjektData(array $subjekt): array
    {
        $ico = $subjekt['ico'] ?? 'N/A';
        $obchodniJmeno = $subjekt['obchodniJmeno'] ?? 'N/A';

        // Adresa
        $adresa = 'N/A';
        if (isset($subjekt['sidlo'])) {
            $sidlo = $subjekt['sidlo'];
            $adresaParts = [];

            if (isset($sidlo['textovaAdresa'])) {
                $adresa = $sidlo['textovaAdresa'];
            } else {
                if (isset($sidlo['nazevUlice'])) {
                    $adresaParts[] = $sidlo['nazevUlice'];
                    if (isset($sidlo['cisloDomovni'])) {
                        $adresaParts[count($adresaParts) - 1] .= ' ' . $sidlo['cisloDomovni'];
                        if (isset($sidlo['cisloOrientacni'])) {
                            $adresaParts[count($adresaParts) - 1] .= '/' . $sidlo['cisloOrientacni'];
                        }
                    }
                }

                if (isset($sidlo['nazevObce'])) {
                    $obecPart = $sidlo['nazevObce'];
                    if (isset($sidlo['psc'])) {
                        $obecPart .= ', ' . $sidlo['psc'];
                    }
                    $adresaParts[] = $obecPart;
                }

                if (!empty($adresaParts)) {
                    $adresa = implode(', ', $adresaParts);
                }
            }
        }

        // DIČ
        $dic = 'N/A';
        if (isset($subjekt['dic'])) {
            $dic = $subjekt['dic'];
        }

        // Právní forma
        $pravniForma = 'N/A';
        if (isset($subjekt['pravniForma'])) {
            $pravniForma = $subjekt['pravniForma']['nazev'] ?? $subjekt['pravniForma']['kod'] ?? 'N/A';
        }

        // Stav subjektu
        $stav = 'N/A';
        if (isset($subjekt['stavZdrojeVr'])) {
            $stav = $subjekt['stavZdrojeVr'];
        } elseif (isset($subjekt['czNace'])) {
            $stav = 'Aktivní';
        }

        // Datum vzniku
        $datumVzniku = 'N/A';
        if (isset($subjekt['datumVzniku'])) {
            $datumVzniku = $subjekt['datumVzniku'];
        }

        return [
            'ico' => $ico,
            'dic' => $dic,
            'obchodni_jmeno' => $obchodniJmeno,
            'pravni_forma' => $pravniForma,
            'adresa' => $adresa,
            'stav' => $stav,
            'datum_vzniku' => $datumVzniku,
        ];
    }
}
