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
use App\Services\ImageService;

#[Path('/image')]
#[Tag('Utility APIs')]
#[OpenApi('
  Manipulace s obrázky pomocí PHP GD knihovny.
  Změna velikosti, ořez, rotace, konverze formátů, vodoznaky a další.
  Podporuje URL nebo base64 jako vstup, vrací base64 data URI.
')]
final class ImageController extends BaseController
{
    public function __construct(
        private readonly ImageService $imageService
    ) {
    }

    #[Path('/resize')]
    #[Method('GET')]
    #[RequestParameter(name: 'url', type: 'string', in: 'query', required: false, description: 'URL obrázku')]
    #[RequestParameter(name: 'base64', type: 'string', in: 'query', required: false, description: 'Base64 obrázku')]
    #[RequestParameter(name: 'width', type: 'int', in: 'query', required: false, description: 'Nová šířka (0 = auto)')]
    #[RequestParameter(name: 'height', type: 'int', in: 'query', required: false, description: 'Nová výška (0 = auto)')]
    #[RequestParameter(name: 'format', type: 'string', in: 'query', required: false, description: 'Formát: jpg, png, webp, gif (výchozí jpg)')]
    #[RequestParameter(name: 'quality', type: 'int', in: 'query', required: false, description: 'Kvalita 1-100 (výchozí 85)')]
    #[OpenApi('
      Změní velikost obrázku se zachováním poměru stran.

      Příklady:
      - /image/resize?url=https://example.com/photo.jpg&width=300
      - /image/resize?url=https://example.com/photo.jpg&height=200
      - /image/resize?url=https://example.com/photo.jpg&width=800&height=600&format=webp

      Použití v IRC:
      !resize https://example.com/photo.jpg 300x200 → Vrátí změněný obrázek

      Pokud zadáte pouze šířku nebo výšku, druhý rozměr se dopočítá
      podle poměru stran. Pokud zadáte oba, obrázek se natáhne/smrští.
    ')]
    #[ApiResponse(code: 200, description: 'Obrázek změněn')]
    #[ApiResponse(code: 400, description: 'Neplatné parametry')]
    public function resize(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $source = $this->getImageSource($request);
            $width = (int) ($request->getParameter('width') ?? 0);
            $height = (int) ($request->getParameter('height') ?? 0);
            $format = $request->getParameter('format') ?? 'jpg';
            $quality = (int) ($request->getParameter('quality') ?? 85);

            $result = $this->imageService->resize($source, $width, $height, $format, $quality);
            return $this->createSuccessResponse($response, $result);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při změně velikosti: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/crop')]
    #[Method('GET')]
    #[RequestParameter(name: 'url', type: 'string', in: 'query', required: false, description: 'URL obrázku')]
    #[RequestParameter(name: 'base64', type: 'string', in: 'query', required: false, description: 'Base64 obrázku')]
    #[RequestParameter(name: 'x', type: 'int', in: 'query', required: true, description: 'Počáteční X souřadnice')]
    #[RequestParameter(name: 'y', type: 'int', in: 'query', required: true, description: 'Počáteční Y souřadnice')]
    #[RequestParameter(name: 'width', type: 'int', in: 'query', required: true, description: 'Šířka ořezu')]
    #[RequestParameter(name: 'height', type: 'int', in: 'query', required: true, description: 'Výška ořezu')]
    #[RequestParameter(name: 'format', type: 'string', in: 'query', required: false, description: 'Formát výstupu')]
    #[RequestParameter(name: 'quality', type: 'int', in: 'query', required: false, description: 'Kvalita 1-100')]
    #[OpenApi('
      Ořízne obrázek na zadané souřadnice a rozměry.

      Příklady:
      - /image/crop?url=https://example.com/photo.jpg&x=100&y=100&width=300&height=200

      Použití:
      - Ořez profilových obrázků
      - Odstranění okrajů
      - Focus na konkrétní část obrázku
    ')]
    #[ApiResponse(code: 200, description: 'Obrázek oříznut')]
    #[ApiResponse(code: 400, description: 'Neplatné parametry')]
    public function crop(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $source = $this->getImageSource($request);
            $x = (int) $request->getParameter('x');
            $y = (int) $request->getParameter('y');
            $width = (int) $request->getParameter('width');
            $height = (int) $request->getParameter('height');
            $format = $request->getParameter('format') ?? 'jpg';
            $quality = (int) ($request->getParameter('quality') ?? 85);

            $result = $this->imageService->crop($source, $x, $y, $width, $height, $format, $quality);
            return $this->createSuccessResponse($response, $result);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při ořezu: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/rotate')]
    #[Method('GET')]
    #[RequestParameter(name: 'url', type: 'string', in: 'query', required: false, description: 'URL obrázku')]
    #[RequestParameter(name: 'base64', type: 'string', in: 'query', required: false, description: 'Base64 obrázku')]
    #[RequestParameter(name: 'degrees', type: 'int', in: 'query', required: true, description: 'Úhel otočení: 90, 180, 270')]
    #[RequestParameter(name: 'format', type: 'string', in: 'query', required: false, description: 'Formát výstupu')]
    #[RequestParameter(name: 'quality', type: 'int', in: 'query', required: false, description: 'Kvalita 1-100')]
    #[OpenApi('
      Otočí obrázek o zadaný úhel.

      Příklady:
      - /image/rotate?url=https://example.com/photo.jpg&degrees=90
      - /image/rotate?url=https://example.com/photo.jpg&degrees=180

      Podporované úhly: 90, 180, 270 stupňů
    ')]
    #[ApiResponse(code: 200, description: 'Obrázek otočen')]
    #[ApiResponse(code: 400, description: 'Neplatný úhel')]
    public function rotate(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $source = $this->getImageSource($request);
            $degrees = (int) $request->getParameter('degrees');
            $format = $request->getParameter('format') ?? 'jpg';
            $quality = (int) ($request->getParameter('quality') ?? 85);

            $result = $this->imageService->rotate($source, $degrees, $format, $quality);
            return $this->createSuccessResponse($response, $result);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při otáčení: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/flip')]
    #[Method('GET')]
    #[RequestParameter(name: 'url', type: 'string', in: 'query', required: false, description: 'URL obrázku')]
    #[RequestParameter(name: 'base64', type: 'string', in: 'query', required: false, description: 'Base64 obrázku')]
    #[RequestParameter(name: 'mode', type: 'string', in: 'query', required: true, description: 'Režim: horizontal, vertical, both')]
    #[RequestParameter(name: 'format', type: 'string', in: 'query', required: false, description: 'Formát výstupu')]
    #[RequestParameter(name: 'quality', type: 'int', in: 'query', required: false, description: 'Kvalita 1-100')]
    #[OpenApi('
      Převrátí (zrcadlí) obrázek horizontálně nebo vertikálně.

      Příklady:
      - /image/flip?url=https://example.com/photo.jpg&mode=horizontal
      - /image/flip?url=https://example.com/photo.jpg&mode=vertical
      - /image/flip?url=https://example.com/photo.jpg&mode=both

      Režimy:
      - horizontal = zleva doprava → zprava doleva
      - vertical = shora dolů → zespodu nahoru
      - both = obě osy současně
    ')]
    #[ApiResponse(code: 200, description: 'Obrázek převrácen')]
    #[ApiResponse(code: 400, description: 'Neplatný režim')]
    public function flip(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $source = $this->getImageSource($request);
            $mode = $request->getParameter('mode');
            $format = $request->getParameter('format') ?? 'jpg';
            $quality = (int) ($request->getParameter('quality') ?? 85);

            if ($mode === null) {
                return $this->createErrorResponse($response, 'Parametr mode je povinný', 400);
            }

            $result = $this->imageService->flip($source, $mode, $format, $quality);
            return $this->createSuccessResponse($response, $result);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při převrácení: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/convert')]
    #[Method('GET')]
    #[RequestParameter(name: 'url', type: 'string', in: 'query', required: false, description: 'URL obrázku')]
    #[RequestParameter(name: 'base64', type: 'string', in: 'query', required: false, description: 'Base64 obrázku')]
    #[RequestParameter(name: 'format', type: 'string', in: 'query', required: true, description: 'Cílový formát: jpg, png, webp, gif')]
    #[RequestParameter(name: 'quality', type: 'int', in: 'query', required: false, description: 'Kvalita 1-100 (jen jpg/webp)')]
    #[OpenApi('
      Převede obrázek na jiný formát.

      Příklady:
      - /image/convert?url=https://example.com/photo.png&format=jpg
      - /image/convert?url=https://example.com/photo.jpg&format=webp&quality=90

      Podporované formáty:
      - jpg/jpeg - dobrá komprese, bez průhlednosti
      - png - bezztrátová komprese, podporuje průhlednost
      - webp - moderní formát, lepší komprese (vyžaduje podporu v PHP)
      - gif - animace, omezená barevná paleta
    ')]
    #[ApiResponse(code: 200, description: 'Obrázek převeden')]
    #[ApiResponse(code: 400, description: 'Nepodporovaný formát')]
    public function convert(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $source = $this->getImageSource($request);
            $format = $request->getParameter('format');
            $quality = (int) ($request->getParameter('quality') ?? 85);

            if ($format === null) {
                return $this->createErrorResponse($response, 'Parametr format je povinný', 400);
            }

            $result = $this->imageService->convert($source, $format, $quality);
            return $this->createSuccessResponse($response, $result);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při konverzi: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/watermark')]
    #[Method('GET')]
    #[RequestParameter(name: 'url', type: 'string', in: 'query', required: false, description: 'URL obrázku')]
    #[RequestParameter(name: 'base64', type: 'string', in: 'query', required: false, description: 'Base64 obrázku')]
    #[RequestParameter(name: 'text', type: 'string', in: 'query', required: true, description: 'Text vodoznaku')]
    #[RequestParameter(name: 'position', type: 'string', in: 'query', required: false, description: 'Pozice: topleft, topright, bottomleft, bottomright, center')]
    #[RequestParameter(name: 'size', type: 'int', in: 'query', required: false, description: 'Velikost písma 1-5')]
    #[RequestParameter(name: 'format', type: 'string', in: 'query', required: false, description: 'Formát výstupu')]
    #[RequestParameter(name: 'quality', type: 'int', in: 'query', required: false, description: 'Kvalita 1-100')]
    #[OpenApi('
      Přidá textový vodoznak na obrázek.

      Příklady:
      - /image/watermark?url=https://example.com/photo.jpg&text=Copyright%202025
      - /image/watermark?url=https://example.com/photo.jpg&text=My%20Photo&position=bottomright&size=3

      Pozice:
      - topleft = levý horní roh
      - topright = pravý horní roh
      - bottomleft = levý dolní roh
      - bottomright = pravý dolní roh (výchozí)
      - center = střed obrázku
    ')]
    #[ApiResponse(code: 200, description: 'Vodoznak přidán')]
    #[ApiResponse(code: 400, description: 'Chybí text')]
    public function watermark(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $source = $this->getImageSource($request);
            $text = $request->getParameter('text');
            $position = $request->getParameter('position') ?? 'bottomright';
            $size = (int) ($request->getParameter('size') ?? 3);
            $format = $request->getParameter('format') ?? 'jpg';
            $quality = (int) ($request->getParameter('quality') ?? 85);

            if ($text === null || $text === '') {
                return $this->createErrorResponse($response, 'Parametr text je povinný', 400);
            }

            $result = $this->imageService->watermark($source, $text, $position, $size, $format, $quality);
            return $this->createSuccessResponse($response, $result);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při přidání vodoznaku: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/info')]
    #[Method('GET')]
    #[RequestParameter(name: 'url', type: 'string', in: 'query', required: false, description: 'URL obrázku')]
    #[RequestParameter(name: 'base64', type: 'string', in: 'query', required: false, description: 'Base64 obrázku')]
    #[OpenApi('
      Získá informace o obrázku bez jeho úpravy.

      Příklady:
      - /image/info?url=https://example.com/photo.jpg

      Vrací:
      - Rozměry (šířka × výška)
      - Typ/formát (jpg, png, gif, webp)
      - MIME typ
      - Velikost v bajtech a KB
      - Poměr stran (aspect ratio)

      Použití:
      - Validace před nahráním
      - Automatická detekce formátu
      - Zobrazení informací o obrázku
    ')]
    #[ApiResponse(code: 200, description: 'Informace získány')]
    #[ApiResponse(code: 400, description: 'Neplatný obrázek')]
    public function getInfo(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $source = $this->getImageSource($request);
            $result = $this->imageService->getInfo($source);
            return $this->createSuccessResponse($response, $result);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při získávání informací: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Získá zdroj obrázku z parametrů (URL nebo base64)
     */
    private function getImageSource(ApiRequest $request): string
    {
        $url = $request->getParameter('url');
        $base64 = $request->getParameter('base64');

        if ($url !== null) {
            return $url;
        }

        if ($base64 !== null) {
            return $base64;
        }

        throw new \RuntimeException('Musíte zadat parametr url nebo base64');
    }
}
