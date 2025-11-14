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
use App\Services\JokeService;

#[Path('/joke')]
#[Tag('Jokes & Fun')]
final class JokeController extends BaseController
{
    public function __construct(
        private readonly JokeService $jokeService
    ) {
    }

    #[Path('/')]
    #[Method('GET')]
    #[RequestParameter(name: 'category', type: 'string', in: 'query', required: false, description: 'Kategorie vtipu (Programming, Misc, Dark, Pun, Any)')]
    #[RequestParameter(name: 'safe', type: 'bool', in: 'query', required: false, description: 'Pouze bezpečné vtipy (true/false, výchozí true)')]
        #[ApiResponse(code: '200', description: 'Náhodný vtip')]
    #[ApiResponse(code: '400', description: 'Neplatná kategorie')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function getRandomJoke(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $category = $request->getParameter('category');
            $safeParam = $request->getParameter('safe');

            $safe = $safeParam === null || $safeParam === 'true' || $safeParam === '1';

            $data = $this->jokeService->getRandomJoke($category, $safe);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }

    #[Path('/programming')]
    #[Method('GET')]
        #[ApiResponse(code: '200', description: 'Vtip o programování')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function getProgrammingJoke(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $this->jokeService->getRandomJoke('Programming', true);
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
