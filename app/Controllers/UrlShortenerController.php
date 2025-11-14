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
use App\Services\UrlShortenerService;

#[Path('/url')]
#[Tag('Utility APIs')]
final class UrlShortenerController extends BaseController
{
    public function __construct(
        private readonly UrlShortenerService $urlShortenerService
    ) {
    }

    #[Path('/shorten')]
    #[Method('GET')]
    #[RequestParameter(name: 'url', type: 'string', in: 'query', required: true, description: 'URL ke zkrácení')]
    #[RequestParameter(name: 'alias', type: 'string', in: 'query', required: false, description: 'Vlastní alias (pouze is.gd)')]
    #[RequestParameter(name: 'service', type: 'string', in: 'query', required: false, description: 'Služba: isgd nebo tinyurl (auto výchozí)')]
        #[ApiResponse(code: '200', description: 'URL zkrácena')]
    #[ApiResponse(code: '400', description: 'Neplatná URL')]
    #[ApiResponse(code: '500', description: 'Chyba služby')]
    public function shorten(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $url = $request->getParameter('url');
            $alias = $request->getParameter('alias');
            $service = $request->getParameter('service');

            if (empty($url)) {
                return $this->createErrorResponse($response, 'URL je povinný parametr', 400);
            }

            $result = match ($service) {
                'isgd' => $this->urlShortenerService->shortenWithIsGd($url, $alias),
                'tinyurl' => $this->urlShortenerService->shortenWithTinyUrl($url),
                default => $this->urlShortenerService->shorten($url, $alias),
            };

            return $this->createSuccessResponse($response, $result);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při zkracování URL: ' . $e->getMessage(), 500);
        }
    }

    #[Path('/stats')]
    #[Method('GET')]
    #[RequestParameter(name: 'short_url', type: 'string', in: 'query', required: true, description: 'Zkrácená is.gd URL')]
        #[ApiResponse(code: '200', description: 'Statistiky získány')]
    #[ApiResponse(code: '400', description: 'Neplatná URL nebo není is.gd')]
    public function getStats(ApiRequest $request, HttpApiResponse $response): HttpApiResponse
    {
        try {
            $shortUrl = $request->getParameter('short_url');

            if (empty($shortUrl)) {
                return $this->createErrorResponse($response, 'short_url je povinný parametr', 400);
            }

            $result = $this->urlShortenerService->getStats($shortUrl);
            return $this->createSuccessResponse($response, $result);
        } catch (\RuntimeException $e) {
            return $this->createErrorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->createErrorResponse($response, 'Chyba při získávání statistik: ' . $e->getMessage(), 500);
        }
    }
}
