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
use App\Services\NewsRssService;

#[Path('/news')]
#[Tag('České zprávy (RSS)')]
#[OpenApi('
  API pro získávání aktuálních českých zpráv z RSS feedů.

  **Dostupné zdroje:**
  - `ct24` - ČT24 (Česká televize)
  - `novinky` - Novinky.cz (Seznam)
  - `aktualne` - Aktuálně.cz
  - `blesk` - Blesk.cz

  **Cache:** 15 minut

  **IRC bot příklady:**
  ```
  !news latest ct24          - Posledních 10 zpráv z ČT24
  !news latest novinky 5     - Posledních 5 zpráv z Novinek
  !news sources              - Seznam všech zdrojů
  !news search Babiš         - Vyhledání zpráv o Babišovi
  !news search volby ct24    - Vyhledání zpráv o volbách na ČT24
  ```
')]
final class NewsRssController extends BaseController
{
    public function __construct(
        private readonly NewsRssService $newsRssService
    ) {
    }

    #[Path('/sources')]
    #[Method('GET')]
    #[OpenApi('
      Získá seznam všech dostupných RSS zdrojů.

      Vrací informace o všech podporovaných českých zpravodajských serverech
      včetně jejich názvů a popisů.

      **Příklad odpovědi:**
      ```json
      {
        "sources": {
          "ct24": {
            "name": "ČT24",
            "description": "Zpravodajství České televize"
          },
          "novinky": {
            "name": "Novinky.cz",
            "description": "Zpravodajský portál Novinky.cz (Seznam)"
          }
        }
      }
      ```

      **IRC bot použití:**
      ```
      !news sources
      ```
    ')]
    #[ApiResponse(code: 200, description: 'Seznam dostupných RSS zdrojů')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
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
    #[OpenApi('
      Získá nejnovější zprávy z vybraného zdroje.

      **Parametry:**
      - `source` (povinný) - Identifikátor zdroje: `ct24`, `novinky`, `aktualne`, `blesk`
      - `limit` (nepovinný) - Počet zpráv k vrácení (1-50, výchozí: 10)

      **Příklad odpovědi:**
      ```json
      {
        "source": "ČT24",
        "count": 10,
        "articles": [
          {
            "title": "Nadpis zprávy",
            "link": "https://ct24.ceskatelevize.cz/clanek/...",
            "description": "Krátký popis zprávy...",
            "pubDate": "Fri, 14 Nov 2025 10:00:00 GMT",
            "author": "Autor zprávy"
          }
        ]
      }
      ```

      **IRC bot použití:**
      ```
      !news latest ct24           - 10 nejnovějších zpráv z ČT24
      !news latest novinky 5      - 5 nejnovějších zpráv z Novinek
      !news latest blesk 20       - 20 nejnovějších zpráv z Blesku
      ```

      **Příklady volání:**
      ```
      GET /news/latest?source=ct24
      GET /news/latest?source=novinky&limit=5
      GET /news/latest?source=blesk&limit=20
      ```
    ')]
    #[ApiResponse(code: 200, description: 'Seznam nejnovějších zpráv')]
    #[ApiResponse(code: 400, description: 'Chybné parametry požadavku')]
    #[ApiResponse(code: 404, description: 'Zdroj nenalezen')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru nebo RSS feed nedostupný')]
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
    #[OpenApi('
      Vyhledá zprávy podle klíčového slova v titulcích a popisech.

      Vyhledávání probíhá case-insensitive (nerozlišuje velká/malá písmena)
      v titulcích i popisech článků. Výsledky jsou seřazeny podle data publikace
      (nejnovější první).

      **Parametry:**
      - `query` (povinný) - Vyhledávací dotaz (klíčové slovo nebo fráze)
      - `source` (nepovinný) - Zdroj: `all` (všechny), `ct24`, `novinky`, `aktualne`, `blesk` (výchozí: `all`)

      **Příklad odpovědi:**
      ```json
      {
        "query": "Babiš",
        "source": "Všechny zdroje",
        "count": 5,
        "articles": [
          {
            "title": "Babiš oznámil řešení střetu zájmů",
            "link": "https://ct24.ceskatelevize.cz/clanek/...",
            "description": "Předseda ANO Andrej Babiš...",
            "pubDate": "Thu, 13 Nov 2025 19:42:25 GMT",
            "source": "ČT24"
          }
        ]
      }
      ```

      **IRC bot použití:**
      ```
      !news search Babiš              - Hledá zprávy o Babišovi ve všech zdrojích
      !news search volby ct24         - Hledá zprávy o volbách na ČT24
      !news search "Donald Trump"     - Hledá zprávy o Donaldu Trumpovi
      !news search korupce novinky    - Hledá zprávy o korupci na Novinkách
      ```

      **Příklady volání:**
      ```
      GET /news/search?query=Babiš
      GET /news/search?query=volby&source=ct24
      GET /news/search?query=korupce&source=all
      ```

      **Poznámka:**
      Vyhledávání běží přes RSS feedy, které obsahují omezený počet nejnovějších
      článků. Pro rozsáhlejší vyhledávání doporučujeme použít přímo weby
      zpravodajských serverů.
    ')]
    #[ApiResponse(code: 200, description: 'Výsledky vyhledávání')]
    #[ApiResponse(code: 400, description: 'Chybné parametry požadavku')]
    #[ApiResponse(code: 404, description: 'Zdroj nenalezen')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru nebo RSS feed nedostupný')]
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
