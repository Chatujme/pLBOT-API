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
use App\Services\FoxService;

#[Path('/fox')]
#[Tag('Fun APIs')]
final class FoxController extends BaseController
{
    public function __construct(
        private readonly FoxService $foxService
    ) {
    }

    #[Path('/')]
    #[Method('GET')]
    #[RequestParameter(name: 'count', type: 'int', in: 'query', required: false, description: 'Počet obrázků (1-10, výchozí 1)')]
        #[ApiResponse(code: '200', description: 'Náhodný obrázek lišky')]
    #[ApiResponse(code: '400', description: 'Neplatný počet')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
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
