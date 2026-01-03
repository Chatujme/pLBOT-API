<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class CzechApiTest extends Tester\TestCase
{
    public function testPocasiDnes(): void
    {
        $response = ApiTestHelper::get('/pocasi/dnes');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testPocasiZitra(): void
    {
        $response = ApiTestHelper::get('/pocasi/zitra');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testHoroskopBeran(): void
    {
        $response = ApiTestHelper::get('/horoskop/beran');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testHoroskopLev(): void
    {
        $response = ApiTestHelper::get('/horoskop/lev');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testTvProgram(): void
    {
        $response = ApiTestHelper::get('/tv');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testTvCt1(): void
    {
        $response = ApiTestHelper::get('/tv/ct1');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testAresCompany(): void
    {
        $response = ApiTestHelper::get('/ares/ico/25596641');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        // Can be error if external API is down
        Assert::true(isset($response['json']['data']) || isset($response['json']['error']));
    }
}

(new CzechApiTest())->run();
