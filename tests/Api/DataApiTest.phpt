<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class DataApiTest extends Tester\TestCase
{
    public function testCountriesAll(): void
    {
        $response = ApiTestHelper::get('/countries');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
        Assert::true(is_array($response['json']['data']));
    }

    public function testCountryByCode(): void
    {
        $response = ApiTestHelper::get('/countries/CZ');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testCountrySearch(): void
    {
        $response = ApiTestHelper::get('/countries/search?name=Czech');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testCryptoPrice(): void
    {
        $response = ApiTestHelper::get('/crypto/bitcoin');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testCryptoList(): void
    {
        $response = ApiTestHelper::get('/crypto/list');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testIssLocation(): void
    {
        $response = ApiTestHelper::get('/iss');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testVatValidate(): void
    {
        $response = ApiTestHelper::get('/vat/validate?vat=CZ12345678');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testNewsRss(): void
    {
        $response = ApiTestHelper::get('/news');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testNewsByCategory(): void
    {
        $response = ApiTestHelper::get('/news/sport');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }
}

(new DataApiTest())->run();
