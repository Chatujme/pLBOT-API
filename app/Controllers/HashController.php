<?php

declare(strict_types=1);

namespace App\Controllers;

use Apitte\Core\Attribute\Controller\Path;
use Apitte\Core\Attribute\Controller\Method;
use Apitte\Core\Attribute\Controller\Tag;
use Apitte\Core\Attribute\Controller\OpenApi;
use Apitte\Core\Attribute\Controller\RequestParameter;
use Apitte\Core\Attribute\Controller\Response as ApiResponse;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse as HttpApiResponse;
use App\Services\HashService;

#[Path('/hash')]
#[Tag('Utility APIs')]
#[OpenApi('
  Hashování a kódování dat pomocí různých algoritmů.
  Podporuje MD5, SHA-1, SHA-256, SHA-512, Base64, Hex, HMAC a další.
  Všechny operace jsou prováděny lokálně (žádné externí API).
')]
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
    #[OpenApi('
      Vypočítá hash pro zadaná data.

      Příklady:
      - /hash/?data=password&algo=md5
      - /hash/?data=secret&algo=sha256
      - /hash/?data=test&algo=sha512

      Použití v IRC:
      !hash md5 "password" → 5f4dcc3b5aa765d61d8327deb882cf99
      !hash sha256 "secret" → 2bb...
    ')]
    #[ApiResponse(code: 200, description: 'Hash vypočítán')]
    #[ApiResponse(code: 400, description: 'Neplatná data nebo algoritmus')]
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
    #[OpenApi('
      Kóduje data do Base64.

      Příklady:
      - /hash/base64/encode?data=Hello%20World → SGVsbG8gV29ybGQ=

      Použití v IRC:
      !base64encode "Hello World" → SGVsbG8gV29ybGQ=
    ')]
    #[ApiResponse(code: 200, description: 'Data zakódována')]
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
    #[OpenApi('
      Dekóduje Base64 data.

      Příklady:
      - /hash/base64/decode?data=SGVsbG8gV29ybGQ= → Hello World

      Použití v IRC:
      !base64decode "SGVsbG8gV29ybGQ=" → Hello World
    ')]
    #[ApiResponse(code: 200, description: 'Data dekódována')]
    #[ApiResponse(code: 400, description: 'Neplatná Base64 data')]
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
    #[OpenApi('Kóduje data do HEX formátu')]
    #[ApiResponse(code: 200, description: 'Data zakódována')]
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
    #[OpenApi('Dekóduje HEX data')]
    #[ApiResponse(code: 200, description: 'Data dekódována')]
    #[ApiResponse(code: 400, description: 'Neplatná HEX data')]
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
    #[OpenApi('
      Vypočítá HMAC (Hash-based Message Authentication Code).

      HMAC se používá pro ověření integrity a autenticity dat.

      Příklady:
      - /hash/hmac?data=message&key=secret&algo=sha256

      Použití:
      - API signature verification
      - Webhook payload verification
      - Message authentication
    ')]
    #[ApiResponse(code: 200, description: 'HMAC vypočítán')]
    #[ApiResponse(code: 400, description: 'Chybějící parametry')]
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
    #[OpenApi('
      Vrátí seznam všech podporovaných hash algoritmů.

      Užitečné pro zjištění, jaké algoritmy jsou dostupné na serveru.
    ')]
    #[ApiResponse(code: 200, description: 'Seznam algoritmů')]
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
