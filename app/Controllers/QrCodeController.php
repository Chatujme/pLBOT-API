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
use App\Services\QrCodeService;

#[Path('/qr')]
#[Tag('Utility APIs')]
#[OpenApi('
  Generování QR kódů pro texty, URLs, vCard kontakty a WiFi.
  Bez registrace, bez limitů.
')]
final class QrCodeController extends BaseController
{
    public function __construct(
        private readonly QrCodeService $qrCodeService
    ) {
    }

    #[Path('/generate')]
    #[Method('GET')]
    #[RequestParameter(name: 'data', type: 'string', in: 'query', required: true, description: 'Text nebo URL pro QR kód')]
    #[RequestParameter(name: 'size', type: 'int', in: 'query', required: false, description: 'Velikost v pixelech (50-1000, výchozí 200)')]
    #[RequestParameter(name: 'format', type: 'string', in: 'query', required: false, description: 'Formát: png, svg, eps (výchozí png)')]
    #[OpenApi('
      Vygeneruje QR kód pro jakýkoliv text nebo URL.

      Příklady:
      - /qr/generate?data=https://github.com - URL
      - /qr/generate?data=Hello%20World&size=300 - Text s vlastní velikostí
      - /qr/generate?data=tel:+420123456789 - Telefonní číslo
      - /qr/generate?data=mailto:info@example.com - Email

      Podporované formáty:
      - png (výchozí) - rastrový obrázek
      - svg - vektorová grafika
      - eps - pro tisk
    ')]
    #[ApiResponse(code: 200, description: 'URL QR kódu vygenerován')]
    #[ApiResponse(code: 400, description: 'Neplatná data')]
    public function generate(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $data = $request->getParameter('data');
            $sizeParam = $request->getParameter('size');
            $format = $request->getParameter('format') ?? 'png';

            if (empty($data)) {
                return $this->createErrorResponse($response, 'Parametr data je povinný', 400);
            }

            $size = $sizeParam !== null ? (int) $sizeParam : 200;

            $result = $this->qrCodeService->generate($data, $size, $format);
            return $this->createSuccessResponse($response, $result);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při generování QR kódu: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/vcard')]
    #[Method('POST')]
    #[OpenApi('
      Vygeneruje QR kód pro vCard kontakt.

      POST body (JSON):
      {
        "name": "Jan Novák",
        "organization": "Firma s.r.o.",
        "phone": "+420123456789",
        "email": "jan@example.com",
        "url": "https://example.com",
        "address": "Praha, Česká republika"
      }

      Použití v IRC:
      !qr vcard "Jan Novák" "+420123456789" "jan@example.com"
    ')]
    #[ApiResponse(code: 200, description: 'vCard QR kód vygenerován')]
    #[ApiResponse(code: 400, description: 'Neplatná data')]
    public function generateVCard(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $body = $request->getJsonBody();

            if (!is_array($body) || empty($body)) {
                return $this->createErrorResponse($response, 'Kontaktní údaje jsou povinné', 400);
            }

            $size = isset($body['size']) ? (int) $body['size'] : 200;
            unset($body['size']);

            $result = $this->qrCodeService->generateVCard($body, $size);
            return $this->createSuccessResponse($response, $result);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při generování vCard: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/wifi')]
    #[Method('GET')]
    #[RequestParameter(name: 'ssid', type: 'string', in: 'query', required: true, description: 'Název WiFi sítě')]
    #[RequestParameter(name: 'password', type: 'string', in: 'query', required: true, description: 'Heslo WiFi')]
    #[RequestParameter(name: 'encryption', type: 'string', in: 'query', required: false, description: 'Typ šifrování: WPA, WEP, nopass (výchozí WPA)')]
    #[RequestParameter(name: 'size', type: 'int', in: 'query', required: false, description: 'Velikost QR kódu')]
    #[OpenApi('
      Vygeneruje QR kód pro připojení k WiFi.

      Po naskenování QR kódu mobilním zařízením se automaticky připojí k síti.

      Příklady:
      - /qr/wifi?ssid=MojeWiFi&password=heslo123 - WPA šifrování
      - /qr/wifi?ssid=GuestWiFi&password=&encryption=nopass - Bez hesla

      Použití v IRC:
      !qrwifi "MojeWiFi" "heslo123"
    ')]
    #[ApiResponse(code: 200, description: 'WiFi QR kód vygenerován')]
    #[ApiResponse(code: 400, description: 'Neplatná data')]
    public function generateWiFi(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $ssid = $request->getParameter('ssid');
            $password = $request->getParameter('password');
            $encryption = $request->getParameter('encryption') ?? 'WPA';
            $sizeParam = $request->getParameter('size');

            if (empty($ssid)) {
                return $this->createErrorResponse($response, 'SSID je povinný parametr', 400);
            }

            $size = $sizeParam !== null ? (int) $sizeParam : 200;

            $result = $this->qrCodeService->generateWiFi($ssid, $password ?? '', $encryption, $size);
            return $this->createSuccessResponse($response, $result);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při generování WiFi QR: ' . $e->getMessage(), 500);
        }
    }
}
