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
use App\Services\BoredService;

#[Path('/bored')]
#[Tag('Fun APIs')]
final class BoredController extends BaseController
{
    public function __construct(
        private readonly BoredService $boredService
    ) {
    }

    #[Path('/')]
    #[Method('GET')]
    #[RequestParameter(name: 'type', type: 'string', in: 'query', required: false, description: 'Typ aktivity (education, recreational, social, diy, charity, cooking, relaxation, music, busywork)')]
    #[RequestParameter(name: 'participants', type: 'int', in: 'query', required: false, description: 'Počet účastníků (1-10)')]
        #[ApiResponse(code: '200', description: 'Návrh aktivity')]
    #[ApiResponse(code: '400', description: 'Neplatné parametry')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function getRandomActivity(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $type = $request->getParameter('type');
            $participantsStr = $request->getParameter('participants');
            $participants = $participantsStr !== null ? (int) $participantsStr : null;

            $data = $this->boredService->getRandomActivity($type, $participants);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/activity/{key}')]
    #[Method('GET')]
    #[RequestParameter(name: 'key', type: 'string', in: 'path', required: true, description: 'Unikátní klíč aktivity')]
        #[ApiResponse(code: '200', description: 'Detaily aktivity')]
    #[ApiResponse(code: '404', description: 'Aktivita nenalezena')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function getActivityByKey(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $key = $request->getParameter('key');
            $data = $this->boredService->getActivityByKey($key);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            $errorCode = str_contains($e->getMessage(), 'nenalezena') ? 404 : 500;
            return $this->createErrorResponse($response, $e->getMessage(), $errorCode);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
