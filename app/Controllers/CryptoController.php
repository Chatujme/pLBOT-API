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
use App\Services\CryptoService;

#[Path('/crypto')]
#[Tag('Cryptocurrency')]
final class CryptoController extends BaseController
{
    public function __construct(
        private readonly CryptoService $cryptoService
    ) {
    }

    #[Path('/price/{coin}')]
    #[Method('GET')]
    #[RequestParameter(name: 'coin', type: 'string', in: 'path', required: true, description: 'ID kryptoměny (bitcoin, ethereum, cardano, atd.)')]
    #[RequestParameter(name: 'currency', type: 'string', in: 'query', required: false, description: 'Měna (usd, eur, czk, gbp, btc) - výchozí USD')]
        #[ApiResponse(code: '200', description: 'Aktuální cena kryptoměny')]
    #[ApiResponse(code: '400', description: 'Neplatná měna')]
    #[ApiResponse(code: '404', description: 'Kryptoměna nenalezena')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function getCoinPrice(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $coin = $request->getParameter('coin');
            $currency = $request->getParameter('currency') ?? 'usd';

            $data = $this->cryptoService->getCoinPrice($coin, $currency);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            $errorCode = str_contains($e->getMessage(), 'nenalezena') ? 404 : 400;
            return $this->createErrorResponse($response, $e->getMessage(), $errorCode);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/popular')]
    #[Method('GET')]
    #[RequestParameter(name: 'currency', type: 'string', in: 'query', required: false, description: 'Měna (usd, eur, czk, gbp) - výchozí USD')]
        #[ApiResponse(code: '200', description: 'Ceny populárních kryptoměn')]
    #[ApiResponse(code: '400', description: 'Neplatná měna')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function getPopularCoins(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $currency = $request->getParameter('currency') ?? 'usd';

            $data = $this->cryptoService->getPopularCoins($currency);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
