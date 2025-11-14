<?php

declare(strict_types=1);

namespace App\Controllers;

use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Tag;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Services\StatsService;

#[Path('/admin')]
#[Tag('Admin')]
final class AdminController extends BaseController
{
    public function __construct(
        private readonly StatsService $statsService
    ) {
    }

    #[Path('/stats')]
    #[Method('GET')]
    public function getStats(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        try {
            $stats = $this->statsService->getStats();

            return $this->createSuccessResponse($response, $stats);
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                $response,
                'Nepodařilo se načíst statistiky: ' . $e->getMessage(),
                500
            );
        }
    }

    #[Path('/stats/reset')]
    #[Method('POST')]
    public function resetStats(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        try {
            $this->statsService->resetStats();

            return $this->createSuccessResponse($response, [
                'message' => 'Statistiky byly resetovány'
            ]);
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                $response,
                'Nepodařilo se resetovat statistiky: ' . $e->getMessage(),
                500
            );
        }
    }
}
