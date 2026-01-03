<?php

declare(strict_types=1);

use Apitte\Core\Application\IApplication;
use App\Services\StatsService;

// Strip base path from REQUEST_URI for Apitte routing
$basePath = '/pLBOT-API/www';
$originalUri = $_SERVER['REQUEST_URI'] ?? '/';
if (str_starts_with($originalUri, $basePath)) {
    $_SERVER['REQUEST_URI'] = substr($originalUri, strlen($basePath)) ?: '/';
}

$startTime = microtime(true);
$container = require __DIR__ . '/../app/bootstrap.php';

// Register stats logging at shutdown
register_shutdown_function(function () use ($container, $startTime, $originalUri) {
    try {
        $statsService = $container->getByType(StatsService::class);
        $responseTime = (microtime(true) - $startTime) * 1000;
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($originalUri, PHP_URL_PATH) ?? '/';

        // Remove base path for cleaner stats
        $cleanPath = str_replace('/pLBOT-API/www', '', $path) ?: '/';

        // Get response code from headers if possible
        $statusCode = http_response_code() ?: 200;

        // Get client IP and User Agent
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $statsService->logRequest($cleanPath, $method, $statusCode, $responseTime, $ipAddress, $userAgent);
    } catch (\Throwable $e) {
        // Silent fail - stats are not critical
    }
});

// Run Apitte Application
$application = $container->getByType(IApplication::class);
$application->run();
