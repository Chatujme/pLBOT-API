<?php

declare(strict_types=1);

namespace App\Controllers;

use Apitte\Core\Http\ApiResponse;
use Contributte\Psr7\Psr7ResponseFactory;

/**
 * Základní controller pro všechny API controllery
 */
abstract class BaseController
{
    /**
     * Vytvoří úspěšnou JSON odpověď
     *
     * @param array<mixed> $data
     */
    protected function createSuccessResponse(ApiResponse $response, array $data, int $code = 200): ApiResponse
    {
        return $response
            ->withStatus($code)
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->writeJsonBody($data);
    }

    /**
     * Vytvoří chybovou JSON odpověď
     *
     * @param array<string, mixed>|null $additionalData
     */
    protected function createErrorResponse(
        ApiResponse $response,
        string $message,
        int $code = 400,
        ?array $additionalData = null
    ): ApiResponse {
        $data = [
            'error' => [
                'message' => $message,
                'code' => $code,
            ],
        ];

        if ($additionalData !== null) {
            $data = array_merge($data, $additionalData);
        }

        return $response
            ->withStatus($code)
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->writeJsonBody($data);
    }

    /**
     * Vytvoří odpověď s vlastními daty
     *
     * @param array<mixed> $data
     */
    protected function createJsonResponse(ApiResponse $response, array $data, int $code = 200): ApiResponse
    {
        return $response
            ->withStatus($code)
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->writeJsonBody($data);
    }
}
