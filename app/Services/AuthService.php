<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Utils\FileSystem;
use Nette\Utils\Json;

/**
 * Service for admin authentication
 */
final class AuthService
{
    private const CONFIG_FILE = __DIR__ . '/../../config/auth.json';
    private const TOKEN_EXPIRATION = 86400; // 24 hours

    private array $config;
    private array $tokens = [];

    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * Authenticate user with username and password
     */
    public function authenticate(string $username, string $password): ?string
    {
        $users = $this->config['users'] ?? [];

        if (!isset($users[$username])) {
            return null;
        }

        $user = $users[$username];

        // Verify password (use password_verify for hashed passwords)
        if (isset($user['password_hash'])) {
            if (!password_verify($password, $user['password_hash'])) {
                return null;
            }
        } elseif (isset($user['password'])) {
            // Plain text fallback (not recommended)
            if ($password !== $user['password']) {
                return null;
            }
        } else {
            return null;
        }

        // Generate token
        $token = bin2hex(random_bytes(32));
        $this->tokens[$token] = [
            'username' => $username,
            'created' => time(),
            'expires' => time() + self::TOKEN_EXPIRATION,
        ];

        $this->saveTokens();

        return $token;
    }

    /**
     * Validate token
     */
    public function validateToken(string $token): ?array
    {
        $this->loadTokens();

        if (!isset($this->tokens[$token])) {
            return null;
        }

        $tokenData = $this->tokens[$token];

        // Check expiration
        if ($tokenData['expires'] < time()) {
            unset($this->tokens[$token]);
            $this->saveTokens();
            return null;
        }

        return $tokenData;
    }

    /**
     * Revoke token (logout)
     */
    public function revokeToken(string $token): bool
    {
        $this->loadTokens();

        if (!isset($this->tokens[$token])) {
            return false;
        }

        unset($this->tokens[$token]);
        $this->saveTokens();

        return true;
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
     * Load config from file
     */
    private function loadConfig(): void
    {
        if (!file_exists(self::CONFIG_FILE)) {
            // Create default config
            $this->config = [
                'users' => [
                    'admin' => [
                        'password_hash' => password_hash('admin', PASSWORD_DEFAULT),
                        'role' => 'admin',
                    ],
                ],
            ];
            $this->saveConfig();
            return;
        }

        try {
            $content = FileSystem::read(self::CONFIG_FILE);
            $this->config = Json::decode($content, forceArrays: true);
        } catch (\Exception $e) {
            $this->config = ['users' => []];
        }
    }

    /**
     * Save config to file
     */
    private function saveConfig(): void
    {
        try {
            $dir = dirname(self::CONFIG_FILE);
            if (!is_dir($dir)) {
                FileSystem::createDir($dir);
            }
            FileSystem::write(self::CONFIG_FILE, Json::encode($this->config, Json::PRETTY));
        } catch (\Exception $e) {
            // Silent fail
        }
    }

    /**
     * Load tokens from temp file
     */
    private function loadTokens(): void
    {
        $tokensFile = dirname(self::CONFIG_FILE) . '/../temp/tokens.json';

        if (!file_exists($tokensFile)) {
            $this->tokens = [];
            return;
        }

        try {
            $content = FileSystem::read($tokensFile);
            $this->tokens = Json::decode($content, forceArrays: true);

            // Clean expired tokens
            $now = time();
            $this->tokens = array_filter($this->tokens, fn($t) => $t['expires'] > $now);
        } catch (\Exception $e) {
            $this->tokens = [];
        }
    }

    /**
     * Save tokens to temp file
     */
    private function saveTokens(): void
    {
        $tokensFile = dirname(self::CONFIG_FILE) . '/../temp/tokens.json';

        try {
            $dir = dirname($tokensFile);
            if (!is_dir($dir)) {
                FileSystem::createDir($dir);
            }
            FileSystem::write($tokensFile, Json::encode($this->tokens, Json::PRETTY));
        } catch (\Exception $e) {
            // Silent fail
        }
    }

    /**
     * Change user password
     */
    public function changePassword(string $username, string $newPassword): bool
    {
        if (!isset($this->config['users'][$username])) {
            return false;
        }

        $this->config['users'][$username]['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        unset($this->config['users'][$username]['password']); // Remove plain text if exists

        $this->saveConfig();
        return true;
    }
}
