<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class FunApiTest extends Tester\TestCase
{
    public function testAdvice(): void
    {
        $response = ApiTestHelper::get('/advice');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testJoke(): void
    {
        $response = ApiTestHelper::get('/joke');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testJokeCz(): void
    {
        $response = ApiTestHelper::get('/joke/cz');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testChuckNorris(): void
    {
        $response = ApiTestHelper::get('/chucknorris');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testChuckNorrisCategory(): void
    {
        $response = ApiTestHelper::get('/chucknorris/dev');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testQuote(): void
    {
        $response = ApiTestHelper::get('/quote');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testTrivia(): void
    {
        $response = ApiTestHelper::get('/trivia');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testNumberFact(): void
    {
        $response = ApiTestHelper::get('/number/42');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }

    public function testBored(): void
    {
        $response = ApiTestHelper::get('/bored');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['data']));
    }
}

(new FunApiTest())->run();
