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
use App\Services\VatService;

#[Path('/vat')]
#[Tag('EU VAT Verification')]
final class VatController extends BaseController
{
    public function __construct(
        private readonly VatService $vatService
    ) {
    }

    #[Path('/validate/{countryCode}/{vatNumber}')]
    #[Method('GET')]
    #[RequestParameter(name: 'countryCode', type: 'string', in: 'path', required: true, description: 'Kód země EU (CZ, DE, SK, ...)')]
    #[RequestParameter(name: 'vatNumber', type: 'string', in: 'path', required: true, description: 'VAT číslo bez kódu země')]
        #[ApiResponse(code: '200', description: 'VAT ověřeno (může být platné i neplatné)')]
    #[ApiResponse(code: '400', description: 'Neplatný vstup')]
    #[ApiResponse(code: '500', description: 'Chyba VIES služby')]
    public function validateVat(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $countryCode = $request->getParameter('countryCode');
            $vatNumber = $request->getParameter('vatNumber');

            if (empty($countryCode) || empty($vatNumber)) {
                return $this->createErrorResponse($response, 'Kód země a VAT číslo jsou povinné', 400);
            }

            $data = $this->vatService->validateVat($countryCode, $vatNumber);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při ověřování VAT: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/check/{fullVat}')]
    #[Method('GET')]
    #[RequestParameter(name: 'fullVat', type: 'string', in: 'path', required: true, description: 'Kompletní VAT včetně kódu země (např. CZ12345678)')]
        #[ApiResponse(code: '200', description: 'VAT ověřeno')]
    #[ApiResponse(code: '400', description: 'Neplatný vstup')]
    #[ApiResponse(code: '500', description: 'Chyba VIES služby')]
    public function checkFullVat(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $fullVat = $request->getParameter('fullVat');

            if (empty($fullVat)) {
                return $this->createErrorResponse($response, 'VAT číslo je povinné', 400);
            }

            $data = $this->vatService->validateFullVat($fullVat);
            return $this->createSuccessResponse($response, $data);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při ověřování VAT: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/format/{countryCode}/{vatNumber}')]
    #[Method('GET')]
    #[RequestParameter(name: 'countryCode', type: 'string', in: 'path', required: true, description: 'Kód země EU')]
    #[RequestParameter(name: 'vatNumber', type: 'string', in: 'path', required: true, description: 'VAT číslo')]
        #[ApiResponse(code: '200', description: 'Validace formátu provedena')]
    #[ApiResponse(code: '400', description: 'Neplatný vstup')]
    public function validateFormat(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $countryCode = $request->getParameter('countryCode');
            $vatNumber = $request->getParameter('vatNumber');

            if (empty($countryCode) || empty($vatNumber)) {
                return $this->createErrorResponse($response, 'Kód země a VAT číslo jsou povinné', 400);
            }

            $data = $this->vatService->validateFormat($countryCode, $vatNumber);
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        }
    }

    #[Path('/countries')]
    #[Method('GET')]
        #[ApiResponse(code: '200', description: 'Seznam EU zemí')]
    public function getSupportedCountries(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $this->vatService->getSupportedCountries();
            return $this->createSuccessResponse($response, $data);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 500);
        }
    }
}
