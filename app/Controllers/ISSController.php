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
use App\Services\ISSService;

#[Path('/iss')]
#[Tag('Space & Science')]
#[OpenApi('
  Sledování Mezinárodní vesmírné stanice (ISS).
  Poskytuje aktuální polohu ISS, časy přeletů a seznam astronautů ve vesmíru.
  Data o poloze jsou cachována 1 minutu.
')]
final class ISSController extends BaseController
{
    public function __construct(
        private readonly ISSService $issService
    ) {
    }

    #[Path('/position')]
    #[Method('GET')]
    #[OpenApi('
      Získá aktuální polohu ISS.

      Vrací zeměpisnou šířku a délku kde se právě nachází ISS.
    ')]
    #[ApiResponse(code: 200, description: 'Aktuální poloha ISS')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
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
    #[OpenApi('
      Získá časy kdy ISS přeletí nad danou polohou.

      Příklady:
      - /iss/pass?lat=50.0755&lon=14.4378 - přelety nad Prahou
      - /iss/pass?lat=40.7128&lon=-74.0060&n=10 - 10 přeletů nad New Yorkem
    ')]
    #[ApiResponse(code: 200, description: 'Časy přeletů ISS')]
    #[ApiResponse(code: 400, description: 'Neplatné parametry')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
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
    #[OpenApi('
      Získá seznam astronautů aktuálně ve vesmíru.

      Vrací jména a lodi (ISS, Tiangong, atd.) všech lidí aktuálně ve vesmíru.
    ')]
    #[ApiResponse(code: 200, description: 'Seznam astronautů ve vesmíru')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
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
