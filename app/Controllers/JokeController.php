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
use App\Services\JokeService;

#[Path('/joke')]
#[Tag('Jokes & Fun')]
#[OpenApi('
  Náhodné vtipy z JokeAPI.
  Kategorie: Programming, Misc, Dark, Pun, Spooky, Christmas.
  Data jsou cachována 1 hodinu.
')]
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
    #[OpenApi('
      Získá náhodný vtip z vybrané kategorie.

      Příklady:
      - /joke/ - náhodný bezpečný vtip
      - /joke/?category=Programming - vtip o programování
      - /joke/?category=Pun&safe=false - slovní hříčka (včetně nesafé)
    ')]
    #[ApiResponse(code: 200, description: 'Náhodný vtip')]
    #[ApiResponse(code: 400, description: 'Neplatná kategorie')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
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
    #[OpenApi('
      Získá náhodný vtip o programování.

      Krátká cesta pro /joke/?category=Programming&safe=true
    ')]
    #[ApiResponse(code: 200, description: 'Vtip o programování')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
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
