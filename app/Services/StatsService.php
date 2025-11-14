<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;

/**
 * Service pro správu a agregaci statistik API requestů
 */
final class StatsService
{
    private Cache $cache;
    private const STATS_FILE = __DIR__ . '/../../temp/stats.json';
    private const CACHE_EXPIRATION = '5 minutes';

    public function __construct(
        Storage $storage
    ) {
        $this->cache = new Cache($storage, self::class);
    }

    /**
     * Zaloguje API request
     */
    public function logRequest(string $path, string $method, int $statusCode, float $responseTime): void
    {
        $stats = $this->loadStats();

        // Increment total requests
        $stats['total_requests'] = ($stats['total_requests'] ?? 0) + 1;

        // Track by endpoint
        $endpoint = $method . ' ' . $path;
        if (!isset($stats['endpoints'][$endpoint])) {
            $stats['endpoints'][$endpoint] = [
                'path' => $path,
                'method' => $method,
                'requests' => 0,
                'success' => 0,
                'errors' => 0,
                'total_time' => 0,
            ];
        }

        $stats['endpoints'][$endpoint]['requests']++;
        $stats['endpoints'][$endpoint]['total_time'] += $responseTime;

        if ($statusCode >= 200 && $statusCode < 400) {
            $stats['endpoints'][$endpoint]['success']++;
        } else {
            $stats['endpoints'][$endpoint]['errors']++;
        }

        // Track by hour
        $hour = date('Y-m-d H:00');
        if (!isset($stats['by_hour'][$hour])) {
            $stats['by_hour'][$hour] = 0;
        }
        $stats['by_hour'][$hour]++;

        // Keep only last 24 hours
        $stats['by_hour'] = array_slice($stats['by_hour'], -24, 24, true);

        $this->saveStats($stats);
        $this->cache->remove('aggregated_stats');
    }

    /**
     * Získá agregované statistiky
     */
    public function getStats(): array
    {
        return $this->cache->load('aggregated_stats', function () {
            $stats = $this->loadStats();

            if (empty($stats['endpoints'])) {
                return $this->getDefaultStats();
            }

            // Calculate aggregated data
            $totalRequests = $stats['total_requests'] ?? 0;
            $endpoints = $stats['endpoints'] ?? [];

            // Sort endpoints by requests
            uasort($endpoints, fn($a, $b) => $b['requests'] <=> $a['requests']);

            // Calculate success rate
            $totalSuccess = array_sum(array_column($endpoints, 'success'));
            $successRate = $totalRequests > 0 ? round(($totalSuccess / $totalRequests) * 100, 1) : 100;

            // Calculate average response time
            $totalTime = array_sum(array_column($endpoints, 'total_time'));
            $avgResponseTime = $totalRequests > 0 ? round($totalTime / $totalRequests) : 0;

            // Top 10 endpoints
            $topEndpoints = array_slice($endpoints, 0, 10);
            $topEndpointsData = [];
            foreach ($topEndpoints as $endpoint) {
                $percent = $totalRequests > 0 ? round(($endpoint['requests'] / $totalRequests) * 100, 1) : 0;
                $topEndpointsData[] = [
                    'path' => $endpoint['path'],
                    'method' => $endpoint['method'],
                    'requests' => $endpoint['requests'],
                    'percent' => $percent,
                ];
            }

            // Category stats (simplified)
            $categories = [
                [
                    'name' => 'All Endpoints',
                    'endpoints' => count($endpoints),
                    'requests' => $totalRequests,
                    'avg' => count($endpoints) > 0 ? round($totalRequests / count($endpoints)) : 0,
                ]
            ];

            return [
                'totalRequests' => $totalRequests,
                'successRate' => $successRate,
                'avgResponseTime' => $avgResponseTime,
                'topEndpoints' => $topEndpointsData,
                'categories' => $categories,
                'byHour' => $stats['by_hour'] ?? [],
            ];
        }, [
            Cache::Expire => self::CACHE_EXPIRATION,
        ]);
    }

    /**
     * Načte statistiky ze souboru
     */
    private function loadStats(): array
    {
        if (!file_exists(self::STATS_FILE)) {
            return [];
        }

        try {
            $content = FileSystem::read(self::STATS_FILE);
            return Json::decode($content, forceArrays: true);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Uloží statistiky do souboru
     */
    private function saveStats(array $stats): void
    {
        try {
            $dir = dirname(self::STATS_FILE);
            if (!is_dir($dir)) {
                FileSystem::createDir($dir);
            }

            FileSystem::write(self::STATS_FILE, Json::encode($stats, Json::PRETTY));
        } catch (\Exception $e) {
            // Silent fail - statistiky nejsou kritické
        }
    }

    /**
     * Vrátí defaultní statistiky když ještě nejsou žádná data
     */
    private function getDefaultStats(): array
    {
        return [
            'totalRequests' => 0,
            'successRate' => 100,
            'avgResponseTime' => 0,
            'topEndpoints' => [],
            'categories' => [],
            'byHour' => [],
        ];
    }

    /**
     * Resetuje statistiky
     */
    public function resetStats(): void
    {
        if (file_exists(self::STATS_FILE)) {
            FileSystem::delete(self::STATS_FILE);
        }
        $this->cache->remove('aggregated_stats');
    }
}
