<?php

declare(strict_types=1);

namespace App\Controllers;

use Apitte\Core\Attribute\Controller\Path;
use Apitte\Core\Attribute\Controller\Method;
use Apitte\Core\Attribute\Controller\Tag;
use Apitte\Core\Attribute\Controller\OpenApi;
use Apitte\Core\Attribute\Controller\Response as ApiResponse;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse as HttpApiResponse;
use App\Services\CatFactsService;

#[Path('/catfact')]
#[Tag('Fun APIs')]
#[OpenApi('
  Náhodné zajímavosti o kočkách z Cat Facts API.
  Vrací náhodné fakty o kočkách v angličtině.
')]
final class CatFactsController extends BaseController
{
    public function __construct(
        private readonly CatFactsService $catFactsService
    ) {
    }

    #[Path('/')]
    #[Method('GET')]
    #[OpenApi('
      Získá náhodnou zajímavost o kočkách.

      Příklad:
      - /catfact/ - náhodný cat fact
    ')]
    #[ApiResponse(code: 200, description: 'Náhodná zajímavost o kočkách')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function getRandomFact(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $this->catFactsService->getRandomFact();
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
