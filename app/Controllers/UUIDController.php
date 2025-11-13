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
use App\Services\UUIDService;

#[Path('/uuid')]
#[Tag('Utilities')]
#[OpenApi('
  Generování a validace UUID (Universally Unique Identifier).
  Podporuje generování UUID v4 a validaci existujících UUID.
')]
final class UUIDController extends BaseController
{
    public function __construct(
        private readonly UUIDService $uuidService
    ) {
    }

    #[Path('/')]
    #[Method('GET')]
    #[RequestParameter(name: 'count', type: 'int', in: 'query', required: false, description: 'Počet UUID (1-100, výchozí 1)')]
    #[OpenApi('
      Generuje nové UUID v4.

      Příklady:
      - /uuid/ - jedno UUID
      - /uuid/?count=5 - pět UUID
      - /uuid/?count=10 - deset UUID
    ')]
    #[ApiResponse(code: 200, description: 'Vygenerované UUID')]
    #[ApiResponse(code: 400, description: 'Neplatný počet')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function generate(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $countStr = $request->getParameter('count');
            $count = $countStr !== null ? (int) $countStr : 1;

            if ($count === 1) {
                $data = $this->uuidService->generateV4();
            } else {
                $data = $this->uuidService->generateMultiple($count);
            }

            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/validate/{uuid}')]
    #[Method('GET')]
    #[RequestParameter(name: 'uuid', type: 'string', in: 'path', required: true, description: 'UUID k validaci')]
    #[OpenApi('
      Validuje UUID a vrací informace o verzi a variantě.

      Příklad:
      - /uuid/validate/550e8400-e29b-41d4-a716-446655440000
    ')]
    #[ApiResponse(code: 200, description: 'Výsledek validace UUID')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function validate(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $uuid = $request->getParameter('uuid');
            $data = $this->uuidService->validate($uuid);
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/nil')]
    #[Method('GET')]
    #[OpenApi('
      Vrací NIL UUID (všechny nuly).

      NIL UUID je speciální UUID používané jako nulová hodnota.
    ')]
    #[ApiResponse(code: 200, description: 'NIL UUID')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function getNil(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $this->uuidService->generateNil();
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
