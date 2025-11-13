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
use App\Services\MistnostService;

#[Path('/mistnost')]
#[Tag('Místnost')]
#[OpenApi('
  Informace o místnostech z Chatujme.cz.
  Data jsou cachována 5 minut.
')]
final class MistnostController extends BaseController
{
    public function __construct(
        private readonly MistnostService $mistnostService
    ) {
    }

    #[Path('/{id}')]
    #[Method('GET')]
    #[RequestParameter(name: 'id', type: 'string', in: 'path', required: true, description: 'ID místnosti z Chatujme.cz')]
    #[OpenApi('
      Získá detailní informace o místnosti z Chatujme.cz
      včetně stálých správců, času, limitů a dalších údajů.
    ')]
    #[ApiResponse(code: 200, description: 'Informace o místnosti')]
    #[ApiResponse(code: 400, description: 'Chybí ID místnosti')]
    #[ApiResponse(code: 404, description: 'Místnost nenalezena')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function getMistnost(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $id = $request->getParameter('id');

            if (empty($id)) {
                return $this->createErrorResponse($response, 'Není zadáno ID místnosti', 400);
            }

            $data = $this->mistnostService->getMistnost($id);

            if (isset($data['data']['code']) && $data['data']['code'] === 404) {
                return $this->createErrorResponse($response, $data['data']['message'], 404);
            }

            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
