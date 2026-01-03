<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class AdminApiTest extends Tester\TestCase
{
    private ?string $authToken = null;

    public function testLoginSuccess(): void
    {
        $response = ApiTestHelper::post('/admin/login', [
            'username' => 'admin',
            'password' => 'admin',
        ]);

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['token']));

        $this->authToken = $response['json']['token'];
    }

    public function testLoginFailure(): void
    {
        $response = ApiTestHelper::post('/admin/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        Assert::same(401, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['error']));
    }

    public function testStatsEndpoint(): void
    {
        $response = ApiTestHelper::get('/admin/stats');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['totalRequests']));
        Assert::true(isset($response['json']['successRate']));
        Assert::true(isset($response['json']['avgResponseTime']));
        Assert::true(isset($response['json']['topEndpoints']));
    }

    public function testOpenApiSpec(): void
    {
        $response = ApiTestHelper::get('/openapi/spec');

        Assert::same(200, $response['status']);
        Assert::type('array', $response['json']);
        Assert::true(isset($response['json']['openapi']));
        Assert::true(isset($response['json']['info']));
        Assert::true(isset($response['json']['paths']));
    }
}

(new AdminApiTest())->run();
