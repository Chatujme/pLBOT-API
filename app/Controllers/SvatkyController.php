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
use App\Services\SvatkyService;

#[Path('/svatky')]
#[Tag('Svátky')]
#[OpenApi('
  Získávání informací o českých svátkách.
  Data jsou cachována 1 den.
')]
final class SvatkyController extends BaseController
{
    public function __construct(
        private readonly SvatkyService $svatkyService
    ) {
    }

    #[Path('/')]
    #[Method('GET')]
    #[OpenApi('
      Získá svátky pro všechny dny (předevčírem, včera, dnes, zítra)
    ')]
    #[ApiResponse(code: 200, description: 'Seznam svátků')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function index(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $this->svatkyService->getSvatek();
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/{den}')]
    #[Method('GET')]
    #[RequestParameter(name: 'den', type: 'string', in: 'path', required: true, description: 'Den (predevcirem, vcera, dnes, zitra)')]
    #[OpenApi('
      Získá svátek pro konkrétní den
    ')]
    #[ApiResponse(code: 200, description: 'Svátek pro daný den')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function getDay(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $den = $request->getParameter('den');
            $data = $this->svatkyService->getSvatek($den);
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
