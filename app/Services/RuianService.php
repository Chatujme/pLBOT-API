<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;
use Nette\Caching\Storage;

/**
 * Service pro získávání informací z RUIAN
 *
 * RUIAN (Registr územní identifikace, adres a nemovitostí) poskytuje informace
 * o adresách, územních prvcích a nemovitostech v České republice.
 */
final class RuianService
{
    private Cache $cache;

    private const URL_RUIAN_MAP_SERVER = 'https://ags.cuzk.cz/arcgis/rest/services/RUIAN/Vyhledavaci_sluzba_nad_daty_RUIAN/MapServer';
    private const CACHE_EXPIRATION = '1 week'; // Adresy se mění velmi zřídka

    // Layer IDs z RUIAN API
    private const LAYER_ADRESNI_MISTO = 1; // Adresní místa
    private const LAYER_ULICE = 4; // Ulice
    private const LAYER_OBEC = 12; // Obce
    private const LAYER_CAST_OBCE = 11; // Části obce
    private const LAYER_PARCELA = 5; // Parcely
    private const LAYER_STAVEBNI_OBJEKT = 3; // Stavební objekty

    public function __construct(
        private readonly HttpClientService $httpClient,
        Storage $storage
    ) {
        $this->cache = new Cache($storage, self::class);
    }

    /**
     * Vyhledá obce podle názvu
     *
     * @param string $nazev Název obce nebo jeho část
     * @param int $limit Maximální počet výsledků (výchozí 10)
     * @return array{data: array<int, array<string, mixed>>}
     * @throws \RuntimeException
     */
    public function vyhledatObce(string $nazev, int $limit = 10): array
    {
        if (empty($nazev) || strlen($nazev) < 2) {
            throw new \RuntimeException('Název musí obsahovat alespoň 2 znaky');
        }

        $cacheKey = 'ruian_obec_' . md5($nazev) . '_' . $limit;

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $url = self::URL_RUIAN_MAP_SERVER . '/' . self::LAYER_OBEC . '/query';
            $queryParams = [
                'where' => "nazev LIKE '%" . str_replace("'", "''", $nazev) . "%'",
                'outFields' => '*',
                'f' => 'json',
                'returnGeometry' => 'false',
                'resultRecordCount' => (string) $limit,
            ];

            $fullUrl = $url . '?' . http_build_query($queryParams);
            $data = $this->httpClient->getJson($fullUrl);

            if (!isset($data['features'])) {
                return ['data' => []];
            }

            $obce = array_map([$this, 'formatObec'], $data['features']);
            $result = ['data' => $obce];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se vyhledat obce: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Vyhledá ulice podle názvu a obce
     *
     * @param string $nazev Název ulice
     * @param string|null $obec Název obce (volitelné)
     * @param int $limit Maximální počet výsledků
     * @return array{data: array<int, array<string, mixed>>}
     * @throws \RuntimeException
     */
    public function vyhledatUlice(string $nazev, ?string $obec = null, int $limit = 10): array
    {
        if (empty($nazev) || strlen($nazev) < 2) {
            throw new \RuntimeException('Název ulice musí obsahovat alespoň 2 znaky');
        }

        $cacheKey = 'ruian_ulice_' . md5($nazev . ($obec ?? '')) . '_' . $limit;

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $url = self::URL_RUIAN_MAP_SERVER . '/' . self::LAYER_ULICE . '/query';

            // Připravíme WHERE klauzuli
            $where = "nazev LIKE '%" . str_replace("'", "''", $nazev) . "%'";

            $queryParams = [
                'where' => $where,
                'outFields' => '*',
                'f' => 'json',
                'returnGeometry' => 'false',
                'resultRecordCount' => (string) $limit,
            ];

            $fullUrl = $url . '?' . http_build_query($queryParams);
            $data = $this->httpClient->getJson($fullUrl);

            if (!isset($data['features'])) {
                return ['data' => []];
            }

            $ulice = array_map([$this, 'formatUlice'], $data['features']);
            $result = ['data' => $ulice];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se vyhledat ulice: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Vyhledá adresní místa (kompletní adresy)
     *
     * @param string $query Hledaný výraz (část adresy)
     * @param int $limit Maximální počet výsledků
     * @return array{data: array<int, array<string, mixed>>}
     * @throws \RuntimeException
     */
    public function vyhledatAdresy(string $query, int $limit = 10): array
    {
        if (empty($query) || strlen($query) < 3) {
            throw new \RuntimeException('Hledaný výraz musí obsahovat alespoň 3 znaky');
        }

        $cacheKey = 'ruian_adresa_' . md5($query) . '_' . $limit;

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            // Použijeme find operaci pro vyhledávání napříč poli
            $url = self::URL_RUIAN_MAP_SERVER . '/find';
            $queryParams = [
                'searchText' => $query,
                'layers' => (string) self::LAYER_ADRESNI_MISTO,
                'f' => 'json',
                'returnGeometry' => 'false',
                'contains' => 'true',
            ];

            $fullUrl = $url . '?' . http_build_query($queryParams);
            $data = $this->httpClient->getJson($fullUrl);

            if (!isset($data['results']) || count($data['results']) === 0) {
                // Pokud find nenašel nic, zkusíme query endpoint
                return $this->vyhledatAdresyQuery($query, $limit);
            }

            $results = array_slice($data['results'], 0, $limit);
            $adresy = array_map([$this, 'formatAdresniMisto'], $results);
            $result = ['data' => $adresy];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se vyhledat adresy: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Pomocná metoda pro vyhledávání adres pomocí query endpoint
     */
    private function vyhledatAdresyQuery(string $query, int $limit): array
    {
        $url = self::URL_RUIAN_MAP_SERVER . '/' . self::LAYER_ADRESNI_MISTO . '/query';
        $queryParams = [
            'where' => "1=1", // Vyhledáme všechny záznamy, filtrování bude na straně klienta
            'outFields' => '*',
            'f' => 'json',
            'returnGeometry' => 'false',
            'resultRecordCount' => (string) $limit,
        ];

        $fullUrl = $url . '?' . http_build_query($queryParams);
        $data = $this->httpClient->getJson($fullUrl);

        if (!isset($data['features'])) {
            return ['data' => []];
        }

        $adresy = array_map(function($feature) {
            return $this->formatAdresniMistoFromFeature($feature);
        }, $data['features']);

        return ['data' => $adresy];
    }

    /**
     * Validuje českou adresu
     *
     * @param string $ulice Název ulice
     * @param string $cisloPopisne Číslo popisné
     * @param string $obec Název obce
     * @param string|null $psc PSČ (volitelné)
     * @return array{data: array<string, mixed>}
     * @throws \RuntimeException
     */
    public function validateAdresa(string $ulice, string $cisloPopisne, string $obec, ?string $psc = null): array
    {
        $cacheKey = 'ruian_validate_' . md5($ulice . $cisloPopisne . $obec . ($psc ?? ''));

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            // Nejdřív vyhledáme obec
            $obceResult = $this->vyhledatObce($obec, 1);
            if (empty($obceResult['data'])) {
                return [
                    'data' => [
                        'valid' => false,
                        'message' => "Obec '{$obec}' nebyla nalezena",
                    ],
                ];
            }

            // Pak vyhledáme ulici v této obci
            $uliceResult = $this->vyhledatUlice($ulice, $obec, 5);
            if (empty($uliceResult['data'])) {
                return [
                    'data' => [
                        'valid' => false,
                        'message' => "Ulice '{$ulice}' nebyla nalezena v obci '{$obec}'",
                    ],
                ];
            }

            $result = [
                'data' => [
                    'valid' => true,
                    'message' => 'Adresa byla nalezena v RUIAN',
                    'obec' => $obceResult['data'][0],
                    'ulice' => $uliceResult['data'][0],
                ],
            ];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se validovat adresu: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Formátuje data o obci do čitelné podoby
     *
     * @param array<string, mixed> $feature
     * @return array<string, mixed>
     */
    private function formatObec(array $feature): array
    {
        $attr = $feature['attributes'] ?? [];

        return [
            'kod' => $attr['kod'] ?? 'N/A',
            'nazev' => $attr['nazev'] ?? 'N/A',
            'okres' => $attr['okres'] ?? null,
            'nuts_lau' => $attr['nutslau'] ?? null,
        ];
    }

    /**
     * Formátuje data o ulici do čitelné podoby
     *
     * @param array<string, mixed> $feature
     * @return array<string, mixed>
     */
    private function formatUlice(array $feature): array
    {
        $attr = $feature['attributes'] ?? [];

        return [
            'kod' => $attr['kod'] ?? 'N/A',
            'nazev' => $attr['nazev'] ?? 'N/A',
            'obec_kod' => $attr['obec'] ?? null,
        ];
    }

    /**
     * Formátuje data o adresním místě z find výsledku
     *
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function formatAdresniMisto(array $result): array
    {
        $attr = $result['attributes'] ?? [];

        return [
            'value' => $result['value'] ?? 'N/A',
            'layer_name' => $result['layerName'] ?? 'N/A',
            'kod' => $attr['kod'] ?? null,
        ];
    }

    /**
     * Formátuje data o adresním místě z query feature
     *
     * @param array<string, mixed> $feature
     * @return array<string, mixed>
     */
    private function formatAdresniMistoFromFeature(array $feature): array
    {
        $attr = $feature['attributes'] ?? [];

        return [
            'kod' => $attr['kod'] ?? 'N/A',
            'ulice_kod' => $attr['ulice'] ?? null,
            'cislo_domovni' => $attr['cislodomovni'] ?? null,
            'cislo_orientacni' => $attr['cisloorientacni'] ?? null,
            'psc' => $attr['psc'] ?? null,
        ];
    }
}
