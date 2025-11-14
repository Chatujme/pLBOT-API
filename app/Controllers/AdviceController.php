<?php

declare(strict_types=1);

namespace App\Controllers;

use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Tag;
use Apitte\Core\Annotation\Controller\OpenApi;
use Apitte\Core\Annotation\Controller\Response as ApiResponse;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse as HttpApiResponse;
use App\Services\AdviceService;

#[Path('/advice')]
#[Tag('Fun APIs')]
final class AdviceController extends BaseController
{
    public function __construct(
        private readonly AdviceService $adviceService
    ) {
    }

    #[Path('/')]
    #[Method('GET')]
        #[ApiResponse(code: '200', description: 'Náhodná rada')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
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
