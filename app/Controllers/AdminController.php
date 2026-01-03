<?php

declare(strict_types=1);

namespace App\Controllers;

use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Tag;
use Apitte\Core\Annotation\Controller\RequestBody;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Services\StatsService;
use App\Services\AuthService;

#[Path('/admin')]
#[Tag('Admin')]
final class AdminController extends BaseController
{
    public function __construct(
        private readonly StatsService $statsService,
        private readonly AuthService $authService
    ) {
    }

    #[Path('/login')]
    #[Method('POST')]
    public function login(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        try {
            $body = $request->getJsonBody();

            $username = $body['username'] ?? '';
            $password = $body['password'] ?? '';

            if (empty($username) || empty($password)) {
                return $this->createErrorResponse(
                    $response,
                    'Chybí username nebo password',
                    400
                );
            }

            $token = $this->authService->authenticate($username, $password);

            if ($token === null) {
                return $this->createErrorResponse(
                    $response,
                    'Nesprávné přihlašovací údaje',
                    401
                );
            }

            return $this->createSuccessResponse($response, [
                'token' => $token,
                'message' => 'Přihlášení úspěšné',
                'expires_in' => 86400,
            ]);
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                $response,
                'Chyba při přihlášení: ' . $e->getMessage(),
                500
            );
        }
    }

    #[Path('/logout')]
    #[Method('POST')]
    public function logout(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        try {
            $authHeader = $request->getHeaderLine('Authorization');
            $user = $this->authService->getCurrentUser($authHeader);

            if ($user === null) {
                return $this->createErrorResponse(
                    $response,
                    'Neplatný token',
                    401
                );
            }

            $token = substr($authHeader, 7);
            $this->authService->revokeToken($token);

            return $this->createSuccessResponse($response, [
                'message' => 'Odhlášení úspěšné',
            ]);
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                $response,
                'Chyba při odhlášení: ' . $e->getMessage(),
                500
            );
        }
    }

    #[Path('/me')]
    #[Method('GET')]
    public function getCurrentUser(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        try {
            $authHeader = $request->getHeaderLine('Authorization');
            $user = $this->authService->getCurrentUser($authHeader);

            if ($user === null) {
                return $this->createErrorResponse(
                    $response,
                    'Nepřihlášen',
                    401
                );
            }

            return $this->createSuccessResponse($response, [
                'username' => $user['username'],
                'authenticated' => true,
            ]);
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                $response,
                'Chyba: ' . $e->getMessage(),
                500
            );
        }
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
            // Require authentication for reset
            $authHeader = $request->getHeaderLine('Authorization');
            $user = $this->authService->getCurrentUser($authHeader);

            if ($user === null) {
                return $this->createErrorResponse(
                    $response,
                    'Přihlášení vyžadováno',
                    401
                );
            }

            $this->statsService->resetStats();

            return $this->createSuccessResponse($response, [
                'message' => 'Statistiky byly resetovány',
            ]);
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                $response,
                'Nepodařilo se resetovat statistiky: ' . $e->getMessage(),
                500
            );
        }
    }

    #[Path('/stats/hourly')]
    #[Method('GET')]
    public function getHourlyStats(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        try {
            $stats = $this->statsService->getStats();
            $byHour = $stats['byHour'] ?? [];

            // Fill missing hours with zeros (last 24 hours)
            $hourlyData = [];
            $now = new \DateTime();
            for ($i = 23; $i >= 0; $i--) {
                $hour = (clone $now)->modify("-{$i} hours")->format('Y-m-d H:00');
                $hourlyData[] = [
                    'hour' => $hour,
                    'label' => (clone $now)->modify("-{$i} hours")->format('H:00'),
                    'requests' => $byHour[$hour] ?? 0,
                ];
            }

            return $this->createSuccessResponse($response, [
                'hourly' => $hourlyData,
                'total' => array_sum(array_column($hourlyData, 'requests')),
            ]);
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                $response,
                'Nepodařilo se načíst hodinové statistiky: ' . $e->getMessage(),
                500
            );
        }
    }

    #[Path('/stats/errors')]
    #[Method('GET')]
    public function getErrorStats(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        try {
            $errorStats = $this->statsService->getErrorStats();

            return $this->createSuccessResponse($response, $errorStats);
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                $response,
                'Nepodařilo se načíst chybové statistiky: ' . $e->getMessage(),
                500
            );
        }
    }

    #[Path('/stats/latency')]
    #[Method('GET')]
    public function getLatencyStats(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        try {
            $latencyStats = $this->statsService->getLatencyStats();

            return $this->createSuccessResponse($response, $latencyStats);
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                $response,
                'Nepodařilo se načíst latency statistiky: ' . $e->getMessage(),
                500
            );
        }
    }

    #[Path('/health')]
    #[Method('GET')]
    public function getHealth(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        try {
            $health = $this->statsService->getHealthStatus();

            return $this->createSuccessResponse($response, $health);
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                $response,
                'Health check failed: ' . $e->getMessage(),
                500
            );
        }
    }

    #[Path('/stats/by-ip')]
    #[Method('GET')]
    public function getStatsByIp(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        try {
            $stats = $this->statsService->getStatsByIp();

            return $this->createSuccessResponse($response, [
                'by_ip' => $stats,
                'total_unique_ips' => count($stats),
            ]);
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                $response,
                'Failed to get IP stats: ' . $e->getMessage(),
                500
            );
        }
    }

    #[Path('/stats/by-user-agent')]
    #[Method('GET')]
    public function getStatsByUserAgent(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        try {
            $stats = $this->statsService->getStatsByUserAgent();

            return $this->createSuccessResponse($response, [
                'by_user_agent' => $stats,
                'total_unique_agents' => count($stats),
            ]);
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                $response,
                'Failed to get user agent stats: ' . $e->getMessage(),
                500
            );
        }
    }

    #[Path('/stats/requests')]
    #[Method('GET')]
    public function getFilteredRequests(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        try {
            $ip = $request->getQueryParam('ip');
            $userAgent = $request->getQueryParam('user_agent');
            $limit = (int) ($request->getQueryParam('limit') ?? 100);

            $requests = $this->statsService->getFilteredRequests($ip, $userAgent, min($limit, 500));

            return $this->createSuccessResponse($response, [
                'requests' => $requests,
                'count' => count($requests),
                'filters' => [
                    'ip' => $ip,
                    'user_agent' => $userAgent,
                    'limit' => $limit,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                $response,
                'Failed to get filtered requests: ' . $e->getMessage(),
                500
            );
        }
    }

    #[Path('/stats/live')]
    #[Method('GET')]
    public function getLiveStats(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        try {
            $stats = $this->statsService->getLiveStats();

            return $this->createSuccessResponse($response, $stats);
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                $response,
                'Failed to get live stats: ' . $e->getMessage(),
                500
            );
        }
    }

    #[Path('/change-password')]
    #[Method('POST')]
    public function changePassword(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        try {
            $authHeader = $request->getHeaderLine('Authorization');
            $user = $this->authService->getCurrentUser($authHeader);

            if ($user === null) {
                return $this->createErrorResponse(
                    $response,
                    'Přihlášení vyžadováno',
                    401
                );
            }

            $body = $request->getJsonBody();
            $newPassword = $body['new_password'] ?? '';

            if (strlen($newPassword) < 6) {
                return $this->createErrorResponse(
                    $response,
                    'Heslo musí mít alespoň 6 znaků',
                    400
                );
            }

            $success = $this->authService->changePassword($user['username'], $newPassword);

            if (!$success) {
                return $this->createErrorResponse(
                    $response,
                    'Nepodařilo se změnit heslo',
                    500
                );
            }

            return $this->createSuccessResponse($response, [
                'message' => 'Heslo bylo změněno',
            ]);
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                $response,
                'Chyba: ' . $e->getMessage(),
                500
            );
        }
    }
}
