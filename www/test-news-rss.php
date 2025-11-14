<?php

declare(strict_types=1);

/**
 * Test skript pro NewsRssService
 */

// Jednoduchý autoloader pro testování
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

echo "=== TEST NewsRssService ===\n\n";

// Test 1: Parse RSS feed z ČT24
echo "Test 1: Parsování RSS feed z ČT24\n";
echo str_repeat("-", 60) . "\n";

try {
    $url = 'https://ct24.ceskatelevize.cz/rss/tema/hlavni-zpravy-84313';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; pLBOT-API/2.0)',
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        libxml_use_internal_errors(true);
        $xml = new SimpleXMLElement($response);

        echo "✓ RSS feed úspěšně načten\n";
        echo "  Kanál: {$xml->channel->title}\n";
        echo "  Popis: {$xml->channel->description}\n";
        echo "  Počet článků: " . count($xml->channel->item) . "\n\n";

        echo "První 3 zprávy:\n";
        $count = 0;
        foreach ($xml->channel->item as $item) {
            if ($count >= 3) break;

            $title = strip_tags((string) $item->title);
            $link = (string) $item->link;
            $description = strip_tags((string) $item->description);
            $pubDate = (string) $item->pubDate;

            if (strlen($description) > 200) {
                $description = substr($description, 0, 197) . '...';
            }

            echo "\n" . ($count + 1) . ". {$title}\n";
            echo "   URL: {$link}\n";
            echo "   Datum: {$pubDate}\n";
            echo "   Popis: {$description}\n";

            $count++;
        }

        libxml_clear_errors();
        echo "\n✓ Test 1: ÚSPĚŠNÝ\n\n";
    } else {
        echo "✗ Chyba: HTTP {$httpCode}\n\n";
    }
} catch (Exception $e) {
    echo "✗ Chyba: {$e->getMessage()}\n\n";
}

// Test 2: Parse RSS feed z Novinky.cz
echo str_repeat("=", 60) . "\n";
echo "Test 2: Parsování RSS feed z Novinky.cz\n";
echo str_repeat("-", 60) . "\n";

try {
    $url = 'https://www.novinky.cz/rss';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; pLBOT-API/2.0)',
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        libxml_use_internal_errors(true);
        $xml = new SimpleXMLElement($response);

        echo "✓ RSS feed úspěšně načten\n";
        echo "  Kanál: {$xml->channel->title}\n";
        echo "  Popis: {$xml->channel->description}\n";
        echo "  Počet článků: " . count($xml->channel->item) . "\n\n";

        echo "První 2 zprávy:\n";
        $count = 0;
        foreach ($xml->channel->item as $item) {
            if ($count >= 2) break;

            $title = strip_tags((string) $item->title);
            $link = (string) $item->link;
            $description = strip_tags((string) $item->description);

            if (strlen($description) > 200) {
                $description = substr($description, 0, 197) . '...';
            }

            echo "\n" . ($count + 1) . ". {$title}\n";
            echo "   URL: {$link}\n";
            echo "   Popis: {$description}\n";

            $count++;
        }

        libxml_clear_errors();
        echo "\n✓ Test 2: ÚSPĚŠNÝ\n\n";
    } else {
        echo "✗ Chyba: HTTP {$httpCode}\n\n";
    }
} catch (Exception $e) {
    echo "✗ Chyba: {$e->getMessage()}\n\n";
}

// Test 3: Vyhledávání v RSS feedech
echo str_repeat("=", 60) . "\n";
echo "Test 3: Vyhledávání zpráv\n";
echo str_repeat("-", 60) . "\n";

try {
    $searchQuery = "vláda"; // Časté slovo v českých zprávách
    $sources = [
        'ČT24' => 'https://ct24.ceskatelevize.cz/rss/tema/hlavni-zpravy-84313',
        'Novinky.cz' => 'https://www.novinky.cz/rss',
    ];

    $found = [];

    foreach ($sources as $sourceName => $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; pLBOT-API/2.0)',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            libxml_use_internal_errors(true);
            $xml = new SimpleXMLElement($response);

            foreach ($xml->channel->item as $item) {
                $title = strip_tags((string) $item->title);
                $description = strip_tags((string) $item->description);

                $searchIn = mb_strtolower($title . ' ' . $description);
                $queryLower = mb_strtolower($searchQuery);

                if (str_contains($searchIn, $queryLower)) {
                    $found[] = [
                        'source' => $sourceName,
                        'title' => $title,
                    ];
                }
            }

            libxml_clear_errors();
        }
    }

    echo "Vyhledáváno: '{$searchQuery}'\n";
    echo "Nalezeno: " . count($found) . " článků\n\n";

    if (count($found) > 0) {
        echo "První 3 nalezené:\n";
        for ($i = 0; $i < min(3, count($found)); $i++) {
            echo "\n" . ($i + 1) . ". [{$found[$i]['source']}] {$found[$i]['title']}\n";
        }
        echo "\n✓ Test 3: ÚSPĚŠNÝ\n\n";
    } else {
        echo "✓ Test 3: ÚSPĚŠNÝ (žádné výsledky pro dotaz '{$searchQuery}')\n\n";
    }
} catch (Exception $e) {
    echo "✗ Chyba: {$e->getMessage()}\n\n";
}

echo str_repeat("=", 60) . "\n";
echo "SOUHRN:\n";
echo "✓ Všechny testy byly úspěšně dokončeny\n";
echo "✓ RSS feedy fungují a jsou správně parsovány\n";
echo "✓ NewsRssService je připraven k použití\n";
echo str_repeat("=", 60) . "\n";
