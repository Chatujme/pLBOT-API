<?php

declare(strict_types=1);

/**
 * Test pro ARES API - s reálnými HTTP požadavky
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "🏢 ARES Registr Firem API - Komplexní Test\n";
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
// 1. TEST ARES API - Přímý přístup k ARES
// =============================================================================

echo "📊 ARES Source API Tests (JSON API)\n";
echo str_repeat('-', 80) . "\n\n";

test("ARES - vyhledání firmy podle IČO (Seznam.cz)", function() {
    $ico = '45274649'; // Seznam.cz a.s.

    $ch = curl_init('https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/vyhledat?ico=' . $ico);
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

    if (!isset($data['ekonomickeSubjekty']) || count($data['ekonomickeSubjekty']) === 0) {
        echo "   Firma nebyla nalezena\n";
        return false;
    }

    $subjekt = $data['ekonomickeSubjekty'][0];

    echo "   IČO: {$subjekt['ico']}\n";
    echo "   Název: {$subjekt['obchodniJmeno']}\n";

    if (isset($subjekt['sidlo']['textovaAdresa'])) {
        echo "   Adresa: {$subjekt['sidlo']['textovaAdresa']}\n";
    }

    if (isset($subjekt['dic'])) {
        echo "   DIČ: {$subjekt['dic']}\n";
    }

    return true;
});

test("ARES - vyhledání firmy podle IČO (Škoda Auto)", function() {
    $ico = '00177041'; // Škoda Auto a.s.

    $ch = curl_init('https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/vyhledat?ico=' . $ico);
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

    if (!isset($data['ekonomickeSubjekty']) || count($data['ekonomickeSubjekty']) === 0) {
        echo "   Firma nebyla nalezena\n";
        return false;
    }

    $subjekt = $data['ekonomickeSubjekty'][0];

    echo "   IČO: {$subjekt['ico']}\n";
    echo "   Název: {$subjekt['obchodniJmeno']}\n";

    return isset($subjekt['obchodniJmeno']) && str_contains(strtolower($subjekt['obchodniJmeno']), 'škoda');
});

test("ARES - vyhledání firem podle názvu", function() {
    $nazev = 'google';

    $ch = curl_init('https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/vyhledat?obchodniJmeno=' . urlencode($nazev));
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

    if (!isset($data['ekonomickeSubjekty'])) {
        echo "   Žádné firmy nenalezeny\n";
        return false;
    }

    $count = count($data['ekonomickeSubjekty']);
    echo "   Počet nalezených firem: {$count}\n";

    if ($count > 0) {
        echo "   První firma: {$data['ekonomickeSubjekty'][0]['obchodniJmeno']}\n";
        echo "   IČO: {$data['ekonomickeSubjekty'][0]['ico']}\n";
    }

    return $count > 0;
});

test("ARES - parsování adresy", function() {
    $ico = '27082440'; // Slevomatu s.r.o.

    $ch = curl_init('https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/vyhledat?ico=' . $ico);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $json = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($json, true);

    if (!isset($data['ekonomickeSubjekty'][0])) {
        return false;
    }

    $subjekt = $data['ekonomickeSubjekty'][0];
    $sidlo = $subjekt['sidlo'] ?? null;

    if ($sidlo === null) {
        echo "   Sídlo není k dispozici\n";
        return false;
    }

    // Zkusíme sestavit adresu
    $adresaParts = [];

    if (isset($sidlo['nazevUlice'])) {
        $ulice = $sidlo['nazevUlice'];
        if (isset($sidlo['cisloDomovni'])) {
            $ulice .= ' ' . $sidlo['cisloDomovni'];
            if (isset($sidlo['cisloOrientacni'])) {
                $ulice .= '/' . $sidlo['cisloOrientacni'];
            }
        }
        $adresaParts[] = $ulice;
    }

    if (isset($sidlo['nazevObce'])) {
        $obec = $sidlo['nazevObce'];
        if (isset($sidlo['psc'])) {
            $obec .= ', ' . $sidlo['psc'];
        }
        $adresaParts[] = $obec;
    }

    $adresa = implode(', ', $adresaParts);

    echo "   Adresa: {$adresa}\n";
    echo "   Textová adresa: " . ($sidlo['textovaAdresa'] ?? 'N/A') . "\n";

    return !empty($adresa);
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
    echo "🎉 All ARES tests passed!\n\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed. Please review the results above.\n\n";
    exit(1);
}
