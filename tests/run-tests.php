<?php

declare(strict_types=1);

/**
 * pLBOT API v2.0 - Comprehensive Test Runner
 * Runs all API endpoint tests using Nette Tester
 */

require __DIR__ . '/bootstrap.php';

echo "======================================================================\n";
echo "  pLBOT API v2.0 - Comprehensive Test Runner\n";
echo "======================================================================\n\n";

$baseUrl = $argv[1] ?? 'http://localhost/pLBOT-API/www';
ApiTestHelper::setBaseUrl($baseUrl);

echo "API Base URL: " . ApiTestHelper::getBaseUrl() . "\n\n";

// Define all endpoints to test (using correct paths from OpenAPI spec)
$endpoints = [
    // Svatky API
    ['GET', '/svatky', 'Svatky - All'],
    ['GET', '/svatky/today', 'Svatky - Today'],
    ['GET', '/svatky/dnes', 'Svatky - Dnes'],
    ['GET', '/svatky/zitra', 'Svatky - Zitra'],
    ['GET', '/svatky/vcera', 'Svatky - Vcera'],
    ['GET', '/svatky/predevcirem', 'Svatky - Predevcirem'],

    // CNB API
    ['GET', '/cnb/kurzy', 'CNB - All Rates'],
    ['GET', '/cnb/kurzy/eur', 'CNB - EUR Rate'],
    ['GET', '/cnb/kurzy/usd', 'CNB - USD Rate'],

    // Utility API
    ['GET', '/uuid', 'UUID - Generate'],
    ['GET', '/uuid/nil', 'UUID - Nil'],
    ['GET', '/hash/algorithms', 'Hash - Algorithms'],
    ['GET', '/hash?data=test', 'Hash - Generate'],
    ['GET', '/qr/generate?data=Hello', 'QR Code - Generate'],

    // Fun API
    ['GET', '/advice', 'Advice - Random'],
    ['GET', '/joke', 'Joke - Random'],
    ['GET', '/joke/programming', 'Joke - Programming'],
    ['GET', '/chucknorris', 'Chuck Norris - Random'],
    ['GET', '/chucknorris/categories', 'Chuck Norris - Categories'],
    ['GET', '/trivia', 'Trivia - Random'],
    ['GET', '/trivia/categories', 'Trivia - Categories'],

    // Animal API
    ['GET', '/catfact', 'Cat Fact - Random'],
    ['GET', '/dog', 'Dog - Random Image'],
    ['GET', '/dog/breeds', 'Dog - Breeds List'],
    ['GET', '/fox', 'Fox - Random Image'],

    // Data API
    ['GET', '/countries/CZ', 'Countries - Czech Republic'],
    ['GET', '/crypto/popular', 'Crypto - Popular'],
    ['GET', '/crypto/price/bitcoin', 'Crypto - Bitcoin Price'],
    ['GET', '/iss/position', 'ISS - Position'],
    ['GET', '/iss/astronauts', 'ISS - Astronauts'],
    ['GET', '/news/sources', 'News RSS - Sources'],

    // Czech API
    ['GET', '/pocasi', 'Pocasi - All'],
    ['GET', '/pocasi/dnes', 'Pocasi - Dnes'],
    ['GET', '/pocasi/zitra', 'Pocasi - Zitra'],
    ['GET', '/horoskop/beran', 'Horoskop - Beran'],
    ['GET', '/horoskop/lev', 'Horoskop - Lev'],
    ['GET', '/tv', 'TV Program - Stations'],
    ['GET', '/tv/vse', 'TV Program - All'],

    // VAT API
    ['GET', '/vat/countries', 'VAT - Countries'],

    // Admin API
    ['GET', '/admin/stats', 'Admin - Stats'],
    ['GET', '/openapi/spec', 'OpenAPI - Spec'],
];

$stats = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'errors' => [],
];

foreach ($endpoints as $endpoint) {
    $method = $endpoint[0];
    $path = $endpoint[1];
    $description = $endpoint[2];
    $body = $endpoint[3] ?? null;
    $stats['total']++;

    echo "Testing: {$description}\n";
    echo "  {$method} {$path}\n";

    try {
        $options = $body ? ['body' => $body] : [];
        $response = ApiTestHelper::request($method, $path, $options);

        if ($response['error']) {
            echo "  [FAIL] cURL Error: {$response['error']}\n\n";
            $stats['failed']++;
            $stats['errors'][] = "{$description}: {$response['error']}";
            continue;
        }

        $statusOk = $response['status'] >= 200 && $response['status'] < 400;
        $isJson = $response['json'] !== null;
        $isImage = strpos($response['contentType'] ?? '', 'image') !== false;

        if (!$statusOk) {
            echo "  [FAIL] HTTP {$response['status']}\n\n";
            $stats['failed']++;
            $stats['errors'][] = "{$description}: HTTP {$response['status']}";
            continue;
        }

        if (!$isJson && !$isImage) {
            echo "  [FAIL] Invalid response format\n\n";
            $stats['failed']++;
            $stats['errors'][] = "{$description}: Invalid response format";
            continue;
        }

        echo "  [OK] HTTP {$response['status']} - " . number_format($response['duration'], 2) . "ms\n\n";
        $stats['passed']++;

    } catch (Exception $e) {
        echo "  [FAIL] Exception: {$e->getMessage()}\n\n";
        $stats['failed']++;
        $stats['errors'][] = "{$description}: Exception - {$e->getMessage()}";
    }
}

echo "======================================================================\n";
echo "  Test Results\n";
echo "======================================================================\n\n";

echo "Total:  {$stats['total']}\n";
echo "Passed: {$stats['passed']}\n";
echo "Failed: {$stats['failed']}\n\n";

if ($stats['failed'] > 0) {
    echo "Failed tests:\n";
    foreach ($stats['errors'] as $error) {
        echo "  - {$error}\n";
    }
    echo "\n";
    exit(1);
}

echo "All tests passed!\n\n";
exit(0);
