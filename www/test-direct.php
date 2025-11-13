<?php

declare(strict_types=1);

/**
 * Direct test script for API services (bez Apitte frameworku)
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Mock autoloader
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../app/' . str_replace('\\', '/', str_replace('App\\', '', $class)) . '.php';
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    return false;
});

// Mock Nette Cache
class MockCache {
    public function load($key) { return null; }
    public function save($key, $data, $options = []) {}
}

// Test Services přímo
echo "🧪 Testing pLBOT API Services\n";
echo str_repeat('=', 80) . "\n\n";

use App\Services\HttpClientService;
use App\Services\SvatkyService;
use App\Services\PocasiService;

$httpClient = new HttpClientService();
$cache = new MockCache();

// Test 1: Svátky Service
echo "1️⃣ Testing SvatkyService...\n";
try {
    $svatkyService = new SvatkyService($httpClient, $cache);
    $result = $svatkyService->getSvatek('dnes');
    echo "   ✅ Result: " . json_encode($result, JSON_UNESCAPED_UNICODE) . "\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Počasí Service
echo "2️⃣ Testing PocasiService...\n";
try {
    $pocasiService = new PocasiService($httpClient, $cache);
    $result = $pocasiService->getPocasi('dnes', 'praha');
    echo "   ✅ Result: " . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

echo str_repeat('=', 80) . "\n";
echo "Tests completed!\n";
