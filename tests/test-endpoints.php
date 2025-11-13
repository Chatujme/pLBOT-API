<?php

declare(strict_types=1);

/**
 * pLBOT API v2.0 - Endpoint Testing Script
 *
 * Usage: php tests/test-endpoints.php [api-url]
 * Example: php tests/test-endpoints.php http://api.plbot.local
 */

$apiUrl = $argv[1] ?? 'http://localhost';
$apiUrl = rtrim($apiUrl, '/');

echo "🧪 Testing pLBOT API v2.0\n";
echo "API URL: {$apiUrl}\n";
echo str_repeat('=', 80) . "\n\n";

$stats = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'errors' => [],
];

/**
 * Test API endpoint
 */
function testEndpoint(string $method, string $endpoint, string $description, ?callable $validator = null): void
{
    global $apiUrl, $stats;

    $stats['total']++;
    $url = $apiUrl . $endpoint;

    echo "Testing: {$description}\n";
    echo "  URL: {$method} {$url}\n";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => $method,
    ]);

    $startTime = microtime(true);
    $response = curl_exec($ch);
    $duration = microtime(true) - $startTime;
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "  ❌ FAILED: cURL Error: {$error}\n";
        $stats['failed']++;
        $stats['errors'][] = "{$description}: {$error}";
        echo "\n";
        return;
    }

    echo "  HTTP Code: {$httpCode}\n";
    echo "  Duration: " . number_format($duration * 1000, 2) . "ms\n";

    // Try to decode JSON
    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "  ❌ FAILED: Invalid JSON response\n";
        echo "  Response: " . substr($response, 0, 200) . "...\n";
        $stats['failed']++;
        $stats['errors'][] = "{$description}: Invalid JSON";
        echo "\n";
        return;
    }

    // Custom validator
    if ($validator !== null) {
        $valid = $validator($data, $httpCode);
        if (!$valid) {
            echo "  ❌ FAILED: Validation failed\n";
            echo "  Data: " . json_encode($data) . "\n";
            $stats['failed']++;
            $stats['errors'][] = "{$description}: Validation failed";
            echo "\n";
            return;
        }
    }

    echo "  ✅ PASSED\n";
    $stats['passed']++;
    echo "\n";
}

// =============================================================================
// SVATKY API TESTS
// =============================================================================

echo "📅 Svátky API Tests\n";
echo str_repeat('-', 80) . "\n\n";

testEndpoint('GET', '/svatky', 'Svátky - všechny dny', function ($data) {
    return isset($data['data'])
        && is_array($data['data'])
        && isset($data['data']['dnes'])
        && isset($data['data']['zitra']);
});

testEndpoint('GET', '/svatky/dnes', 'Svátky - dnes', function ($data) {
    return isset($data['data']) && is_string($data['data']);
});

testEndpoint('GET', '/svatky/zitra', 'Svátky - zítra', function ($data) {
    return isset($data['data']) && is_string($data['data']);
});

testEndpoint('GET', '/svatky/vcera', 'Svátky - včera', function ($data) {
    return isset($data['data']) && is_string($data['data']);
});

// =============================================================================
// POCASI API TESTS
// =============================================================================

echo "🌤️  Počasí API Tests\n";
echo str_repeat('-', 80) . "\n\n";

testEndpoint('GET', '/pocasi', 'Počasí - Praha všechny dny', function ($data) {
    return isset($data['data'])
        && is_array($data['data'])
        && isset($data['data']['dnes'])
        && isset($data['data']['zitra']);
});

testEndpoint('GET', '/pocasi/dnes', 'Počasí - Praha dnes', function ($data) {
    return isset($data['data'])
        && isset($data['data']['datum'])
        && isset($data['data']['predpoved']);
});

testEndpoint('GET', '/pocasi/dnes?mesto=brno', 'Počasí - Brno dnes', function ($data) {
    return isset($data['data'])
        && isset($data['data']['datum']);
});

testEndpoint('GET', '/pocasi/zitra?mesto=plzen', 'Počasí - Plzeň zítra', function ($data) {
    return isset($data['data'])
        && isset($data['data']['datum']);
});

// =============================================================================
// HOROSKOPY API TESTS
// =============================================================================

echo "🔮 Horoskopy API Tests\n";
echo str_repeat('-', 80) . "\n\n";

$znameni = ['beran', 'byk', 'blizenci', 'rak', 'lev', 'panna', 'vahy', 'stir', 'strelec', 'kozoroh', 'vodnar', 'ryby'];

foreach ($znameni as $z) {
    testEndpoint('GET', "/horoskop/{$z}", "Horoskop - {$z}", function ($data) {
        return isset($data['data'])
            && (isset($data['data']['znameni']) || isset($data['data']['message']));
    });
}

// =============================================================================
// TV PROGRAM API TESTS
// =============================================================================

echo "📺 TV Program API Tests\n";
echo str_repeat('-', 80) . "\n\n";

testEndpoint('GET', '/tv', 'TV - seznam stanic', function ($data) {
    return isset($data['data']) && is_array($data['data']);
});

testEndpoint('GET', '/tv/vse', 'TV - všechny programy', function ($data) {
    return isset($data['data']) && is_array($data['data']);
});

testEndpoint('GET', '/tv/ct1', 'TV - ČT1', function ($data) {
    return isset($data['data']);
});

testEndpoint('GET', '/tv/nova', 'TV - Nova', function ($data) {
    return isset($data['data']);
});

testEndpoint('GET', '/tv/prima', 'TV - Prima', function ($data) {
    return isset($data['data']);
});

// =============================================================================
// MISTNOST API TESTS
// =============================================================================

echo "🏠 Místnost API Tests\n";
echo str_repeat('-', 80) . "\n\n";

testEndpoint('GET', '/mistnost/12345', 'Místnost - test ID', function ($data, $code) {
    // Může být buď úspěch nebo 404
    return (isset($data['data']) || isset($data['error']));
});

// =============================================================================
// ERROR HANDLING TESTS
// =============================================================================

echo "⚠️  Error Handling Tests\n";
echo str_repeat('-', 80) . "\n\n";

testEndpoint('GET', '/tv/neexistujici-stanice', 'TV - neexistující stanice (404)', function ($data, $code) {
    return $code === 404 && isset($data['error']);
});

// =============================================================================
// RESULTS
// =============================================================================

echo str_repeat('=', 80) . "\n";
echo "📊 Test Results\n";
echo str_repeat('=', 80) . "\n\n";

echo "Total tests: {$stats['total']}\n";
echo "✅ Passed: {$stats['passed']}\n";
echo "❌ Failed: {$stats['failed']}\n";
echo "\n";

if ($stats['failed'] > 0) {
    echo "Failed tests:\n";
    foreach ($stats['errors'] as $error) {
        echo "  - {$error}\n";
    }
    echo "\n";
    exit(1);
}

echo "🎉 All tests passed!\n\n";
exit(0);
