<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;

/**
 * Service pro získávání kurzů měn z ČNB
 */
final class CnbKurzyService
{
    private const URL_CNB_DAILY = 'https://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt';
    private const CACHE_EXPIRATION = '12 hours'; // ČNB aktualizuje kurzy 1x denně po 14:30

    public function __construct(
        private readonly HttpClientService $httpClient,
        private readonly Cache $cache
    ) {
    }

    /**
     * Získá kurzy všech měn
     *
     * @return array{data: array<string, array<string, mixed>>}
     * @throws \RuntimeException
     */
    public function getAllKurzy(): array
    {
        $cacheKey = 'cnb_kurzy_' . date('Y-m-d');

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $txt = $this->httpClient->get(self::URL_CNB_DAILY);
            $kurzy = $this->parseTxtFormat($txt);

            $result = ['data' => $kurzy];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat kurzy ČNB: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Získá kurz konkrétní měny
     *
     * @return array{data: array<string, mixed>}
     * @throws \RuntimeException
     */
    public function getKurz(string $mena): array
    {
        $mena = strtoupper($mena);
        $allKurzy = $this->getAllKurzy();

        if (!isset($allKurzy['data'][$mena])) {
            throw new \RuntimeException("Měna {$mena} nebyla nalezena v kurzovním lístku");
        }

        return ['data' => $allKurzy['data'][$mena]];
    }

    /**
     * Převede částku z jedné měny do druhé
     *
     * @return array{data: array<string, mixed>}
     */
    public function convertCurrency(float $amount, string $from, string $to): array
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === 'CZK' && $to === 'CZK') {
            return ['data' => [
                'amount' => $amount,
                'from' => $from,
                'to' => $to,
                'result' => $amount,
                'rate' => 1.0,
            ]];
        }

        $allKurzy = $this->getAllKurzy();

        // Pokud převádíme z CZK do jiné měny
        if ($from === 'CZK') {
            if (!isset($allKurzy['data'][$to])) {
                throw new \RuntimeException("Měna {$to} nebyla nalezena");
            }

            $targetRate = $allKurzy['data'][$to]['kurz'];
            $targetAmount = $allKurzy['data'][$to]['mnozstvi'];
            $result = ($amount / $targetRate) * $targetAmount;

            return ['data' => [
                'amount' => $amount,
                'from' => $from,
                'to' => $to,
                'result' => round($result, 2),
                'rate' => $targetRate,
            ]];
        }

        // Pokud převádíme z jiné měny do CZK
        if ($to === 'CZK') {
            if (!isset($allKurzy['data'][$from])) {
                throw new \RuntimeException("Měna {$from} nebyla nalezena");
            }

            $sourceRate = $allKurzy['data'][$from]['kurz'];
            $sourceAmount = $allKurzy['data'][$from]['mnozstvi'];
            $result = ($amount / $sourceAmount) * $sourceRate;

            return ['data' => [
                'amount' => $amount,
                'from' => $from,
                'to' => $to,
                'result' => round($result, 2),
                'rate' => $sourceRate,
            ]];
        }

        // Převod mezi dvěma cizími měnami (přes CZK)
        if (!isset($allKurzy['data'][$from]) || !isset($allKurzy['data'][$to])) {
            throw new \RuntimeException("Jedna z měn nebyla nalezena");
        }

        $sourceRate = $allKurzy['data'][$from]['kurz'];
        $sourceAmount = $allKurzy['data'][$from]['mnozstvi'];
        $targetRate = $allKurzy['data'][$to]['kurz'];
        $targetAmount = $allKurzy['data'][$to]['mnozstvi'];

        $inCzk = ($amount / $sourceAmount) * $sourceRate;
        $result = ($inCzk / $targetRate) * $targetAmount;

        return ['data' => [
            'amount' => $amount,
            'from' => $from,
            'to' => $to,
            'result' => round($result, 2),
            'rate' => round($sourceRate / $targetRate, 4),
        ]];
    }

    /**
     * Parsuje TXT formát ČNB
     *
     * @return array<string, array<string, mixed>>
     */
    private function parseTxtFormat(string $txt): array
    {
        $lines = explode("\n", trim($txt));

        // První řádek je datum a #číslo
        $datum = $lines[0] ?? '';

        // Druhý řádek je hlavička
        // země|měna|množství|kód|kurz

        $kurzy = [];

        // Zpracujeme data od 3. řádku
        for ($i = 2; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) {
                continue;
            }

            $parts = explode('|', $line);
            if (count($parts) !== 5) {
                continue;
            }

            [$zeme, $mena, $mnozstvi, $kod, $kurzStr] = $parts;

            // Převedeme kurz (nahradíme čárku tečkou)
            $kurz = (float) str_replace(',', '.', $kurzStr);

            $kurzy[$kod] = [
                'zeme' => $zeme,
                'mena' => $mena,
                'mnozstvi' => (int) $mnozstvi,
                'kod' => $kod,
                'kurz' => $kurz,
            ];
        }

        return $kurzy;
    }
}
