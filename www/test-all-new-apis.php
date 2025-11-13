<?php

declare(strict_types=1);

/**
 * Test pro VŠECHNY nové API endpointy - s reálnými HTTP požadavky
 *
 * Testuje všechny nově přidané API služby v projektu pLBOT-API
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "🚀 Test všech nových API endpointů\n";
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
// 1. JOKEAPI - Vtipy
// =============================================================================

echo "😄 JokeAPI Tests\n";
echo str_repeat('-', 80) . "\n\n";

test("JokeAPI - Get random joke", function() {
    $ch = curl_init('https://v2.jokeapi.dev/joke/Any?safe-mode');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo "   HTTP Code: {$code}\n";
        return false;
    }

    $data = json_decode($response, true);

    if (isset($data['error']) && $data['error'] === true) {
        echo "   Error: " . ($data['message'] ?? 'Unknown') . "\n";
        return false;
    }

    if (isset($data['joke'])) {
        echo "   Type: single\n";
        echo "   Category: " . ($data['category'] ?? 'N/A') . "\n";
        echo "   Joke: " . substr($data['joke'], 0, 80) . "...\n";
    } elseif (isset($data['setup']) && isset($data['delivery'])) {
        echo "   Type: twopart\n";
        echo "   Category: " . ($data['category'] ?? 'N/A') . "\n";
        echo "   Setup: " . substr($data['setup'], 0, 50) . "...\n";
    } else {
        echo "   Invalid response structure\n";
        return false;
    }

    return true;
});

// =============================================================================
// 2. CAT FACTS - Zajímavosti o kočkách
// =============================================================================

echo "🐱 Cat Facts API Tests\n";
echo str_repeat('-', 80) . "\n\n";

test("CatFacts - Get random fact", function() {
    $ch = curl_init('https://catfact.ninja/fact');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo "   HTTP Code: {$code}\n";
        return false;
    }

    $data = json_decode($response, true);

    if (!isset($data['fact'])) {
        echo "   No 'fact' field in response\n";
        return false;
    }

    echo "   Fact: " . substr($data['fact'], 0, 80) . "...\n";
    echo "   Length: " . ($data['length'] ?? strlen($data['fact'])) . "\n";

    return true;
});

// =============================================================================
// 3. DOG CEO - Obrázky psů
// =============================================================================

echo "🐶 Dog CEO API Tests\n";
echo str_repeat('-', 80) . "\n\n";

test("Dog CEO - Get random dog image", function() {
    $ch = curl_init('https://dog.ceo/api/breeds/image/random');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo "   HTTP Code: {$code}\n";
        return false;
    }

    $data = json_decode($response, true);

    if ($data['status'] !== 'success') {
        echo "   Status: " . ($data['status'] ?? 'unknown') . "\n";
        return false;
    }

    if (!isset($data['message']) || !filter_var($data['message'], FILTER_VALIDATE_URL)) {
        echo "   Invalid image URL\n";
        return false;
    }

    echo "   Image URL: " . $data['message'] . "\n";

    return true;
});

// =============================================================================
// 4. ADVICE SLIP - Rady
// =============================================================================

echo "💡 Advice Slip API Tests\n";
echo str_repeat('-', 80) . "\n\n";

test("Advice Slip - Get random advice", function() {
    $ch = curl_init('https://api.adviceslip.com/advice');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo "   HTTP Code: {$code}\n";
        return false;
    }

    $data = json_decode($response, true);

    if (!isset($data['slip']['advice'])) {
        echo "   No advice in response\n";
        return false;
    }

    echo "   Advice ID: " . ($data['slip']['id'] ?? 'N/A') . "\n";
    echo "   Advice: " . substr($data['slip']['advice'], 0, 80) . "...\n";

    return true;
});

// =============================================================================
// 5. COINGECKO - Ceny kryptoměn
// =============================================================================

echo "💰 CoinGecko API Tests\n";
echo str_repeat('-', 80) . "\n\n";

test("CoinGecko - Get Bitcoin price", function() {
    $ch = curl_init('https://api.coingecko.com/api/v3/simple/price?ids=bitcoin&vs_currencies=usd&include_24hr_change=true');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo "   HTTP Code: {$code}\n";
        return false;
    }

    $data = json_decode($response, true);

    if (!isset($data['bitcoin']['usd'])) {
        echo "   No Bitcoin price in response\n";
        return false;
    }

    echo "   BTC Price: $" . number_format($data['bitcoin']['usd'], 2) . " USD\n";
    if (isset($data['bitcoin']['usd_24h_change'])) {
        echo "   24h Change: " . number_format($data['bitcoin']['usd_24h_change'], 2) . "%\n";
    }

    return $data['bitcoin']['usd'] > 0;
});

// =============================================================================
// 6. QUOTABLE - Citáty
// =============================================================================

echo "📖 Quotable API Tests\n";
echo str_repeat('-', 80) . "\n\n";

test("Quotable - Get random quote", function() {
    $ch = curl_init('https://api.quotable.io/quotes/random');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo "   HTTP Code: {$code}\n";
        return false;
    }

    $data = json_decode($response, true);

    if (!is_array($data) || empty($data)) {
        echo "   Empty response\n";
        return false;
    }

    $quote = $data[0];

    if (!isset($quote['content'])) {
        echo "   No quote content\n";
        return false;
    }

    echo "   Quote: " . substr($quote['content'], 0, 80) . "...\n";
    echo "   Author: " . ($quote['author'] ?? 'Unknown') . "\n";
    if (isset($quote['tags']) && is_array($quote['tags'])) {
        echo "   Tags: " . implode(', ', $quote['tags']) . "\n";
    }

    return true;
});

// =============================================================================
// 7. CHUCK NORRIS - Vtipy o Chucku Norrisovi
// =============================================================================

echo "🥋 Chuck Norris API Tests\n";
echo str_repeat('-', 80) . "\n\n";

test("Chuck Norris - Get random joke", function() {
    $ch = curl_init('https://api.chucknorris.io/jokes/random');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo "   HTTP Code: {$code}\n";
        return false;
    }

    $data = json_decode($response, true);

    if (!isset($data['value'])) {
        echo "   No joke in response\n";
        return false;
    }

    echo "   Joke: " . substr($data['value'], 0, 80) . "...\n";
    echo "   ID: " . ($data['id'] ?? 'N/A') . "\n";

    return true;
});

// =============================================================================
// 8. NUMBERS API - Zajímavosti o číslech
// =============================================================================

echo "🔢 Numbers API Tests\n";
echo str_repeat('-', 80) . "\n\n";

test("NumbersAPI - Get number fact", function() {
    $ch = curl_init('http://numbersapi.com/42/trivia?json');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo "   HTTP Code: {$code}\n";
        return false;
    }

    $data = json_decode($response, true);

    if (!isset($data['text'])) {
        echo "   No fact text in response\n";
        return false;
    }

    echo "   Number: " . ($data['number'] ?? 'N/A') . "\n";
    echo "   Type: " . ($data['type'] ?? 'N/A') . "\n";
    echo "   Fact: " . substr($data['text'], 0, 80) . "...\n";

    return true;
});

// =============================================================================
// 9. REST COUNTRIES - Informace o zemích
// =============================================================================

echo "🌍 REST Countries API Tests\n";
echo str_repeat('-', 80) . "\n\n";

test("REST Countries - Get Czech Republic info", function() {
    $ch = curl_init('https://restcountries.com/v3.1/name/czechia');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo "   HTTP Code: {$code}\n";
        return false;
    }

    $data = json_decode($response, true);

    if (!is_array($data) || empty($data)) {
        echo "   Empty response\n";
        return false;
    }

    $country = $data[0];

    echo "   Name: " . ($country['name']['common'] ?? 'N/A') . "\n";
    echo "   Official: " . ($country['name']['official'] ?? 'N/A') . "\n";
    echo "   Capital: " . ($country['capital'][0] ?? 'N/A') . "\n";
    echo "   Population: " . number_format($country['population'] ?? 0) . "\n";
    echo "   Region: " . ($country['region'] ?? 'N/A') . "\n";
    echo "   Code: " . ($country['cca2'] ?? 'N/A') . "\n";

    return isset($country['name']['common']);
});

// =============================================================================
// 10. BORED API - Nápady na aktivity
// =============================================================================

echo "🎯 Bored API Tests\n";
echo str_repeat('-', 80) . "\n\n";

test("Bored API - Get random activity", function() {
    $ch = curl_init('https://www.boredapi.com/api/activity');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo "   HTTP Code: {$code}\n";
        return false;
    }

    $data = json_decode($response, true);

    if (!isset($data['activity'])) {
        echo "   No activity in response\n";
        return false;
    }

    echo "   Activity: " . $data['activity'] . "\n";
    echo "   Type: " . ($data['type'] ?? 'N/A') . "\n";
    echo "   Participants: " . ($data['participants'] ?? 'N/A') . "\n";
    echo "   Price: " . ($data['price'] ?? 'N/A') . " (0=free, 1=expensive)\n";

    return true;
});

// =============================================================================
// 11. ISS API - Poloha Mezinárodní vesmírné stanice
// =============================================================================

echo "🛰️  ISS API Tests\n";
echo str_repeat('-', 80) . "\n\n";

test("ISS API - Get current position", function() {
    $ch = curl_init('http://api.open-notify.org/iss-now.json');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo "   HTTP Code: {$code}\n";
        return false;
    }

    $data = json_decode($response, true);

    if (!isset($data['iss_position'])) {
        echo "   No ISS position in response\n";
        return false;
    }

    $pos = $data['iss_position'];
    echo "   Latitude: " . ($pos['latitude'] ?? 'N/A') . "\n";
    echo "   Longitude: " . ($pos['longitude'] ?? 'N/A') . "\n";
    echo "   Timestamp: " . date('Y-m-d H:i:s', $data['timestamp'] ?? time()) . "\n";

    return isset($pos['latitude']) && isset($pos['longitude']);
});

// =============================================================================
// 12. OPEN TRIVIA DATABASE - Trivia otázky
// =============================================================================

echo "❓ Open Trivia Database Tests\n";
echo str_repeat('-', 80) . "\n\n";

test("Open Trivia - Get trivia questions", function() {
    $ch = curl_init('https://opentdb.com/api.php?amount=1');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo "   HTTP Code: {$code}\n";
        return false;
    }

    $data = json_decode($response, true);

    if (!isset($data['results']) || $data['response_code'] !== 0) {
        echo "   API error or no results\n";
        return false;
    }

    $question = $data['results'][0];

    echo "   Category: " . html_entity_decode($question['category'] ?? 'N/A', ENT_QUOTES | ENT_HTML5) . "\n";
    echo "   Difficulty: " . ($question['difficulty'] ?? 'N/A') . "\n";
    echo "   Type: " . ($question['type'] ?? 'N/A') . "\n";
    echo "   Question: " . substr(html_entity_decode($question['question'] ?? '', ENT_QUOTES | ENT_HTML5), 0, 80) . "...\n";

    return isset($question['question']);
});

// =============================================================================
// 13. UUID - Generování UUID
// =============================================================================

echo "🔑 UUID Generation Tests\n";
echo str_repeat('-', 80) . "\n\n";

test("UUID - Generate and validate UUID v4", function() {
    // Generujeme UUID v4
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

    echo "   Generated UUID: {$uuid}\n";

    // Validujeme UUID
    $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
    $isValid = preg_match($pattern, $uuid) === 1;

    echo "   Valid: " . ($isValid ? 'Yes' : 'No') . "\n";
    echo "   Version: 4\n";

    return $isValid;
});

// =============================================================================
// 14. FOX API - Obrázky lišek
// =============================================================================

echo "🦊 Fox API Tests\n";
echo str_repeat('-', 80) . "\n\n";

test("Fox API - Get random fox image", function() {
    $ch = curl_init('https://randomfox.ca/floof/');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo "   HTTP Code: {$code}\n";
        return false;
    }

    $data = json_decode($response, true);

    if (!isset($data['image'])) {
        echo "   No image in response\n";
        return false;
    }

    if (!filter_var($data['image'], FILTER_VALIDATE_URL)) {
        echo "   Invalid image URL\n";
        return false;
    }

    echo "   Image URL: " . $data['image'] . "\n";

    return true;
});

// =============================================================================
// 15. RUIAN - Registry územní identifikace, adres a nemovitostí
// =============================================================================

echo "🏘️  RUIAN API Tests\n";
echo str_repeat('-', 80) . "\n\n";

test("RUIAN - Search Czech city (Praha)", function() {
    $nazev = 'Praha';
    $url = 'https://ags.cuzk.cz/arcgis/rest/services/RUIAN/Vyhledavaci_sluzba_nad_daty_RUIAN/MapServer/12/query';
    $queryParams = [
        'where' => "nazev LIKE '%" . str_replace("'", "''", $nazev) . "%'",
        'outFields' => '*',
        'f' => 'json',
        'returnGeometry' => 'false',
        'resultRecordCount' => '1',
    ];

    $fullUrl = $url . '?' . http_build_query($queryParams);

    $ch = curl_init($fullUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo "   HTTP Code: {$code}\n";
        return false;
    }

    $data = json_decode($response, true);

    if (!isset($data['features']) || empty($data['features'])) {
        echo "   No results found\n";
        return false;
    }

    $obec = $data['features'][0]['attributes'] ?? [];

    echo "   Název: " . ($obec['nazev'] ?? 'N/A') . "\n";
    echo "   Kód: " . ($obec['kod'] ?? 'N/A') . "\n";
    echo "   Okres: " . ($obec['okres'] ?? 'N/A') . "\n";

    return isset($obec['nazev']);
});

// =============================================================================
// 16. ZÁSILKOVNA - Sledování zásilek
// =============================================================================

echo "📦 Zásilkovna API Tests\n";
echo str_repeat('-', 80) . "\n\n";

test("Zásilkovna - Verify tracking endpoint exists", function() {
    // Testujeme s neexistujícím balíkem - očekáváme 404 nebo error response
    // To nám potvrdí, že endpoint existuje a funguje
    $packageId = 'TEST123456789';
    $url = 'https://tracking.packeta.com/api/v1/tracking/' . urlencode($packageId);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Očekáváme 404 (balík nenalezen) nebo nějakou error response
    // To potvrdí, že API endpoint existuje a odpovídá
    if ($code === 404) {
        echo "   HTTP Code: 404 (expected - package not found)\n";
        echo "   Endpoint is working correctly\n";
        return true;
    }

    if ($code === 200) {
        $data = json_decode($response, true);
        echo "   HTTP Code: 200\n";
        echo "   Endpoint is working\n";
        return true;
    }

    // Jiné kódy mohou znamenat různé věci
    echo "   HTTP Code: {$code}\n";
    echo "   Response: " . substr($response, 0, 100) . "\n";

    // Pokud API odpovědělo (i když errorem), považujeme to za úspěch
    // protože to znamená, že endpoint existuje
    return $code >= 200 && $code < 500;
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
    echo "🎉 All API tests passed!\n\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed. Please review the results above.\n\n";
    exit(1);
}
