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
use App\Services\AresService;

#[Path('/ares')]
#[Tag('ARES Registr firem')]
#[OpenApi('
  Administrativní Registr Ekonomických Subjektů (ARES).
  Vyhledávání informací o firmách registrovaných v České republice.
  Data jsou cachována 1 měsíc (mění se velmi zřídka).
')]
final class AresController extends BaseController
{
    public function __construct(
        private readonly AresService $aresService
    ) {
    }

    #[Path('/ico/{ico}')]
    #[Method('GET')]
    #[RequestParameter(name: 'ico', type: 'string', in: 'path', required: true, description: 'IČO firmy (8 číslic)')]
    #[OpenApi('
      Získá detailní informace o firmě podle IČO (identifikační číslo organizace).

      IČO může být zadáno s nebo bez nul na začátku.

      Příklady:
      - /ares/ico/27082440 - Slevomatu
      - /ares/ico/45274649 - Seznam.cz
      - /ares/ico/00000205 - Škoda Auto
    ')]
    #[ApiResponse(code: 200, description: 'Informace o firmě')]
    #[ApiResponse(code: 400, description: 'Neplatné IČO')]
    #[ApiResponse(code: 404, description: 'Firma nenalezena')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
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
    #[OpenApi('
      Vyhledá firmy podle názvu nebo jeho části.

      Hledání je case-insensitive a funguje i pro částečné názvy.

      Příklady:
      - /ares/vyhledat?nazev=seznam - najde Seznam.cz a.s.
      - /ares/vyhledat?nazev=škoda&limit=5 - najde Škoda Auto a další firmy se slovem "škoda"
      - /ares/vyhledat?nazev=google - najde Google Czech Republic s.r.o.
    ')]
    #[ApiResponse(code: 200, description: 'Seznam nalezených firem')]
    #[ApiResponse(code: 400, description: 'Chybí nebo je neplatný název')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
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
