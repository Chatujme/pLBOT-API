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
use App\Services\NewsRssService;

#[Path('/news')]
#[Tag('České zprávy (RSS)')]
final class NewsRssController extends BaseController
{
    public function __construct(
        private readonly NewsRssService $newsRssService
    ) {
    }

    #[Path('/sources')]
    #[Method('GET')]
        #[ApiResponse(code: '200', description: 'Seznam dostupných RSS zdrojů')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function getSources(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $this->newsRssService->getAllSources();
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/latest')]
    #[Method('GET')]
    #[RequestParameter(name: 'source', type: 'string', in: 'query', required: true, description: 'Zdroj zpráv (ct24, novinky, aktualne, blesk)')]
    #[RequestParameter(name: 'limit', type: 'int', in: 'query', required: false, description: 'Maximální počet zpráv (1-50, výchozí: 10)')]
        #[ApiResponse(code: '200', description: 'Seznam nejnovějších zpráv')]
    #[ApiResponse(code: '400', description: 'Chybné parametry požadavku')]
    #[ApiResponse(code: '404', description: 'Zdroj nenalezen')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru nebo RSS feed nedostupný')]
    public function getLatestNews(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $source = $request->getParameter('source');
            $limit = $request->getParameter('limit', 10);

            if (empty($source)) {
                return $this->createErrorResponse($response, 'Parametr "source" je povinný', 400);
            }

            // Validace a konverze limitu
            if (!is_numeric($limit)) {
                return $this->createErrorResponse($response, 'Parametr "limit" musí být číslo', 400);
            }

            $limit = (int) $limit;

            $data = $this->newsRssService->getLatestNews($source, $limit);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            // Rozlišujeme mezi chybou uživatele a chybou serveru
            if (str_contains($e->getMessage(), 'Neznámý zdroj')) {
                return $this->createErrorResponse($response, $e->getMessage(), 404);
            }
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/search')]
    #[Method('GET')]
    #[RequestParameter(name: 'query', type: 'string', in: 'query', required: true, description: 'Vyhledávací dotaz')]
    #[RequestParameter(name: 'source', type: 'string', in: 'query', required: false, description: 'Zdroj pro vyhledávání (all, ct24, novinky, aktualne, blesk), výchozí: all')]
        #[ApiResponse(code: '200', description: 'Výsledky vyhledávání')]
    #[ApiResponse(code: '400', description: 'Chybné parametry požadavku')]
    #[ApiResponse(code: '404', description: 'Zdroj nenalezen')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru nebo RSS feed nedostupný')]
    public function searchNews(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $query = $request->getParameter('query');
            $source = $request->getParameter('source', 'all');

            if (empty($query)) {
                return $this->createErrorResponse($response, 'Parametr "query" je povinný', 400);
            }

            $data = $this->newsRssService->searchNews($query, $source);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            // Rozlišujeme mezi chybou uživatele a chybou serveru
            if (str_contains($e->getMessage(), 'Neznámý zdroj')) {
                return $this->createErrorResponse($response, $e->getMessage(), 404);
            }
            if (str_contains($e->getMessage(), 'nesmí být prázdný')) {
                return $this->createErrorResponse($response, $e->getMessage(), 400);
            }
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
