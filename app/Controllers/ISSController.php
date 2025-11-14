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
use App\Services\ISSService;

#[Path('/iss')]
#[Tag('Space & Science')]
final class ISSController extends BaseController
{
    public function __construct(
        private readonly ISSService $issService
    ) {
    }

    #[Path('/position')]
    #[Method('GET')]
        #[ApiResponse(code: '200', description: 'Aktuální poloha ISS')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function getCurrentPosition(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $this->issService->getCurrentPosition();
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/pass')]
    #[Method('GET')]
    #[RequestParameter(name: 'lat', type: 'float', in: 'query', required: true, description: 'Zeměpisná šířka (-90 až 90)')]
    #[RequestParameter(name: 'lon', type: 'float', in: 'query', required: true, description: 'Zeměpisná délka (-180 až 180)')]
    #[RequestParameter(name: 'n', type: 'int', in: 'query', required: false, description: 'Počet přeletů (1-100, výchozí 5)')]
        #[ApiResponse(code: '200', description: 'Časy přeletů ISS')]
    #[ApiResponse(code: '400', description: 'Neplatné parametry')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function getPassTimes(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $latStr = $request->getParameter('lat');
            $lonStr = $request->getParameter('lon');
            $nStr = $request->getParameter('n');

            if ($latStr === null || $lonStr === null) {
                return $this->createErrorResponse($response, 'Chybí povinné parametry: lat, lon', 400);
            }

            $latitude = (float) $latStr;
            $longitude = (float) $lonStr;
            $passes = $nStr !== null ? (int) $nStr : 5;

            $data = $this->issService->getPassTimes($latitude, $longitude, $passes);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/astronauts')]
    #[Method('GET')]
        #[ApiResponse(code: '200', description: 'Seznam astronautů ve vesmíru')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function getAstronauts(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $this->issService->getAstronauts();
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
