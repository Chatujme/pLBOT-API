<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class AnimalApiTest extends Tester\TestCase
{
    public function testCatFact(): void
    {
        $response = ApiTestHelper::get('/catfact');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testCatFactMultiple(): void
    {
        $response = ApiTestHelper::get('/catfact/3');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testDogImage(): void
    {
        $response = ApiTestHelper::get('/dog');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
        Assert::true(isset($response['json']['data']['image']));
    }

    public function testDogBreed(): void
    {
        $response = ApiTestHelper::get('/dog/breeds');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testFoxImage(): void
    {
        $response = ApiTestHelper::get('/fox');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
        Assert::true(isset($response['json']['data']['image']));
    }
}

(new AnimalApiTest())->run();
