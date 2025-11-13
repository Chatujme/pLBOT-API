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
use App\Services\TriviaService;

#[Path('/trivia')]
#[Tag('Fun APIs')]
#[OpenApi('
  Trivia otázky z Open Trivia Database.
  Vrací otázky z různých kategorií a obtížností.
  Data jsou cachována 1 hodinu.
')]
final class TriviaController extends BaseController
{
    public function __construct(
        private readonly TriviaService $triviaService
    ) {
    }

    #[Path('/')]
    #[Method('GET')]
    #[RequestParameter(name: 'amount', type: 'int', in: 'query', required: false, description: 'Počet otázek (1-50, výchozí 10)')]
    #[RequestParameter(name: 'category', type: 'int', in: 'query', required: false, description: 'ID kategorie (viz /trivia/categories)')]
    #[RequestParameter(name: 'difficulty', type: 'string', in: 'query', required: false, description: 'Obtížnost (easy, medium, hard)')]
    #[RequestParameter(name: 'type', type: 'string', in: 'query', required: false, description: 'Typ otázky (multiple, boolean)')]
    #[OpenApi('
      Získá trivia otázky podle zadaných parametrů.

      Příklady:
      - /trivia/ - 10 náhodných otázek
      - /trivia/?amount=5&difficulty=easy - 5 lehkých otázek
      - /trivia/?category=9&type=multiple - multiple choice otázky z kategorie General Knowledge
      - /trivia/?amount=20&difficulty=hard - 20 těžkých otázek
    ')]
    #[ApiResponse(code: 200, description: 'Trivia otázky')]
    #[ApiResponse(code: 400, description: 'Neplatné parametry')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function getQuestions(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $amountStr = $request->getParameter('amount');
            $categoryStr = $request->getParameter('category');
            $difficulty = $request->getParameter('difficulty');
            $type = $request->getParameter('type');

            $amount = $amountStr !== null ? (int) $amountStr : 10;
            $category = $categoryStr !== null ? (int) $categoryStr : null;

            $data = $this->triviaService->getQuestions($amount, $category, $difficulty, $type);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/categories')]
    #[Method('GET')]
    #[OpenApi('
      Získá seznam všech dostupných kategorií trivia otázek.

      Vrací ID a názvy kategorií které lze použít pro filtrování otázek.
    ')]
    #[ApiResponse(code: 200, description: 'Seznam kategorií')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function getCategories(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $this->triviaService->getCategories();
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
