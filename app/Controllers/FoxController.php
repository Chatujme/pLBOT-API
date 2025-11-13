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
use App\Services\FoxService;

#[Path('/fox')]
#[Tag('Fun APIs')]
#[OpenApi('
  Náhodné obrázky lišek z randomfox.ca.
  Vrací náhodné fotografie lišek.
')]
final class FoxController extends BaseController
{
    public function __construct(
        private readonly FoxService $foxService
    ) {
    }

    #[Path('/')]
    #[Method('GET')]
    #[RequestParameter(name: 'count', type: 'int', in: 'query', required: false, description: 'Počet obrázků (1-10, výchozí 1)')]
    #[OpenApi('
      Získá náhodný obrázek lišky.

      Příklady:
      - /fox/ - jeden obrázek lišky
      - /fox/?count=5 - pět obrázků lišek
    ')]
    #[ApiResponse(code: 200, description: 'Náhodný obrázek lišky')]
    #[ApiResponse(code: 400, description: 'Neplatný počet')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function getRandomFox(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $countStr = $request->getParameter('count');
            $count = $countStr !== null ? (int) $countStr : 1;

            if ($count === 1) {
                $data = $this->foxService->getRandomFox();
            } else {
                $data = $this->foxService->getMultipleFoxes($count);
            }

            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
