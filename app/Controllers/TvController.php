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
use App\Services\TvProgramService;

#[Path('/tv')]
#[Tag('TV Program')]
final class TvController extends BaseController
{
    public function __construct(
        private readonly TvProgramService $tvProgramService
    ) {
    }

    #[Path('/')]
    #[Method('GET')]
        #[ApiResponse(code: '200', description: 'Seznam TV stanic')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
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
        #[ApiResponse(code: '200', description: 'Aktuální programy všech stanic')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
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
        #[ApiResponse(code: '200', description: 'Aktuální program stanice')]
    #[ApiResponse(code: '404', description: 'Stanice nenalezena')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
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
