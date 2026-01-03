<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class CnbTest extends Tester\TestCase
{
    public function testGetKurzy(): void
    {
        $response = ApiTestHelper::get('/cnb/kurzy');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testGetKurzyEur(): void
    {
        $response = ApiTestHelper::get('/cnb/kurzy/eur');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testGetKurzyUsd(): void
    {
        $response = ApiTestHelper::get('/cnb/kurzy/usd');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }
}

(new CnbTest())->run();
