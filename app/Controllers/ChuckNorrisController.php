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
use App\Services\ChuckNorrisService;

#[Path('/chucknorris')]
#[Tag('Jokes & Fun')]
#[OpenApi('
  Chuck Norris vtipy z oficiálního Chuck Norris API.
  Vrací náhodné vtipy o Chucku Norrisovi.
')]
final class ChuckNorrisController extends BaseController
{
    public function __construct(
        private readonly ChuckNorrisService $chuckNorrisService
    ) {
    }

    #[Path('/')]
    #[Method('GET')]
    #[RequestParameter(name: 'category', type: 'string', in: 'query', required: false, description: 'Kategorie vtipu (dev, movie, food, atd.)')]
    #[OpenApi('
      Získá náhodný Chuck Norris vtip.

      Příklady:
      - /chucknorris/ - náhodný vtip
      - /chucknorris/?category=dev - vtip o programování
      - /chucknorris/?category=movie - vtip o filmech
    ')]
    #[ApiResponse(code: 200, description: 'Náhodný Chuck Norris vtip')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
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
    #[OpenApi('
      Získá seznam všech dostupných kategorií vtipů.

      Vrací seznam kategorií které lze použít pro filtrování vtipů.
    ')]
    #[ApiResponse(code: 200, description: 'Seznam kategorií')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
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
