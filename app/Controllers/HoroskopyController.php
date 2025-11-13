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
use App\Services\HoroskopyService;

#[Path('/horoskop')]
#[Tag('Horoskopy')]
#[OpenApi('
  Denní horoskopy z Horoskopy.cz.
  Podporuje všechna znamení zvěrokruhu včetně diakritiky.
  Data jsou cachována 1 den.
')]
final class HoroskopyController extends BaseController
{
    public function __construct(
        private readonly HoroskopyService $horoskopyService
    ) {
    }

    #[Path('/{znameni}')]
    #[Method('GET')]
    #[RequestParameter(name: 'znameni', type: 'string', in: 'path', required: true, description: 'Znamení zvěrokruhu (beran, byk, blíženci, rak, lev, panna, váhy, štír, střelec, kozoroh, vodnář, ryby)')]
    #[OpenApi('
      Získá horoskop pro zadané znamení zvěrokruhu.
      Podporuje české názvy s diakritikou i bez.
    ')]
    #[ApiResponse(code: 200, description: 'Horoskop pro dané znamení')]
    #[ApiResponse(code: 400, description: 'Neplatné znamení')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
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
