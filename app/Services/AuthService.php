<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * Service for admin authentication using SQLite
 */
final class AuthService
{
    private PDO $pdo;
    private const TOKEN_EXPIRATION = 86400; // 24 hours

    public function __construct(DatabaseService $database)
    {
        $this->pdo = $database->getPdo();
    }

    /**
     * Authenticate user with username and password
     */
    public function authenticate(string $username, string $password): ?string
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user) {
                return null;
            }

            if (!password_verify($password, $user['password_hash'])) {
                return null;
            }

            // Generate token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + self::TOKEN_EXPIRATION);

            $stmt = $this->pdo->prepare("
                INSERT INTO auth_tokens (token, username, expires_at)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$token, $username, $expiresAt]);

            return $token;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate token
     */
    public function validateToken(string $token): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM auth_tokens
                WHERE token = ? AND expires_at > datetime('now')
            ");
            $stmt->execute([$token]);
            $tokenData = $stmt->fetch();

            if (!$tokenData) {
                return null;
            }

            return [
                'username' => $tokenData['username'],
                'expires' => strtotime($tokenData['expires_at']),
            ];

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Revoke token (logout)
     */
    public function revokeToken(string $token): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM auth_tokens WHERE token = ?");
            $stmt->execute([$token]);
            return $stmt->rowCount() > 0;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get current user from request
     */
    public function getCurrentUser(string $authHeader): ?array
    {
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authHeader, 7);
        return $this->validateToken($token);
    }

    /**
     * Change user password
     */
    public function changePassword(string $username, string $newPassword): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users
                SET password_hash = ?, updated_at = datetime('now')
                WHERE username = ?
            ");
            $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $username]);
            return $stmt->rowCount() > 0;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Clean expired tokens
     */
    public function cleanExpiredTokens(): int
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM auth_tokens WHERE expires_at < datetime('now')
            ");
            $stmt->execute();
            return $stmt->rowCount();

        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get user info by username
     */
    public function getUser(string $username): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT username, role, created_at FROM users WHERE username = ?");
            $stmt->execute([$username]);
            return $stmt->fetch() ?: null;

        } catch (\Exception $e) {
            return null;
        }
    }
}
