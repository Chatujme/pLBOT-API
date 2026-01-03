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
