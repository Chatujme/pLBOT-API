<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Caching\Cache;
use Nette\Caching\Storage;
use PDO;

/**
 * Service for API request statistics using SQLite
 */
final class StatsService
{
    private Cache $cache;
    private PDO $pdo;
    private const CACHE_EXPIRATION = '1 minute';

    public function __construct(
        Storage $storage,
        DatabaseService $database
    ) {
        $this->cache = new Cache($storage, self::class);
        $this->pdo = $database->getPdo();
    }

    /**
     * Log API request with response time
     */
    public function logRequest(string $path, string $method, int $statusCode, float $responseTime): void
    {
        try {
            $this->pdo->beginTransaction();

            // Insert into request log
            $stmt = $this->pdo->prepare("
                INSERT INTO stats_requests (path, method, status_code, response_time, created_at)
                VALUES (?, ?, ?, ?, datetime('now'))
            ");
            $stmt->execute([$path, $method, $statusCode, $responseTime]);

            // Update or insert endpoint stats
            $isSuccess = $statusCode >= 200 && $statusCode < 400;

            $stmt = $this->pdo->prepare("
                INSERT INTO stats_endpoints (path, method, requests, success, errors, total_time, avg_time, min_time, max_time, last_request_at)
                VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, datetime('now'))
                ON CONFLICT(path, method) DO UPDATE SET
                    requests = requests + 1,
                    success = success + excluded.success,
                    errors = errors + excluded.errors,
                    total_time = total_time + excluded.total_time,
                    avg_time = (total_time + excluded.total_time) / (requests + 1),
                    min_time = CASE WHEN min_time IS NULL OR excluded.min_time < min_time THEN excluded.min_time ELSE min_time END,
                    max_time = CASE WHEN max_time IS NULL OR excluded.max_time > max_time THEN excluded.max_time ELSE max_time END,
                    last_request_at = datetime('now')
            ");
            $stmt->execute([
                $path,
                $method,
                $isSuccess ? 1 : 0,
                $isSuccess ? 0 : 1,
                $responseTime,
                $responseTime,
                $responseTime,
                $responseTime
            ]);

            // Update hourly stats
            $hour = date('Y-m-d H:00');
            $stmt = $this->pdo->prepare("
                INSERT INTO stats_hourly (hour, requests)
                VALUES (?, 1)
                ON CONFLICT(hour) DO UPDATE SET
                    requests = requests + 1
            ");
            $stmt->execute([$hour]);

            $this->pdo->commit();
            $this->cache->remove('aggregated_stats');

        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // Silent fail - stats are not critical
        }
    }

    /**
     * Get aggregated statistics
     */
    public function getStats(): array
    {
        return $this->cache->load('aggregated_stats', function () {
            try {
                // Get total requests
                $stmt = $this->pdo->query("SELECT SUM(requests) as total FROM stats_endpoints");
                $totalRequests = (int) ($stmt->fetchColumn() ?: 0);

                if ($totalRequests === 0) {
                    return $this->getDefaultStats();
                }

                // Get success rate
                $stmt = $this->pdo->query("SELECT SUM(success) as total_success, SUM(errors) as total_errors FROM stats_endpoints");
                $row = $stmt->fetch();
                $totalSuccess = (int) ($row['total_success'] ?? 0);
                $successRate = $totalRequests > 0 ? round(($totalSuccess / $totalRequests) * 100, 1) : 100;

                // Get average response time
                $stmt = $this->pdo->query("SELECT SUM(total_time) / SUM(requests) as avg FROM stats_endpoints WHERE requests > 0");
                $avgResponseTime = round((float) ($stmt->fetchColumn() ?: 0), 2);

                // Get top endpoints with detailed stats
                $stmt = $this->pdo->query("
                    SELECT path, method, requests, avg_time, min_time, max_time, success, errors, last_request_at
                    FROM stats_endpoints
                    ORDER BY requests DESC
                    LIMIT 10
                ");
                $topEndpoints = [];
                while ($row = $stmt->fetch()) {
                    $percent = $totalRequests > 0 ? round(($row['requests'] / $totalRequests) * 100, 1) : 0;
                    $topEndpoints[] = [
                        'path' => $row['path'],
                        'method' => $row['method'],
                        'requests' => (int) $row['requests'],
                        'percent' => $percent,
                        'avg_time' => round((float) $row['avg_time'], 2),
                        'min_time' => $row['min_time'] !== null ? round((float) $row['min_time'], 2) : null,
                        'max_time' => $row['max_time'] !== null ? round((float) $row['max_time'], 2) : null,
                        'success' => (int) $row['success'],
                        'errors' => (int) $row['errors'],
                        'last_request' => $row['last_request_at'],
                    ];
                }

                // Get hourly stats (last 24 hours)
                $stmt = $this->pdo->query("
                    SELECT hour, requests
                    FROM stats_hourly
                    WHERE hour >= datetime('now', '-24 hours')
                    ORDER BY hour ASC
                ");
                $byHour = [];
                while ($row = $stmt->fetch()) {
                    $byHour[$row['hour']] = (int) $row['requests'];
                }

                // Get endpoint count
                $stmt = $this->pdo->query("SELECT COUNT(*) FROM stats_endpoints");
                $endpointCount = (int) $stmt->fetchColumn();

                // Get category stats (by first path segment)
                $stmt = $this->pdo->query("
                    SELECT
                        CASE
                            WHEN path LIKE '/admin%' THEN 'Admin'
                            WHEN path LIKE '/ares%' THEN 'ARES'
                            WHEN path LIKE '/cnb%' THEN 'CNB'
                            WHEN path LIKE '/crypto%' THEN 'Crypto'
                            WHEN path LIKE '/countries%' THEN 'Countries'
                            WHEN path LIKE '/dog%' OR path LIKE '/fox%' OR path LIKE '/catfact%' THEN 'Animals'
                            WHEN path LIKE '/joke%' OR path LIKE '/chucknorris%' OR path LIKE '/advice%' THEN 'Fun'
                            WHEN path LIKE '/hash%' OR path LIKE '/uuid%' OR path LIKE '/qr%' OR path LIKE '/url%' THEN 'Utility'
                            WHEN path LIKE '/image%' THEN 'Image'
                            WHEN path LIKE '/news%' THEN 'News'
                            ELSE 'Other'
                        END as category,
                        COUNT(*) as endpoints,
                        SUM(requests) as requests
                    FROM stats_endpoints
                    GROUP BY category
                    ORDER BY requests DESC
                ");
                $categories = [];
                while ($row = $stmt->fetch()) {
                    $categories[] = [
                        'name' => $row['category'],
                        'endpoints' => (int) $row['endpoints'],
                        'requests' => (int) $row['requests'],
                        'avg' => $row['endpoints'] > 0 ? round($row['requests'] / $row['endpoints']) : 0,
                    ];
                }

                return [
                    'totalRequests' => $totalRequests,
                    'successRate' => $successRate,
                    'avgResponseTime' => $avgResponseTime,
                    'endpointCount' => $endpointCount,
                    'topEndpoints' => $topEndpoints,
                    'categories' => $categories,
                    'byHour' => $byHour,
                ];

            } catch (\Exception $e) {
                return $this->getDefaultStats();
            }
        }, [
            Cache::Expire => self::CACHE_EXPIRATION,
        ]);
    }

    /**
     * Get detailed stats for a specific endpoint
     */
    public function getEndpointStats(string $path, string $method = 'GET'): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM stats_endpoints WHERE path = ? AND method = ?
            ");
            $stmt->execute([$path, $method]);
            $row = $stmt->fetch();

            if (!$row) {
                return null;
            }

            // Get recent requests for this endpoint
            $stmt = $this->pdo->prepare("
                SELECT response_time, status_code, created_at
                FROM stats_requests
                WHERE path = ? AND method = ?
                ORDER BY created_at DESC
                LIMIT 100
            ");
            $stmt->execute([$path, $method]);
            $recentRequests = $stmt->fetchAll();

            return [
                'path' => $row['path'],
                'method' => $row['method'],
                'total_requests' => (int) $row['requests'],
                'success' => (int) $row['success'],
                'errors' => (int) $row['errors'],
                'avg_time' => round((float) $row['avg_time'], 2),
                'min_time' => $row['min_time'] !== null ? round((float) $row['min_time'], 2) : null,
                'max_time' => $row['max_time'] !== null ? round((float) $row['max_time'], 2) : null,
                'last_request' => $row['last_request_at'],
                'recent_requests' => $recentRequests,
            ];

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all endpoints with stats
     */
    public function getAllEndpointsStats(): array
    {
        try {
            $stmt = $this->pdo->query("
                SELECT path, method, requests, avg_time, min_time, max_time, success, errors, last_request_at
                FROM stats_endpoints
                ORDER BY path ASC
            ");
            $endpoints = [];
            while ($row = $stmt->fetch()) {
                $endpoints[] = [
                    'path' => $row['path'],
                    'method' => $row['method'],
                    'requests' => (int) $row['requests'],
                    'avg_time' => round((float) $row['avg_time'], 2),
                    'min_time' => $row['min_time'] !== null ? round((float) $row['min_time'], 2) : null,
                    'max_time' => $row['max_time'] !== null ? round((float) $row['max_time'], 2) : null,
                    'success' => (int) $row['success'],
                    'errors' => (int) $row['errors'],
                    'success_rate' => $row['requests'] > 0 ? round(($row['success'] / $row['requests']) * 100, 1) : 100,
                    'last_request' => $row['last_request_at'],
                ];
            }
            return $endpoints;

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Reset all statistics
     */
    public function resetStats(): void
    {
        try {
            $this->pdo->exec("DELETE FROM stats_endpoints");
            $this->pdo->exec("DELETE FROM stats_hourly");
            $this->pdo->exec("DELETE FROM stats_requests");
            $this->cache->remove('aggregated_stats');
        } catch (\Exception $e) {
            // Silent fail
        }
    }

    /**
     * Clean old request logs (keep last 7 days)
     */
    public function cleanOldLogs(): int
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM stats_requests
                WHERE created_at < datetime('now', '-7 days')
            ");
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getDefaultStats(): array
    {
        return [
            'totalRequests' => 0,
            'successRate' => 100,
            'avgResponseTime' => 0,
            'endpointCount' => 0,
            'topEndpoints' => [],
            'categories' => [],
            'byHour' => [],
        ];
    }
}
