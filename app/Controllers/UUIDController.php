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
use App\Services\UUIDService;

#[Path('/uuid')]
#[Tag('Utilities')]
final class UUIDController extends BaseController
{
    public function __construct(
        private readonly UUIDService $uuidService
    ) {
    }

    #[Path('/')]
    #[Method('GET')]
    #[RequestParameter(name: 'count', type: 'int', in: 'query', required: false, description: 'Počet UUID (1-100, výchozí 1)')]
        #[ApiResponse(code: '200', description: 'Vygenerované UUID')]
    #[ApiResponse(code: '400', description: 'Neplatný počet')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
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
        #[ApiResponse(code: '200', description: 'Výsledek validace UUID')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
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
        #[ApiResponse(code: '200', description: 'NIL UUID')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
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
