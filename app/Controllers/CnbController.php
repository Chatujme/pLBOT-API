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
use App\Services\CnbKurzyService;

#[Path('/cnb')]
#[Tag('ČNB Kurzy')]
final class CnbController extends BaseController
{
    public function __construct(
        private readonly CnbKurzyService $cnbService
    ) {
    }

    #[Path('/kurzy')]
    #[Method('GET')]
        #[ApiResponse(code: '200', description: 'Seznam všech kurzů')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function getAllKurzy(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $this->cnbService->getAllKurzy();
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/kurzy/{mena}')]
    #[Method('GET')]
    #[RequestParameter(name: 'mena', type: 'string', in: 'path', required: true, description: 'Kód měny (USD, EUR, GBP, atd.)')]
        #[ApiResponse(code: '200', description: 'Kurz zadané měny')]
    #[ApiResponse(code: '404', description: 'Měna nenalezena')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function getKurz(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $mena = $request->getParameter('mena');
            $data = $this->cnbService->getKurz($mena);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/prevod')]
    #[Method('GET')]
    #[RequestParameter(name: 'amount', type: 'float', in: 'query', required: true, description: 'Částka k převodu')]
    #[RequestParameter(name: 'from', type: 'string', in: 'query', required: true, description: 'Zdrojová měna (kód)')]
    #[RequestParameter(name: 'to', type: 'string', in: 'query', required: true, description: 'Cílová měna (kód)')]
        #[ApiResponse(code: '200', description: 'Výsledek převodu měny')]
    #[ApiResponse(code: '400', description: 'Chybí povinné parametry')]
    #[ApiResponse(code: '404', description: 'Měna nenalezena')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function convertCurrency(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $amountStr = $request->getParameter('amount');
            $from = $request->getParameter('from');
            $to = $request->getParameter('to');

            if (empty($amountStr) || empty($from) || empty($to)) {
                return $this->createErrorResponse($response, 'Chybí povinné parametry: amount, from, to', 400);
            }

            $amount = (float) $amountStr;

            if ($amount <= 0) {
                return $this->createErrorResponse($response, 'Částka musí být větší než 0', 400);
            }

            $data = $this->cnbService->convertCurrency($amount, $from, $to);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
