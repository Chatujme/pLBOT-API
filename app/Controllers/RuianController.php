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
use App\Services\RuianService;

#[Path('/ruian')]
#[Tag('RUIAN - Registry adres')]
final class RuianController extends BaseController
{
    public function __construct(
        private readonly RuianService $ruianService
    ) {
    }

    #[Path('/obce')]
    #[Method('GET')]
    #[RequestParameter(name: 'nazev', type: 'string', in: 'query', required: true, description: 'Název obce nebo jeho část (min. 2 znaky)')]
    #[RequestParameter(name: 'limit', type: 'int', in: 'query', required: false, description: 'Maximální počet výsledků (výchozí 10)')]
        #[ApiResponse(code: '200', description: 'Seznam nalezených obcí')]
    #[ApiResponse(code: '400', description: 'Chybí nebo je neplatný název')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function vyhledatObce(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
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

            $data = $this->ruianService->vyhledatObce($nazev, $limit);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/ulice')]
    #[Method('GET')]
    #[RequestParameter(name: 'nazev', type: 'string', in: 'query', required: true, description: 'Název ulice (min. 2 znaky)')]
    #[RequestParameter(name: 'obec', type: 'string', in: 'query', required: false, description: 'Název obce pro upřesnění hledání')]
    #[RequestParameter(name: 'limit', type: 'int', in: 'query', required: false, description: 'Maximální počet výsledků (výchozí 10)')]
        #[ApiResponse(code: '200', description: 'Seznam nalezených ulic')]
    #[ApiResponse(code: '400', description: 'Chybí nebo je neplatný název')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function vyhledatUlice(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $nazev = $request->getParameter('nazev');
            $obec = $request->getParameter('obec');
            $limitStr = $request->getParameter('limit');

            if (empty($nazev)) {
                return $this->createErrorResponse($response, 'Název je povinný parameter', 400);
            }

            $limit = $limitStr !== null ? (int) $limitStr : 10;

            if ($limit < 1 || $limit > 100) {
                return $this->createErrorResponse($response, 'Limit musí být mezi 1 a 100', 400);
            }

            $data = $this->ruianService->vyhledatUlice($nazev, $obec, $limit);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/adresy')]
    #[Method('GET')]
    #[RequestParameter(name: 'query', type: 'string', in: 'query', required: true, description: 'Hledaný výraz (min. 3 znaky)')]
    #[RequestParameter(name: 'limit', type: 'int', in: 'query', required: false, description: 'Maximální počet výsledků (výchozí 10)')]
        #[ApiResponse(code: '200', description: 'Seznam nalezených adres')]
    #[ApiResponse(code: '400', description: 'Chybí nebo je neplatný hledaný výraz')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function vyhledatAdresy(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $query = $request->getParameter('query');
            $limitStr = $request->getParameter('limit');

            if (empty($query)) {
                return $this->createErrorResponse($response, 'Hledaný výraz je povinný parameter', 400);
            }

            $limit = $limitStr !== null ? (int) $limitStr : 10;

            if ($limit < 1 || $limit > 100) {
                return $this->createErrorResponse($response, 'Limit musí být mezi 1 a 100', 400);
            }

            $data = $this->ruianService->vyhledatAdresy($query, $limit);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/validate')]
    #[Method('GET')]
    #[RequestParameter(name: 'ulice', type: 'string', in: 'query', required: true, description: 'Název ulice')]
    #[RequestParameter(name: 'cislo', type: 'string', in: 'query', required: true, description: 'Číslo popisné')]
    #[RequestParameter(name: 'obec', type: 'string', in: 'query', required: true, description: 'Název obce')]
    #[RequestParameter(name: 'psc', type: 'string', in: 'query', required: false, description: 'PSČ (volitelné)')]
        #[ApiResponse(code: '200', description: 'Výsledek validace adresy')]
    #[ApiResponse(code: '400', description: 'Chybí povinné parametry')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function validateAdresa(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $ulice = $request->getParameter('ulice');
            $cislo = $request->getParameter('cislo');
            $obec = $request->getParameter('obec');
            $psc = $request->getParameter('psc');

            if (empty($ulice) || empty($cislo) || empty($obec)) {
                return $this->createErrorResponse($response, 'Parametry ulice, cislo a obec jsou povinné', 400);
            }

            $data = $this->ruianService->validateAdresa($ulice, $cislo, $obec, $psc);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
