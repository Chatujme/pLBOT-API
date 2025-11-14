<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;
use Nette\Caching\Storage;

/**
 * Service pro ověřování EU VAT čísel přes VIES systém
 *
 * VIES (VAT Information Exchange System) je oficiální EU služba
 * pro ověřování registrace k DPH v členských státech EU.
 */
final class VatService
{
    private Cache $cache;

    // VIES SOAP endpoint - oficiální EU služba
    private const VIES_WSDL = 'http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl';
    private const CACHE_EXPIRATION = '1 day';

    // Mapování kódů zemí na názvy
    private const EU_COUNTRIES = [
        'AT' => 'Austria',
        'BE' => 'Belgium',
        'BG' => 'Bulgaria',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'DE' => 'Germany',
        'DK' => 'Denmark',
        'EE' => 'Estonia',
        'EL' => 'Greece',
        'ES' => 'Spain',
        'FI' => 'Finland',
        'FR' => 'France',
        'HR' => 'Croatia',
        'HU' => 'Hungary',
        'IE' => 'Ireland',
        'IT' => 'Italy',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'LV' => 'Latvia',
        'MT' => 'Malta',
        'NL' => 'Netherlands',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'RO' => 'Romania',
        'SE' => 'Sweden',
        'SI' => 'Slovenia',
        'SK' => 'Slovakia',
    ];

    public function __construct(
        Storage $storage
    ) {
        $this->cache = new Cache($storage, self::class);
    }

    /**
     * Ověří platnost VAT čísla v EU systému VIES
     *
     * @param string $countryCode Kód země (např. CZ, DE, SK)
     * @param string $vatNumber VAT číslo bez kódu země
     * @return array{data: array<string, mixed>}
     * @throws \RuntimeException
     */
    public function validateVat(string $countryCode, string $vatNumber): array
    {
        $countryCode = strtoupper($countryCode);
        $vatNumber = $this->cleanVatNumber($vatNumber);

        if (!isset(self::EU_COUNTRIES[$countryCode])) {
            throw new \RuntimeException("Neplatný kód země. Použijte EU kód (např. CZ, DE, SK)");
        }

        $cacheKey = 'vat_' . $countryCode . '_' . $vatNumber;

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $client = new \SoapClient(self::VIES_WSDL, [
                'exceptions' => true,
                'connection_timeout' => 10,
            ]);

            $result = $client->checkVat([
                'countryCode' => $countryCode,
                'vatNumber' => $vatNumber,
            ]);

            $data = [
                'data' => [
                    'country_code' => $result->countryCode ?? $countryCode,
                    'vat_number' => $result->vatNumber ?? $vatNumber,
                    'request_date' => $result->requestDate ?? date('Y-m-d'),
                    'valid' => $result->valid ?? false,
                    'name' => $result->name ?? 'N/A',
                    'address' => $this->formatAddress($result->address ?? 'N/A'),
                    'full_vat' => $countryCode . $vatNumber,
                    'country_name' => self::EU_COUNTRIES[$countryCode],
                ],
            ];

            // Cache only valid results
            if ($data['data']['valid']) {
                $this->cache->save($cacheKey, $data, [
                    Cache::EXPIRE => self::CACHE_EXPIRATION,
                ]);
            }

            return $data;
        } catch (\SoapFault $e) {
            // VIES service může být dočasně nedostupný
            if (str_contains($e->getMessage(), 'MS_UNAVAILABLE') ||
                str_contains($e->getMessage(), 'SERVICE_UNAVAILABLE')) {
                throw new \RuntimeException('VIES služba je dočasně nedostupná. Zkuste to později.');
            }

            if (str_contains($e->getMessage(), 'INVALID_INPUT')) {
                throw new \RuntimeException('Neplatný formát VAT čísla');
            }

            throw new \RuntimeException("Nepodařilo se ověřit VAT: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Validuje a ověří VAT číslo zadané jako jeden řetězec (např. CZ12345678)
     *
     * @param string $fullVat Kompletní VAT číslo včetně kódu země
     * @return array{data: array<string, mixed>}
     */
    public function validateFullVat(string $fullVat): array
    {
        $fullVat = strtoupper(str_replace([' ', '-', '.'], '', $fullVat));

        // Extrakce kódu země (prvních 2 znaky)
        if (strlen($fullVat) < 3) {
            throw new \RuntimeException('VAT číslo musí obsahovat kód země a číslo (min. 3 znaky)');
        }

        $countryCode = substr($fullVat, 0, 2);
        $vatNumber = substr($fullVat, 2);

        return $this->validateVat($countryCode, $vatNumber);
    }

    /**
     * Získá seznam podporovaných EU zemí
     *
     * @return array{data: array<string, string>}
     */
    public function getSupportedCountries(): array
    {
        return ['data' => self::EU_COUNTRIES];
    }

    /**
     * Validuje formát VAT čísla bez dotazu na VIES
     *
     * @param string $countryCode Kód země
     * @param string $vatNumber VAT číslo
     * @return array{data: array<string, mixed>}
     */
    public function validateFormat(string $countryCode, string $vatNumber): array
    {
        $countryCode = strtoupper($countryCode);
        $vatNumber = $this->cleanVatNumber($vatNumber);

        if (!isset(self::EU_COUNTRIES[$countryCode])) {
            return [
                'data' => [
                    'valid_format' => false,
                    'error' => 'Neplatný kód země',
                    'country_code' => $countryCode,
                ],
            ];
        }

        $isValid = $this->checkVatFormat($countryCode, $vatNumber);

        return [
            'data' => [
                'valid_format' => $isValid,
                'country_code' => $countryCode,
                'vat_number' => $vatNumber,
                'full_vat' => $countryCode . $vatNumber,
                'country_name' => self::EU_COUNTRIES[$countryCode],
                'note' => $isValid
                    ? 'Formát je správný (VIES validace není zahrnuta)'
                    : 'Formát je neplatný pro tuto zemi',
            ],
        ];
    }

    /**
     * Očistí VAT číslo od mezer, pomlček a teček
     */
    private function cleanVatNumber(string $vatNumber): string
    {
        // Odstraníme prefix s kódem země, pokud je přítomen
        $vatNumber = preg_replace('/^[A-Z]{2}/', '', strtoupper($vatNumber));

        // Odstraníme mezery, pomlčky, tečky
        return str_replace([' ', '-', '.'], '', $vatNumber);
    }

    /**
     * Kontroluje základní formát VAT čísla pro danou zemi
     */
    private function checkVatFormat(string $countryCode, string $vatNumber): bool
    {
        $patterns = [
            'AT' => '/^U\d{8}$/',              // Rakousko: U + 8 číslic
            'BE' => '/^\d{10}$/',              // Belgie: 10 číslic
            'BG' => '/^\d{9,10}$/',            // Bulharsko: 9-10 číslic
            'CY' => '/^\d{8}[A-Z]$/',          // Kypr: 8 číslic + 1 písmeno
            'CZ' => '/^\d{8,10}$/',            // ČR: 8-10 číslic
            'DE' => '/^\d{9}$/',               // Německo: 9 číslic
            'DK' => '/^\d{8}$/',               // Dánsko: 8 číslic
            'EE' => '/^\d{9}$/',               // Estonsko: 9 číslic
            'EL' => '/^\d{9}$/',               // Řecko: 9 číslic
            'ES' => '/^[A-Z0-9]\d{7}[A-Z0-9]$/', // Španělsko: komplex
            'FI' => '/^\d{8}$/',               // Finsko: 8 číslic
            'FR' => '/^[A-Z0-9]{2}\d{9}$/',    // Francie: 2 znaky + 9 číslic
            'HR' => '/^\d{11}$/',              // Chorvatsko: 11 číslic
            'HU' => '/^\d{8}$/',               // Maďarsko: 8 číslic
            'IE' => '/^\d[A-Z0-9]\d{5}[A-Z]$/', // Irsko: komplex
            'IT' => '/^\d{11}$/',              // Itálie: 11 číslic
            'LT' => '/^\d{9}|\d{12}$/',        // Litva: 9 nebo 12 číslic
            'LU' => '/^\d{8}$/',               // Lucembursko: 8 číslic
            'LV' => '/^\d{11}$/',              // Lotyšsko: 11 číslic
            'MT' => '/^\d{8}$/',               // Malta: 8 číslic
            'NL' => '/^\d{9}B\d{2}$/',         // Nizozemsko: 9 číslic + B + 2 číslice
            'PL' => '/^\d{10}$/',              // Polsko: 10 číslic
            'PT' => '/^\d{9}$/',               // Portugalsko: 9 číslic
            'RO' => '/^\d{2,10}$/',            // Rumunsko: 2-10 číslic
            'SE' => '/^\d{12}$/',              // Švédsko: 12 číslic
            'SI' => '/^\d{8}$/',               // Slovinsko: 8 číslic
            'SK' => '/^\d{10}$/',              // Slovensko: 10 číslic
        ];

        if (!isset($patterns[$countryCode])) {
            return false;
        }

        return preg_match($patterns[$countryCode], $vatNumber) === 1;
    }

    /**
     * Formátuje adresu (odstraní přebytečné mezery a nové řádky)
     */
    private function formatAddress(string $address): string
    {
        // Nahradíme nové řádky čárkami
        $address = str_replace("\n", ', ', $address);

        // Odstraníme přebytečné mezery
        $address = preg_replace('/\s+/', ' ', $address);

        // Odstraníme duplicitní čárky
        $address = preg_replace('/,+/', ',', $address);

        return trim($address, ', ');
    }
}
