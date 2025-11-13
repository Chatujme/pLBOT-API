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
use App\Services\QuotesService;

#[Path('/quotes')]
#[Tag('Fun APIs')]
#[OpenApi('
  Inspirativní citáty z Quotable API.
  Vrací náhodné citáty slavných osobností.
')]
final class QuotesController extends BaseController
{
    public function __construct(
        private readonly QuotesService $quotesService
    ) {
    }

    #[Path('/')]
    #[Method('GET')]
    #[RequestParameter(name: 'tag', type: 'string', in: 'query', required: false, description: 'Téma citátu (wisdom, inspirational, success, life, happiness, motivational)')]
    #[OpenApi('
      Získá náhodný inspirativní citát.

      Příklady:
      - /quotes/ - náhodný citát
      - /quotes/?tag=wisdom - citát o moudrosti
      - /quotes/?tag=success - citát o úspěchu
    ')]
    #[ApiResponse(code: 200, description: 'Náhodný citát')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function getRandomQuote(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $tag = $request->getParameter('tag');
            $data = $this->quotesService->getRandomQuote($tag);
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/multiple')]
    #[Method('GET')]
    #[RequestParameter(name: 'limit', type: 'int', in: 'query', required: false, description: 'Počet citátů (1-50, výchozí 5)')]
    #[OpenApi('
      Získá více náhodných citátů najednou.

      Příklady:
      - /quotes/multiple - 5 citátů
      - /quotes/multiple?limit=10 - 10 citátů
    ')]
    #[ApiResponse(code: 200, description: 'Seznam citátů')]
    #[ApiResponse(code: 400, description: 'Neplatný limit')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function getMultipleQuotes(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $limitStr = $request->getParameter('limit');
            $limit = $limitStr !== null ? (int) $limitStr : 5;

            $data = $this->quotesService->getMultipleQuotes($limit);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
