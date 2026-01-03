<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class SvatkyTest extends Tester\TestCase
{
    public function testGetToday(): void
    {
        $response = ApiTestHelper::get('/svatky/today');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
        Assert::true(isset($response['json']['data']['dnes']));
        Assert::true(isset($response['json']['data']['zitra']));
        Assert::true(isset($response['json']['data']['vcera']));
    }

    public function testGetDnes(): void
    {
        $response = ApiTestHelper::get('/svatky/dnes');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
        Assert::type('string', $response['json']['data']);
    }

    public function testGetZitra(): void
    {
        $response = ApiTestHelper::get('/svatky/zitra');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testGetVcera(): void
    {
        $response = ApiTestHelper::get('/svatky/vcera');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testGetPredevcirem(): void
    {
        $response = ApiTestHelper::get('/svatky/predevcirem');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }
}

(new SvatkyTest())->run();
