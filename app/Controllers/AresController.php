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
use App\Services\AresService;

#[Path('/ares')]
#[Tag('ARES Registr firem')]
final class AresController extends BaseController
{
    public function __construct(
        private readonly AresService $aresService
    ) {
    }

    #[Path('/ico/{ico}')]
    #[Method('GET')]
    #[RequestParameter(name: 'ico', type: 'string', in: 'path', required: true, description: 'IČO firmy (8 číslic)')]
        #[ApiResponse(code: '200', description: 'Informace o firmě')]
    #[ApiResponse(code: '400', description: 'Neplatné IČO')]
    #[ApiResponse(code: '404', description: 'Firma nenalezena')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function getFirmaByIco(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $ico = $request->getParameter('ico');

            if (empty($ico)) {
                return $this->createErrorResponse($response, 'IČO je povinný parameter', 400);
            }

            $data = $this->aresService->getFirmaByIco($ico);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'nebyla nalezena')) {
                return $this->createErrorResponse($response, $e->getMessage(), 404);
            }
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/vyhledat')]
    #[Method('GET')]
    #[RequestParameter(name: 'nazev', type: 'string', in: 'query', required: true, description: 'Název firmy nebo jeho část (min. 3 znaky)')]
    #[RequestParameter(name: 'limit', type: 'int', in: 'query', required: false, description: 'Maximální počet výsledků (výchozí 10)')]
        #[ApiResponse(code: '200', description: 'Seznam nalezených firem')]
    #[ApiResponse(code: '400', description: 'Chybí nebo je neplatný název')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function vyhledatFirmy(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $nazev = $request->getParameter('nazev');
            $limitStr = $request->getParameter('limit');

            if (empty($nazev)) {
                return $this->createErrorResponse($response, 'Název je povinný parameter', 400);
            }

            $limit = $limitStr !== null ? (int) $limitStr : 10;

            if ($limit < 1 || $limit > 100) {
                return $this->createErrorResponse($response, 'Limit musí být mezi 1 a 100', 400);
            }

            $data = $this->aresService->vyhledatFirmy($nazev, $limit);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
