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
use App\Services\CountriesService;

#[Path('/countries')]
#[Tag('Geography & Data')]
final class CountriesController extends BaseController
{
    public function __construct(
        private readonly CountriesService $countriesService
    ) {
    }

    #[Path('/{country}')]
    #[Method('GET')]
    #[RequestParameter(name: 'country', type: 'string', in: 'path', required: true, description: 'Kód země (CZ, US, DE) nebo název (Czechia, Germany)')]
        #[ApiResponse(code: '200', description: 'Informace o zemi')]
    #[ApiResponse(code: '404', description: 'Země nenalezena')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
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
        #[ApiResponse(code: '200', description: 'Seznam zemí v regionu')]
    #[ApiResponse(code: '400', description: 'Neplatný region')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
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
        #[ApiResponse(code: '200', description: 'Seznam všech zemí')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
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
