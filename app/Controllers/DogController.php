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
use App\Services\DogService;

#[Path('/dog')]
#[Tag('Fun APIs')]
#[OpenApi('
  Náhodné obrázky psů z Dog CEO API.
  Vrací náhodné obrázky psů nebo konkrétních plemen.
')]
final class DogController extends BaseController
{
    public function __construct(
        private readonly DogService $dogService
    ) {
    }

    #[Path('/')]
    #[Method('GET')]
    #[RequestParameter(name: 'breed', type: 'string', in: 'query', required: false, description: 'Plemeno psa (např. husky, beagle, corgi)')]
    #[OpenApi('
      Získá náhodný obrázek psa.

      Příklady:
      - /dog/ - náhodný pes
      - /dog/?breed=husky - obrázek husky
      - /dog/?breed=corgi - obrázek corgi
    ')]
    #[ApiResponse(code: 200, description: 'Náhodný obrázek psa')]
    #[ApiResponse(code: 400, description: 'Neplatné plemeno')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function getRandomDog(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $breed = $request->getParameter('breed');
            $data = $this->dogService->getRandomDog($breed);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/breeds')]
    #[Method('GET')]
    #[OpenApi('
      Získá seznam všech dostupných plemen psů.

      Vrací kompletní seznam plemen včetně sub-plemen.
    ')]
    #[ApiResponse(code: 200, description: 'Seznam všech plemen')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function getBreeds(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $this->dogService->getBreeds();
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
