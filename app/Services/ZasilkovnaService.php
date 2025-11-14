<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;
use Nette\Caching\Storage;

/**
 * Service pro sledování zásilek přes Zásilkovnu (Packeta)
 *
 * Zásilkovna je česká síť výdejních míst pro online nakupování.
 * Tento service umožňuje sledovat stav balíků.
 */
final class ZasilkovnaService
{
    private Cache $cache;

    private const URL_TRACKING_API = 'https://tracking.packeta.com/api/v1/tracking';
    private const CACHE_EXPIRATION = '1 hour'; // Stav balíků se mění relativně často

    public function __construct(
        private readonly HttpClientService $httpClient,
        Storage $storage
    ) {
        $this->cache = new Cache($storage, self::class);
    }

    /**
     * Sleduje balík podle ID
     *
     * @param string $packageId ID balíku (například Z123456789)
     * @return array{data: array<string, mixed>}
     * @throws \RuntimeException
     */
    public function trackPackage(string $packageId): array
    {
        if (empty($packageId)) {
            throw new \RuntimeException('ID balíku je povinné');
        }

        // Normalizujeme ID balíku - odstraníme mezery
        $packageId = trim($packageId);

        $cacheKey = 'zasilkovna_track_' . $packageId;

        $cached = $this->cache->load($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $url = self::URL_TRACKING_API . '/' . urlencode($packageId);

            try {
                $data = $this->httpClient->getJson($url, [
                    'headers' => [
                        'Accept: application/json',
                    ],
                ]);
            } catch (\RuntimeException $e) {
                // Pokud API vrátí 404, balík nebyl nalezen
                if (str_contains($e->getMessage(), '404')) {
                    throw new \RuntimeException("Balík s ID '{$packageId}' nebyl nalezen");
                }
                throw $e;
            }

            $result = ['data' => $this->formatTrackingData($data)];

            $this->cache->save($cacheKey, $result, [
                Cache::EXPIRE => self::CACHE_EXPIRATION,
            ]);

            return $result;
        } catch (\RuntimeException $e) {
            // Propagujeme RuntimeException dál
            throw $e;
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se získat informace o balíku: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Formátuje data o sledování balíku do čitelné podoby
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function formatTrackingData(array $data): array
    {
        // Základní informace o balíku
        $result = [
            'package_id' => $data['packageId'] ?? $data['id'] ?? 'N/A',
            'status' => $data['status'] ?? $data['statusCode'] ?? 'N/A',
            'status_text' => $data['statusText'] ?? $data['statusMessage'] ?? 'N/A',
        ];

        // Informace o příjemci
        if (isset($data['recipient'])) {
            $result['recipient'] = [
                'name' => $data['recipient']['name'] ?? 'N/A',
                'city' => $data['recipient']['city'] ?? 'N/A',
            ];
        }

        // Informace o výdejním místě
        if (isset($data['pickupPoint'])) {
            $result['pickup_point'] = [
                'name' => $data['pickupPoint']['name'] ?? 'N/A',
                'city' => $data['pickupPoint']['city'] ?? 'N/A',
                'address' => $data['pickupPoint']['address'] ?? 'N/A',
            ];
        }

        // Historie stavů
        if (isset($data['history']) && is_array($data['history'])) {
            $result['history'] = array_map(function($item) {
                return [
                    'date' => $item['date'] ?? $item['timestamp'] ?? 'N/A',
                    'status' => $item['status'] ?? $item['statusText'] ?? 'N/A',
                    'location' => $item['location'] ?? null,
                ];
            }, $data['history']);
        }

        // Události (events)
        if (isset($data['events']) && is_array($data['events'])) {
            $result['events'] = array_map(function($event) {
                return [
                    'date' => $event['date'] ?? $event['datetime'] ?? 'N/A',
                    'status' => $event['status'] ?? $event['name'] ?? 'N/A',
                    'description' => $event['description'] ?? null,
                ];
            }, $data['events']);
        }

        // Datum doručení
        if (isset($data['deliveryDate'])) {
            $result['delivery_date'] = $data['deliveryDate'];
        }

        // Odhadované datum doručení
        if (isset($data['estimatedDeliveryDate'])) {
            $result['estimated_delivery_date'] = $data['estimatedDeliveryDate'];
        }

        return $result;
    }
}
