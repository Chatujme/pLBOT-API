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
use App\Services\CountriesService;

#[Path('/countries')]
#[Tag('Geography & Data')]
#[OpenApi('
  Informace o zemích světa z REST Countries API.
  Poskytuje detailní informace o zemích včetně hlavních měst, jazyků, měn a dalších údajů.
  Data jsou cachována 1 týden.
')]
final class CountriesController extends BaseController
{
    public function __construct(
        private readonly CountriesService $countriesService
    ) {
    }

    #[Path('/{country}')]
    #[Method('GET')]
    #[RequestParameter(name: 'country', type: 'string', in: 'path', required: true, description: 'Kód země (CZ, US, DE) nebo název (Czechia, Germany)')]
    #[OpenApi('
      Získá detailní informace o zemi.

      Příklady:
      - /countries/CZ - informace o České republice
      - /countries/US - informace o USA
      - /countries/Germany - informace o Německu
    ')]
    #[ApiResponse(code: 200, description: 'Informace o zemi')]
    #[ApiResponse(code: 404, description: 'Země nenalezena')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function getCountry(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $country = $request->getParameter('country');
            $data = $this->countriesService->getCountry($country);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            $errorCode = str_contains($e->getMessage(), 'nenalezena') ? 404 : 500;
            return $this->createErrorResponse($response, $e->getMessage(), $errorCode);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/region/{region}')]
    #[Method('GET')]
    #[RequestParameter(name: 'region', type: 'string', in: 'path', required: true, description: 'Region (europe, asia, africa, americas, oceania)')]
    #[OpenApi('
      Získá seznam zemí v daném regionu.

      Příklady:
      - /countries/region/europe - země v Evropě
      - /countries/region/asia - země v Asii
      - /countries/region/africa - země v Africe
    ')]
    #[ApiResponse(code: 200, description: 'Seznam zemí v regionu')]
    #[ApiResponse(code: 400, description: 'Neplatný region')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function getCountriesByRegion(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $region = $request->getParameter('region');
            $data = $this->countriesService->getCountriesByRegion($region);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/all')]
    #[Method('GET')]
    #[OpenApi('
      Získá seznam všech zemí světa.

      Vrací základní informace o všech zemích (název, kód, region).
    ')]
    #[ApiResponse(code: 200, description: 'Seznam všech zemí')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function getAllCountries(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $this->countriesService->getAllCountries();
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
