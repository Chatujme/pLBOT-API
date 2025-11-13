<?php

declare(strict_types=1);

namespace App\Controllers;

use Apitte\Core\Attribute\Controller\Path;
use Apitte\Core\Attribute\Controller\Method;
use Apitte\Core\Attribute\Controller\Tag;
use Apitte\Core\Attribute\Controller\OpenApi;
use Apitte\Core\Attribute\Controller\Response as ApiResponse;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse as HttpApiResponse;
use App\Services\AdviceService;

#[Path('/advice')]
#[Tag('Fun APIs')]
#[OpenApi('
  Náhodné rady z Advice Slip API.
  Vrací náhodné životní rady v angličtině.
')]
final class AdviceController extends BaseController
{
    public function __construct(
        private readonly AdviceService $adviceService
    ) {
    }

    #[Path('/')]
    #[Method('GET')]
    #[OpenApi('
      Získá náhodnou radu.

      Příklad:
      - /advice/ - náhodná rada
    ')]
    #[ApiResponse(code: 200, description: 'Náhodná rada')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
    public function getRandomAdvice(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $this->adviceService->getRandomAdvice();
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
