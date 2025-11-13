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
use App\Services\CnbKurzyService;

#[Path('/cnb')]
#[Tag('ČNB Kurzy')]
#[OpenApi('
  Oficiální kurzy měn České národní banky.
  Data jsou aktualizována 1x denně po 14:30 (pracovní dny).
  Všechny kurzy jsou vůči české koruně (CZK).
')]
final class CnbController extends BaseController
{
    public function __construct(
        private readonly CnbKurzyService $cnbService
    ) {
    }

    #[Path('/kurzy')]
    #[Method('GET')]
    #[OpenApi('
      Získá kurzy všech měn z denního kurzovního lístku ČNB.
      Vrací kurzy vůči CZK pro všechny podporované měny.
    ')]
    #[ApiResponse(code: 200, description: 'Seznam všech kurzů')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
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
    #[OpenApi('
      Získá kurz konkrétní měny.

      Příklady kódů měn:
      - USD - americký dolar
      - EUR - euro
      - GBP - britská libra
      - PLN - polský zlotý
      - CHF - švýcarský frank
    ')]
    #[ApiResponse(code: 200, description: 'Kurz zadané měny')]
    #[ApiResponse(code: 404, description: 'Měna nenalezena')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
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
    #[OpenApi('
      Převede částku z jedné měny do druhé podle aktuálního kurzu ČNB.

      Příklady použití:
      - /cnb/prevod?amount=100&from=USD&to=CZK
      - /cnb/prevod?amount=1000&from=CZK&to=EUR
      - /cnb/prevod?amount=50&from=EUR&to=USD
    ')]
    #[ApiResponse(code: 200, description: 'Výsledek převodu měny')]
    #[ApiResponse(code: 400, description: 'Chybí povinné parametry')]
    #[ApiResponse(code: 404, description: 'Měna nenalezena')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
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
