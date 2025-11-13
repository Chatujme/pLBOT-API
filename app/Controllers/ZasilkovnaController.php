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
use App\Services\ZasilkovnaService;

#[Path('/zasilkovna')]
#[Tag('Zásilkovna')]
#[OpenApi('
  Sledování zásilek přes Zásilkovnu (Packeta).
  Získávání informací o stavu balíků a jejich doručení.
  Data jsou cachována 1 hodinu.
')]
final class ZasilkovnaController extends BaseController
{
    public function __construct(
        private readonly ZasilkovnaService $zasilkovnaService
    ) {
    }

    #[Path('/track/{packageId}')]
    #[Method('GET')]
    #[RequestParameter(name: 'packageId', type: 'string', in: 'path', required: true, description: 'ID balíku (například Z123456789)')]
    #[OpenApi('
      Sleduje balík podle ID a vrátí aktuální stav.

      Vrací informace o stavu balíku, historii stavů, výdejním místě a dalších detailech.

      Příklady:
      - /zasilkovna/track/Z123456789
      - /zasilkovna/track/P987654321

      Poznámka: API vyžaduje platné ID balíku. Pro neexistující balíky vrátí chybu 404.
    ')]
    #[ApiResponse(code: 200, description: 'Informace o balíku')]
    #[ApiResponse(code: 404, description: 'Balík nebyl nalezen')]
    #[ApiResponse(code: 500, description: 'Interní chyba serveru')]
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
