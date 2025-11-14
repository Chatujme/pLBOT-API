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
use App\Services\MistnostService;

#[Path('/mistnost')]
#[Tag('Místnost')]
final class MistnostController extends BaseController
{
    public function __construct(
        private readonly MistnostService $mistnostService
    ) {
    }

    #[Path('/{id}')]
    #[Method('GET')]
    #[RequestParameter(name: 'id', type: 'string', in: 'path', required: true, description: 'ID místnosti z Chatujme.cz')]
        #[ApiResponse(code: '200', description: 'Informace o místnosti')]
    #[ApiResponse(code: '400', description: 'Chybí ID místnosti')]
    #[ApiResponse(code: '404', description: 'Místnost nenalezena')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
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
