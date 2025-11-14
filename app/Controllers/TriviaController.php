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
use App\Services\TriviaService;

#[Path('/trivia')]
#[Tag('Fun APIs')]
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
        #[ApiResponse(code: '200', description: 'Trivia otázky')]
    #[ApiResponse(code: '400', description: 'Neplatné parametry')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
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
        #[ApiResponse(code: '200', description: 'Seznam kategorií')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
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
