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
use App\Services\VatService;

#[Path('/vat')]
#[Tag('EU VAT Verification')]
#[OpenApi('
  Ověřování EU VAT čísel přes VIES systém.
  VIES (VAT Information Exchange System) je oficiální EU služba
  pro ověřování registrace k DPH v členských státech EU.
  Data jsou cachována 1 den.
')]
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
    #[OpenApi('
      Ověří platnost VAT čísla v EU systému VIES.

      Příklady:
      - /vat/validate/CZ/12345678 - ověří české IČ DPH
      - /vat/validate/DE/123456789 - ověří německé VAT
      - /vat/validate/SK/1234567890 - ověří slovenské IČ DPH

      Vrací:
      - Zda je VAT platné (valid: true/false)
      - Název firmy registrované na VAT
      - Adresu firmy
      - Datum ověření
    ')]
    #[ApiResponse(code: 200, description: 'VAT ověřeno (může být platné i neplatné)')]
    #[ApiResponse(code: 400, description: 'Neplatný vstup')]
    #[ApiResponse(code: 500, description: 'Chyba VIES služby')]
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
    #[OpenApi('
      Ověří VAT číslo zadané jako jeden řetězec včetně kódu země.

      Příklady:
      - /vat/check/CZ12345678
      - /vat/check/DE123456789
      - /vat/check/SK1234567890

      Pohodlnější endpoint pro případy, kdy máte celé VAT číslo najednou.
    ')]
    #[ApiResponse(code: 200, description: 'VAT ověřeno')]
    #[ApiResponse(code: 400, description: 'Neplatný vstup')]
    #[ApiResponse(code: 500, description: 'Chyba VIES služby')]
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
    #[OpenApi('
      Validuje pouze FORMÁT VAT čísla bez dotazu na VIES.

      Rychlejší než plná validace, jen zkontroluje, zda VAT číslo
      odpovídá formátu dané země (počet číslic, struktura).

      Neověřuje, zda je VAT skutečně registrováno v EU systému!

      Příklady:
      - /vat/format/CZ/12345678 - zkontroluje formát českého IČ DPH
      - /vat/format/DE/123456789 - zkontroluje formát německého VAT
    ')]
    #[ApiResponse(code: 200, description: 'Validace formátu provedena')]
    #[ApiResponse(code: 400, description: 'Neplatný vstup')]
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
    #[OpenApi('
      Vrátí seznam všech podporovaných EU zemí.

      Pro každou zemi vrací kód (např. CZ) a název (např. Czech Republic).

      Užitečné pro validaci vstupů nebo nápovědu uživateli.
    ')]
    #[ApiResponse(code: 200, description: 'Seznam EU zemí')]
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
