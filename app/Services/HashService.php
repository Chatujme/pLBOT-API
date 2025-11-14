<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service pro hashování a kódování dat
 */
final class HashService
{
    private const SUPPORTED_HASH_ALGOS = [
        'md5', 'sha1', 'sha256', 'sha512',
        'sha3-256', 'sha3-512',
        'ripemd160', 'whirlpool',
    ];

    /**
     * Vypočítá hash pro zadaná data
     *
     * @param string $data Data k zahashování
     * @param string $algorithm Algoritmus (md5, sha1, sha256, sha512, ...)
     * @param bool $binary Vrátit binary nebo hex (výchozí false = hex)
     * @return array{data: array<string, mixed>}
     */
    public function hash(string $data, string $algorithm = 'sha256', bool $binary = false): array
    {
        $algorithm = strtolower($algorithm);

        if (!in_array($algorithm, self::SUPPORTED_HASH_ALGOS, true)) {
            throw new \RuntimeException(
                "Nepodporovaný algoritmus. Použijte: " . implode(', ', self::SUPPORTED_HASH_ALGOS)
            );
        }

        $hash = hash($algorithm, $data, $binary);

        return [
            'data' => [
                'algorithm' => $algorithm,
                'hash' => $binary ? base64_encode($hash) : $hash,
                'length' => strlen($hash),
                'input_length' => strlen($data),
                'binary' => $binary,
            ],
        ];
    }

    /**
     * Base64 kódování
     *
     * @param string $data Data ke kódování
     * @return array{data: array<string, string>}
     */
    public function base64Encode(string $data): array
    {
        return [
            'data' => [
                'encoded' => base64_encode($data),
                'original_length' => strlen($data),
                'encoded_length' => strlen(base64_encode($data)),
            ],
        ];
    }

    /**
     * Base64 dekódování
     *
     * @param string $data Data k dekódování
     * @return array{data: array<string, mixed>}
     */
    public function base64Decode(string $data): array
    {
        $decoded = base64_decode($data, true);

        if ($decoded === false) {
            throw new \RuntimeException('Neplatná Base64 data');
        }

        return [
            'data' => [
                'decoded' => $decoded,
                'encoded_length' => strlen($data),
                'decoded_length' => strlen($decoded),
            ],
        ];
    }

    /**
     * URL-safe Base64 kódování
     *
     * @param string $data Data ke kódování
     * @return array{data: array<string, string>}
     */
    public function base64UrlEncode(string $data): array
    {
        $base64 = base64_encode($data);
        $urlSafe = strtr($base64, '+/', '-_');
        $urlSafe = rtrim($urlSafe, '=');

        return [
            'data' => [
                'encoded' => $urlSafe,
                'original_length' => strlen($data),
            ],
        ];
    }

    /**
     * URL-safe Base64 dekódování
     *
     * @param string $data Data k dekódování
     * @return array{data: array<string, string>}
     */
    public function base64UrlDecode(string $data): array
    {
        $base64 = strtr($data, '-_', '+/');
        $padding = strlen($data) % 4;
        if ($padding > 0) {
            $base64 .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($base64, true);

        if ($decoded === false) {
            throw new \RuntimeException('Neplatná URL-safe Base64 data');
        }

        return [
            'data' => [
                'decoded' => $decoded,
            ],
        ];
    }

    /**
     * Hex kódování
     *
     * @param string $data Data ke kódování
     * @return array{data: array<string, string>}
     */
    public function hexEncode(string $data): array
    {
        return [
            'data' => [
                'encoded' => bin2hex($data),
                'original_length' => strlen($data),
            ],
        ];
    }

    /**
     * Hex dekódování
     *
     * @param string $data Data k dekódování
     * @return array{data: array<string, mixed>}
     */
    public function hexDecode(string $data): array
    {
        $decoded = @hex2bin($data);

        if ($decoded === false) {
            throw new \RuntimeException('Neplatná HEX data');
        }

        return [
            'data' => [
                'decoded' => $decoded,
            ],
        ];
    }

    /**
     * HMAC (Hash-based Message Authentication Code)
     *
     * @param string $data Data
     * @param string $key Klíč
     * @param string $algorithm Algoritmus
     * @return array{data: array<string, string>}
     */
    public function hmac(string $data, string $key, string $algorithm = 'sha256'): array
    {
        $algorithm = strtolower($algorithm);

        if (!in_array($algorithm, hash_hmac_algos(), true)) {
            throw new \RuntimeException("Nepodporovaný HMAC algoritmus");
        }

        $hmac = hash_hmac($algorithm, $data, $key);

        return [
            'data' => [
                'hmac' => $hmac,
                'algorithm' => $algorithm,
                'key_length' => strlen($key),
            ],
        ];
    }

    /**
     * Získá seznam podporovaných hash algoritmů
     *
     * @return array{data: array<string, mixed>}
     */
    public function getSupportedAlgorithms(): array
    {
        return [
            'data' => [
                'hash_algos' => self::SUPPORTED_HASH_ALGOS,
                'all_hash_algos' => hash_algos(),
                'hmac_algos' => hash_hmac_algos(),
            ],
        ];
    }

    /**
     * Porovná dva hashe v constant-time (ochrana proti timing attacks)
     *
     * @param string $hash1 První hash
     * @param string $hash2 Druhý hash
     * @return array{data: array<string, bool>}
     */
    public function compareHashes(string $hash1, string $hash2): array
    {
        return [
            'data' => [
                'equal' => hash_equals($hash1, $hash2),
            ],
        ];
    }
}
