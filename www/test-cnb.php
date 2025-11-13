<?php

declare(strict_types=1);

/**
 * Test pro ČNB Kurzy API - s reálnými HTTP požadavky
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "💰 ČNB Kurzy API - Komplexní Test\n";
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
// 1. TEST ČNB API - Přímý přístup k ČNB
// =============================================================================

echo "📊 ČNB Source API Tests (TXT format)\n";
echo str_repeat('-', 80) . "\n\n";

test("ČNB - stažení denního kurzovního lístku", function() {
    $ch = curl_init('https://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $txt = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        echo "   HTTP Code: {$code}\n";
        return false;
    }

    $lines = explode("\n", trim($txt));

    // První řádek je datum
    echo "   Datum: {$lines[0]}\n";

    // Druhý řádek je hlavička
    echo "   Hlavička: {$lines[1]}\n";

    // Třetí řádek je první měna
    if (isset($lines[2])) {
        echo "   První měna: {$lines[2]}\n";
    }

    $count = count($lines) - 2; // mínus datum a hlavička
    echo "   Počet měn: {$count}\n";

    return $count > 0;
});

test("ČNB - parsování TXT formátu", function() {
    $ch = curl_init('https://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $txt = curl_exec($ch);
    curl_close($ch);

    $lines = explode("\n", trim($txt));

    // Zpracujeme data od 3. řádku
    $currencies = [];
    for ($i = 2; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        if (empty($line)) continue;

        $parts = explode('|', $line);
        if (count($parts) !== 5) continue;

        [$zeme, $mena, $mnozstvi, $kod, $kurzStr] = $parts;
        $currencies[$kod] = [
            'zeme' => $zeme,
            'mena' => $mena,
            'mnozstvi' => (int) $mnozstvi,
            'kod' => $kod,
            'kurz' => (float) str_replace(',', '.', $kurzStr),
        ];
    }

    // Testujeme, že máme USD
    if (isset($currencies['USD'])) {
        echo "   USD kurz: {$currencies['USD']['kurz']} CZK/{$currencies['USD']['mnozstvi']} {$currencies['USD']['kod']}\n";
    }

    // Testujeme, že máme EUR
    if (isset($currencies['EUR'])) {
        echo "   EUR kurz: {$currencies['EUR']['kurz']} CZK/{$currencies['EUR']['mnozstvi']} {$currencies['EUR']['kod']}\n";
    }

    echo "   Celkem měn: " . count($currencies) . "\n";

    return count($currencies) > 0 && isset($currencies['USD']) && isset($currencies['EUR']);
});

// =============================================================================
// 2. TEST KONVERZNÍCH KALKULACÍ
// =============================================================================

echo "🔄 Conversion Logic Tests\n";
echo str_repeat('-', 80) . "\n\n";

test("Conversion - USD to CZK", function() {
    $ch = curl_init('https://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $txt = curl_exec($ch);
    curl_close($ch);

    $lines = explode("\n", trim($txt));
    $currencies = [];
    for ($i = 2; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        if (empty($line)) continue;

        $parts = explode('|', $line);
        if (count($parts) !== 5) continue;

        [$zeme, $mena, $mnozstvi, $kod, $kurzStr] = $parts;
        $currencies[$kod] = [
            'mnozstvi' => (int) $mnozstvi,
            'kurz' => (float) str_replace(',', '.', $kurzStr),
        ];
    }

    if (!isset($currencies['USD'])) return false;

    $amount = 100;
    $usdRate = $currencies['USD']['kurz'];
    $usdAmount = $currencies['USD']['mnozstvi'];
    $resultCzk = ($amount / $usdAmount) * $usdRate;

    echo "   100 USD = " . round($resultCzk, 2) . " CZK\n";
    echo "   Kurz: {$usdRate} CZK/{$usdAmount} USD\n";

    return $resultCzk > 0;
});

test("Conversion - CZK to EUR", function() {
    $ch = curl_init('https://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $txt = curl_exec($ch);
    curl_close($ch);

    $lines = explode("\n", trim($txt));
    $currencies = [];
    for ($i = 2; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        if (empty($line)) continue;

        $parts = explode('|', $line);
        if (count($parts) !== 5) continue;

        [$zeme, $mena, $mnozstvi, $kod, $kurzStr] = $parts;
        $currencies[$kod] = [
            'mnozstvi' => (int) $mnozstvi,
            'kurz' => (float) str_replace(',', '.', $kurzStr),
        ];
    }

    if (!isset($currencies['EUR'])) return false;

    $amount = 1000;
    $eurRate = $currencies['EUR']['kurz'];
    $eurAmount = $currencies['EUR']['mnozstvi'];
    $resultEur = ($amount / $eurRate) * $eurAmount;

    echo "   1000 CZK = " . round($resultEur, 2) . " EUR\n";
    echo "   Kurz: {$eurRate} CZK/{$eurAmount} EUR\n";

    return $resultEur > 0;
});

test("Conversion - EUR to USD (cross-rate)", function() {
    $ch = curl_init('https://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $txt = curl_exec($ch);
    curl_close($ch);

    $lines = explode("\n", trim($txt));
    $currencies = [];
    for ($i = 2; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        if (empty($line)) continue;

        $parts = explode('|', $line);
        if (count($parts) !== 5) continue;

        [$zeme, $mena, $mnozstvi, $kod, $kurzStr] = $parts;
        $currencies[$kod] = [
            'mnozstvi' => (int) $mnozstvi,
            'kurz' => (float) str_replace(',', '.', $kurzStr),
        ];
    }

    if (!isset($currencies['EUR']) || !isset($currencies['USD'])) return false;

    $amount = 50;

    // EUR → CZK
    $eurRate = $currencies['EUR']['kurz'];
    $eurAmount = $currencies['EUR']['mnozstvi'];
    $inCzk = ($amount / $eurAmount) * $eurRate;

    // CZK → USD
    $usdRate = $currencies['USD']['kurz'];
    $usdAmount = $currencies['USD']['mnozstvi'];
    $resultUsd = ($inCzk / $usdRate) * $usdAmount;

    echo "   50 EUR = " . round($resultUsd, 2) . " USD\n";
    echo "   Přes CZK: 50 EUR → " . round($inCzk, 2) . " CZK → " . round($resultUsd, 2) . " USD\n";

    return $resultUsd > 0;
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
    echo "🎉 All ČNB tests passed!\n\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed. Please review the results above.\n\n";
    exit(1);
}
