<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Tester\Environment;

Environment::setup();

/**
 * API test helper functions
 */
class ApiTestHelper
{
    private static string $baseUrl = 'http://localhost/pLBOT-API/www';

    public static function setBaseUrl(string $url): void
    {
        self::$baseUrl = rtrim($url, '/');
    }

    public static function getBaseUrl(): string
    {
        return self::$baseUrl;
    }

    /**
     * Make HTTP request to API
     */
    public static function request(string $method, string $endpoint, array $options = []): array
    {
        $url = self::$baseUrl . $endpoint;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $options['headers'] ?? ['Content-Type: application/json'],
        ]);

        if (!empty($options['body'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($options['body']) ? json_encode($options['body']) : $options['body']);
        }

        $startTime = microtime(true);
        $response = curl_exec($ch);
        $duration = (microtime(true) - $startTime) * 1000;
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        return [
            'status' => $httpCode,
            'body' => $response,
            'json' => json_decode($response, true),
            'duration' => $duration,
            'error' => $error,
            'contentType' => $contentType,
        ];
    }

    /**
     * GET request shorthand
     */
    public static function get(string $endpoint): array
    {
        return self::request('GET', $endpoint);
    }

    /**
     * POST request shorthand
     */
    public static function post(string $endpoint, array $body = []): array
    {
        return self::request('POST', $endpoint, ['body' => $body]);
    }
}
