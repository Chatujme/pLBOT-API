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
use App\Services\PocasiService;

#[Path('/pocasi')]
#[Tag('Počasí')]
#[OpenApi('
  Předpověď počasí z Centrum.cz API.
  Podporuje různá města v ČR.
  Data jsou cachována 1 den.
')]
final class PocasiController extends BaseController
{
    public function __construct(
        private readonly PocasiService $pocasiService
    ) {
    }

    #[Path('/')]
    #[Method('GET')]
    #[RequestParameter(name: 'mesto', type: 'string', in: 'query', required: false, description: 'Město pro předpověď (default: praha)')]
    #[OpenApi('
      Získá předpověď počasí pro všechny dny (dnes, zítra, pozítří)
    ')]
    #[ApiResponse(code: 200, description: 'Předpověď počasí')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function index(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $mesto = $request->getParameter('mesto');
            $data = $this->pocasiService->getPocasi(null, $mesto);
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/{den}')]
    #[Method('GET')]
    #[RequestParameter(name: 'den', type: 'string', in: 'path', required: true, description: 'Den (dnes, zitra, pozitri)')]
    #[RequestParameter(name: 'mesto', type: 'string', in: 'query', required: false, description: 'Město pro předpověď (default: praha)')]
    #[OpenApi('
      Získá předpověď počasí pro konkrétní den
    ')]
    #[ApiResponse(code: 200, description: 'Předpověď pro daný den')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function getDay(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $den = $request->getParameter('den');
            $mesto = $request->getParameter('mesto');
            $data = $this->pocasiService->getPocasi($den, $mesto);
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
