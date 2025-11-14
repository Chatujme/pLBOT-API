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
use App\Services\ChuckNorrisService;

#[Path('/chucknorris')]
#[Tag('Jokes & Fun')]
final class ChuckNorrisController extends BaseController
{
    public function __construct(
        private readonly ChuckNorrisService $chuckNorrisService
    ) {
    }

    #[Path('/')]
    #[Method('GET')]
    #[RequestParameter(name: 'category', type: 'string', in: 'query', required: false, description: 'Kategorie vtipu (dev, movie, food, atd.)')]
        #[ApiResponse(code: '200', description: 'Náhodný Chuck Norris vtip')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function getRandomJoke(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $category = $request->getParameter('category');
            $data = $this->chuckNorrisService->getRandomJoke($category);
            return $this->createSuccessResponse($response, $data);
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
            $data = $this->chuckNorrisService->getCategories();
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
