<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class UtilityTest extends Tester\TestCase
{
    public function testUuid(): void
    {
        $response = ApiTestHelper::get('/uuid');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
        Assert::true(isset($response['json']['data']['uuid']));
        // Validate UUID format
        Assert::match('~^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$~', $response['json']['data']['uuid']);
    }

    public function testUuidMultiple(): void
    {
        $response = ApiTestHelper::get('/uuid/5');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
        Assert::true(isset($response['json']['data']['uuids']));
        Assert::count(5, $response['json']['data']['uuids']);
    }

    public function testHashMd5(): void
    {
        $response = ApiTestHelper::get('/hash/md5?text=test');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
        Assert::true(isset($response['json']['data']['hash']));
        Assert::same('098f6bcd4621d373cade4e832627b4f6', $response['json']['data']['hash']);
    }

    public function testHashSha256(): void
    {
        $response = ApiTestHelper::get('/hash/sha256?text=test');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
        Assert::true(isset($response['json']['data']['hash']));
        Assert::same('9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08', $response['json']['data']['hash']);
    }

    public function testQrCode(): void
    {
        $response = ApiTestHelper::get('/qr/generate?text=Hello');

        Assert::same(200, $response['status']);
        // QR code returns image
        Assert::contains('image', $response['contentType']);
    }

    public function testUrlShorten(): void
    {
        $response = ApiTestHelper::post('/url/shorten', ['url' => 'https://example.com']);

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }
}

(new UtilityTest())->run();
