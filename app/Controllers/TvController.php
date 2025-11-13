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
use App\Services\TvProgramService;

#[Path('/tv')]
#[Tag('TV Program')]
#[OpenApi('
  TV program z XMLTV zdroje.
  Aktuální program českých TV stanic.
  Data jsou cachována 1 hodinu.
')]
final class TvController extends BaseController
{
    public function __construct(
        private readonly TvProgramService $tvProgramService
    ) {
    }

    #[Path('/')]
    #[Method('GET')]
    #[OpenApi('
      Získá seznam dostupných TV stanic
    ')]
    #[ApiResponse(code: 200, description: 'Seznam TV stanic')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function index(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $stanice = $this->tvProgramService->getStanice();
            return $this->createSuccessResponse($response, ['data' => $stanice]);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/vse')]
    #[Method('GET')]
    #[OpenApi('
      Získá aktuální program pro všechny TV stanice
    ')]
    #[ApiResponse(code: 200, description: 'Aktuální programy všech stanic')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function getAll(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $this->tvProgramService->getAllCurrentPrograms();
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/{stanice}')]
    #[Method('GET')]
    #[RequestParameter(name: 'stanice', type: 'string', in: 'path', required: true, description: 'Název TV stanice (ct1, ct2, nova, prima, atd.)')]
    #[OpenApi('
      Získá aktuální program pro konkrétní TV stanici
    ')]
    #[ApiResponse(code: 200, description: 'Aktuální program stanice')]
    #[ApiResponse(code: 404, description: 'Stanice nenalezena')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function getChannel(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $stanice = $request->getParameter('stanice');
            $data = $this->tvProgramService->getCurrentProgram($stanice);

            if (isset($data['message'])) {
                return $this->createErrorResponse($response, $data['message'], 404);
            }

            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
