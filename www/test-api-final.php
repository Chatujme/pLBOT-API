<?php

declare(strict_types=1);

/**
 * Finální test všech API služeb - s reálnými HTTP požadavky
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "🧪 pLBOT API v2.0 - Finální Test Všech Služeb\n";
echo str_repeat('=', 80) . "\n\n";

$results = ['passed' => 0, 'failed' => 0, 'total' => 0];

function test(string $name, callable $test): void {
    global $results;
    $results['total']++;
    echo "Testing: {$name}\n";

    try {
        $result = $test();
        if ($result) {
            echo "   ✅ PASSED\n";
            $results['passed']++;
        } else {
            echo "   ❌ FAILED\n";
            $results['failed']++;
        }
    } catch (\Exception $e) {
        echo "   ❌ EXCEPTION: " . $e->getMessage() . "\n";
        $results['failed']++;
    }
    echo "\n";
}

// =============================================================================
// 1. SVÁTKY API (svatkyapi.cz JSON API)
// =============================================================================

echo "📅 Svátky API Tests (JSON API)\n";
echo str_repeat('-', 80) . "\n\n";

test("Svátky - dnešní svátek", function() {
    $ch = curl_init('https://svatkyapi.cz/api/day');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $json = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return false;

    $data = json_decode($json, true);
    if (!isset($data['name'])) return false;

    echo "   Dnes má svátek: {$data['name']}\n";
    echo "   Datum: {$data['date']}\n";
    return true;
});

test("Svátky - zítřejší svátek", function() {
    $tomorrow = (new DateTime())->modify('+1 day')->format('Y-m-d');
    $ch = curl_init("https://svatkyapi.cz/api/day/{$tomorrow}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $json = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return false;

    $data = json_decode($json, true);
    if (!isset($data['name'])) return false;

    echo "   Zítra má svátek: {$data['name']}\n";
    return true;
});

// =============================================================================
// 2. POČASÍ API (centrum.cz JSON API)
// =============================================================================

echo "🌤️  Počasí API Tests (JSON API)\n";
echo str_repeat('-', 80) . "\n\n";

test("Počasí - Praha", function() {
    $ch = curl_init('https://pocasi-backend.centrum.cz/api/v2/widget/welcome/praha');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $json = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo "   HTTP Code: {$code}\n";
        return false;
    }

    $data = json_decode($json, true);
    if (!isset($data['long_term_forecast']['forecasts'][0])) return false;

    $dnes = $data['long_term_forecast']['forecasts'][0];
    echo "   Datum: {$dnes['date']}\n";
    echo "   Předpověď: {$dnes['day_forecast']}\n";
    echo "   Teplota: {$dnes['temp_day']}\n";
    return true;
});

test("Počasí - Brno", function() {
    $ch = curl_init('https://pocasi-backend.centrum.cz/api/v2/widget/welcome/brno');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $json = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return false;

    $data = json_decode($json, true);
    return isset($data['long_term_forecast']['forecasts'][0]);
});

// =============================================================================
// 3. HOROSKOPY (horoskopy.cz HTML)
// =============================================================================

echo "🔮 Horoskopy Tests (HTML)\n";
echo str_repeat('-', 80) . "\n\n";

test("Horoskop - Lev", function() {
    $ch = curl_init('https://www.horoskopy.cz/lev');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return false;

    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($dom);

    $h1 = $xpath->query('//h1');
    if ($h1->length === 0) return false;

    echo "   Znamení: " . trim($h1->item(0)->textContent) . "\n";
    return true;
});

// =============================================================================
// 4. TV PROGRAM (xmltv.tvpc.cz XML)
// =============================================================================

echo "📺 TV Program Tests (XML)\n";
echo str_repeat('-', 80) . "\n\n";

test("TV Program - XMLTV feed", function() {
    $ch = curl_init('http://xmltv.tvpc.cz/xmltv.xml');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $xml = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return false;

    $xmlObj = @simplexml_load_string($xml);
    if ($xmlObj === false) return false;

    $count = count($xmlObj->programme ?? []);
    echo "   Počet programů: {$count}\n";

    if ($count > 0) {
        echo "   První program: " . (string)$xmlObj->programme[0]->title . "\n";
    }

    return $count > 0;
});

// =============================================================================
// SUMMARY
// =============================================================================

echo str_repeat('=', 80) . "\n";
echo "📊 Test Summary\n";
echo str_repeat('=', 80) . "\n\n";

echo "Total tests: {$results['total']}\n";
echo "✅ Passed: {$results['passed']}\n";
echo "❌ Failed: {$results['failed']}\n";
echo "\n";

if ($results['failed'] === 0) {
    echo "🎉 All tests passed! API is fully functional.\n\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed. Please review the results above.\n\n";
    exit(1);
}
