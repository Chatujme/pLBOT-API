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
use App\Services\ZasilkovnaService;

#[Path('/zasilkovna')]
#[Tag('Zásilkovna')]
final class ZasilkovnaController extends BaseController
{
    public function __construct(
        private readonly ZasilkovnaService $zasilkovnaService
    ) {
    }

    #[Path('/track/{packageId}')]
    #[Method('GET')]
    #[RequestParameter(name: 'packageId', type: 'string', in: 'path', required: true, description: 'ID balíku (například Z123456789)')]
        #[ApiResponse(code: '200', description: 'Informace o balíku')]
    #[ApiResponse(code: '404', description: 'Balík nebyl nalezen')]
    #[ApiResponse(code: '500', description: 'Interní chyba serveru')]
    public function trackPackage(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $packageId = $request->getParameter('packageId');

            if (empty($packageId)) {
                return $this->createErrorResponse($response, 'ID balíku je povinný parameter', 400);
            }

            $data = $this->zasilkovnaService->trackPackage($packageId);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'nebyl nalezen')) {
                return $this->createErrorResponse($response, $e->getMessage(), 404);
            }
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
