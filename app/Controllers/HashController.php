<?php

declare(strict_types=1);

namespace App\Controllers;

use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Tag;
use Apitte\Core\Annotation\Controller\OpenApi;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Annotation\Controller\Response as ApiResponse;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse as HttpApiResponse;
use App\Services\HashService;

#[Path('/hash')]
#[Tag('Utility APIs')]
final class HashController extends BaseController
{
    public function __construct(
        private readonly HashService $hashService
    ) {
    }

    #[Path('/')]
    #[Method('GET')]
    #[RequestParameter(name: 'data', type: 'string', in: 'query', required: true, description: 'Data k zahashování')]
    #[RequestParameter(name: 'algo', type: 'string', in: 'query', required: false, description: 'Algoritmus: md5, sha1, sha256, sha512 (výchozí sha256)')]
        #[ApiResponse(code: '200', description: 'Hash vypočítán')]
    #[ApiResponse(code: '400', description: 'Neplatná data nebo algoritmus')]
    public function hash(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $request->getParameter('data');
            $algo = $request->getParameter('algo') ?? 'sha256';

            if ($data === null || $data === '') {
                return $this->createErrorResponse($response, 'Data jsou povinná', 400);
            }

            $result = $this->hashService->hash($data, $algo);
            return $this->createSuccessResponse($response, $result);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při hashování: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/base64/encode')]
    #[Method('GET')]
    #[RequestParameter(name: 'data', type: 'string', in: 'query', required: true, description: 'Data ke kódování')]
        #[ApiResponse(code: '200', description: 'Data zakódována')]
    public function base64Encode(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $request->getParameter('data');

            if ($data === null) {
                return $this->createErrorResponse($response, 'Data jsou povinná', 400);
            }

            $result = $this->hashService->base64Encode($data);
            return $this->createSuccessResponse($response, $result);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při kódování: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/base64/decode')]
    #[Method('GET')]
    #[RequestParameter(name: 'data', type: 'string', in: 'query', required: true, description: 'Base64 data k dekódování')]
        #[ApiResponse(code: '200', description: 'Data dekódována')]
    #[ApiResponse(code: '400', description: 'Neplatná Base64 data')]
    public function base64Decode(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $request->getParameter('data');

            if ($data === null) {
                return $this->createErrorResponse($response, 'Data jsou povinná', 400);
            }

            $result = $this->hashService->base64Decode($data);
            return $this->createSuccessResponse($response, $result);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při dekódování: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/hex/encode')]
    #[Method('GET')]
    #[RequestParameter(name: 'data', type: 'string', in: 'query', required: true, description: 'Data ke kódování')]
        #[ApiResponse(code: '200', description: 'Data zakódována')]
    public function hexEncode(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $request->getParameter('data');

            if ($data === null) {
                return $this->createErrorResponse($response, 'Data jsou povinná', 400);
            }

            $result = $this->hashService->hexEncode($data);
            return $this->createSuccessResponse($response, $result);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při kódování: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/hex/decode')]
    #[Method('GET')]
    #[RequestParameter(name: 'data', type: 'string', in: 'query', required: true, description: 'HEX data k dekódování')]
        #[ApiResponse(code: '200', description: 'Data dekódována')]
    #[ApiResponse(code: '400', description: 'Neplatná HEX data')]
    public function hexDecode(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $request->getParameter('data');

            if ($data === null) {
                return $this->createErrorResponse($response, 'Data jsou povinná', 400);
            }

            $result = $this->hashService->hexDecode($data);
            return $this->createSuccessResponse($response, $result);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při dekódování: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/hmac')]
    #[Method('GET')]
    #[RequestParameter(name: 'data', type: 'string', in: 'query', required: true, description: 'Data pro HMAC')]
    #[RequestParameter(name: 'key', type: 'string', in: 'query', required: true, description: 'Tajný klíč')]
    #[RequestParameter(name: 'algo', type: 'string', in: 'query', required: false, description: 'Algoritmus (výchozí sha256)')]
        #[ApiResponse(code: '200', description: 'HMAC vypočítán')]
    #[ApiResponse(code: '400', description: 'Chybějící parametry')]
    public function hmac(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $request->getParameter('data');
            $key = $request->getParameter('key');
            $algo = $request->getParameter('algo') ?? 'sha256';

            if ($data === null || $key === null) {
                return $this->createErrorResponse($response, 'Data a klíč jsou povinné', 400);
            }

            $result = $this->hashService->hmac($data, $key, $algo);
            return $this->createSuccessResponse($response, $result);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při výpočtu HMAC: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/algorithms')]
    #[Method('GET')]
        #[ApiResponse(code: '200', description: 'Seznam algoritmů')]
    public function getAlgorithms(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $result = $this->hashService->getSupportedAlgorithms();
            return $this->createSuccessResponse($response, $result);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
