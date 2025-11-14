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
use App\Services\HoroskopyService;

#[Path('/horoskop')]
#[Tag('Horoskopy')]
final class HoroskopyController extends BaseController
{
    public function __construct(
        private readonly HoroskopyService $horoskopyService
    ) {
    }

    #[Path('/{znameni}')]
    #[Method('GET')]
    #[RequestParameter(name: 'znameni', type: 'string', in: 'path', required: true, description: 'Znamení zvěrokruhu (beran, byk, blíženci, rak, lev, panna, váhy, štír, střelec, kozoroh, vodnář, ryby)')]
        #[ApiResponse(code: '200', description: 'Horoskop pro dané znamení')]
    #[ApiResponse(code: '400', description: 'Neplatné znamení')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function getHoroskop(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $znameni = $request->getParameter('znameni');

            if (empty($znameni)) {
                return $this->createErrorResponse($response, 'Není zadáno znamení', 400);
            }

            $data = $this->horoskopyService->getHoroskop($znameni);
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
