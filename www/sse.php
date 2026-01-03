<?php
/**
 * Server-Sent Events (SSE) endpoint for real-time dashboard updates
 *
 * Provides live statistics streaming every 2 seconds.
 * Connection auto-reconnects after 5 minutes.
 */

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Access-Control-Allow-Origin: *');
header('X-Accel-Buffering: no');

@ini_set('zlib.output_compression', '0');

while (ob_get_level()) {
    ob_end_flush();
}

set_time_limit(0);

echo "retry: 3000\n\n";
flush();

function sendEvent(string $event, array $data): void
{
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

sendEvent('connected', ['message' => 'SSE connection established', 'timestamp' => date('Y-m-d H:i:s')]);

// Database connection
$dbPath = __DIR__ . '/../data/plbot.db';
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

sendEvent('ready', ['status' => 'database_connected', 'timestamp' => date('Y-m-d H:i:s')]);

/**
 * Get live statistics from database
 */
function getLiveStats(PDO $pdo): array
{
    // Stats from last minute
    $stmt = $pdo->query("
        SELECT COUNT(*) as requests,
               SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as errors,
               ROUND(AVG(response_time), 2) as avg_time
        FROM stats_requests
        WHERE created_at >= datetime('now', '-1 minute')
    ");
    $lastMinute = $stmt->fetch();

    // Last 10 requests
    $stmt = $pdo->query("
        SELECT path, method, status_code, response_time, ip_address, created_at
        FROM stats_requests
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $recentRequests = $stmt->fetchAll();

    // Total requests
    $stmt = $pdo->query("SELECT SUM(requests) as total FROM stats_endpoints");
    $totalRequests = (int) ($stmt->fetchColumn() ?: 0);

    return [
        'timestamp' => date('Y-m-d H:i:s'),
        'last_minute' => [
            'requests' => (int) ($lastMinute['requests'] ?? 0),
            'errors' => (int) ($lastMinute['errors'] ?? 0),
            'avg_time' => (float) ($lastMinute['avg_time'] ?? 0),
        ],
        'total_requests' => $totalRequests,
        'recent_requests' => array_map(function ($row) {
            return [
                'path' => $row['path'],
                'method' => $row['method'],
                'status' => (int) $row['status_code'],
                'time' => round((float) $row['response_time'], 2),
                'ip' => $row['ip_address'],
                'when' => $row['created_at'],
            ];
        }, $recentRequests),
    ];
}

/**
 * Get full statistics (less frequently)
 */
function getFullStats(PDO $pdo): array
{
    // Total requests
    $stmt = $pdo->query("SELECT SUM(requests) as total FROM stats_endpoints");
    $totalRequests = (int) ($stmt->fetchColumn() ?: 0);

    if ($totalRequests === 0) {
        return [
            'totalRequests' => 0,
            'successRate' => 100,
            'avgResponseTime' => 0,
            'topEndpoints' => [],
        ];
    }

    // Success rate
    $stmt = $pdo->query("SELECT SUM(success) as s, SUM(errors) as e FROM stats_endpoints");
    $row = $stmt->fetch();
    $successRate = $totalRequests > 0 ? round(((int) $row['s'] / $totalRequests) * 100, 1) : 100;

    // Avg response time
    $stmt = $pdo->query("SELECT SUM(total_time) / SUM(requests) as avg FROM stats_endpoints WHERE requests > 0");
    $avgResponseTime = round((float) ($stmt->fetchColumn() ?: 0), 2);

    // Top endpoints
    $stmt = $pdo->query("
        SELECT path, method, requests, avg_time
        FROM stats_endpoints
        ORDER BY requests DESC
        LIMIT 5
    ");
    $topEndpoints = $stmt->fetchAll();

    return [
        'totalRequests' => $totalRequests,
        'successRate' => $successRate,
        'avgResponseTime' => $avgResponseTime,
        'topEndpoints' => array_map(function ($row) use ($totalRequests) {
            return [
                'path' => $row['path'],
                'method' => $row['method'],
                'requests' => (int) $row['requests'],
                'percent' => round(((int) $row['requests'] / $totalRequests) * 100, 1),
                'avg_time' => round((float) $row['avg_time'], 2),
            ];
        }, $topEndpoints),
    ];
}

// Main loop
$iteration = 0;

while (true) {
    if (connection_aborted()) {
        break;
    }

    try {
        // Live stats every iteration
        $stats = getLiveStats($pdo);
        sendEvent('stats', $stats);

        // Full stats every 10th iteration
        if ($iteration % 10 === 0) {
            $fullStats = getFullStats($pdo);
            sendEvent('fullStats', $fullStats);
        }

        // Heartbeat
        sendEvent('heartbeat', ['timestamp' => date('Y-m-d H:i:s'), 'iteration' => $iteration]);

    } catch (Exception $e) {
        sendEvent('error', ['message' => $e->getMessage(), 'timestamp' => date('Y-m-d H:i:s')]);
    }

    $iteration++;
    sleep(2);

    // Limit to 5 minutes (150 iterations * 2 seconds)
    if ($iteration > 150) {
        sendEvent('reconnect', ['message' => 'Connection timeout, please reconnect', 'timestamp' => date('Y-m-d H:i:s')]);
        break;
    }
}
