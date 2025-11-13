<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service pro generování UUID (Universally Unique Identifier)
 */
final class UUIDService
{
    /**
     * Generuje UUID v4 (random)
     *
     * @return array{data: array<string, mixed>}
     */
    public function generateV4(): array
    {
        try {
            $data = random_bytes(16);

            // Nastavit verzi (4)
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
            // Nastavit variant (RFC 4122)
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

            $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

            return ['data' => [
                'uuid' => $uuid,
                'version' => 4,
                'variant' => 'RFC 4122',
            ]];
        } catch (\Exception $e) {
            throw new \RuntimeException("Nepodařilo se vygenerovat UUID: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Generuje více UUID najednou
     *
     * @param int $count Počet UUID (1-100)
     * @return array{data: array<string, mixed>}
     */
    public function generateMultiple(int $count = 1): array
    {
        if ($count < 1 || $count > 100) {
            throw new \RuntimeException('Počet UUID musí být mezi 1 a 100');
        }

        $uuids = [];
        for ($i = 0; $i < $count; $i++) {
            $result = $this->generateV4();
            $uuids[] = $result['data']['uuid'];
        }

        return ['data' => [
            'uuids' => $uuids,
            'count' => count($uuids),
            'version' => 4,
        ]];
    }

    /**
     * Validuje UUID
     *
     * @param string $uuid UUID k validaci
     * @return array{data: array<string, mixed>}
     */
    public function validate(string $uuid): array
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        $isValid = preg_match($pattern, $uuid) === 1;

        $version = null;
        $variant = null;

        if ($isValid) {
            // Extrahovat verzi
            $versionChar = $uuid[14];
            $version = (int) $versionChar;

            // Extrahovat variant
            $variantChar = $uuid[19];
            $variantInt = hexdec($variantChar);

            if (($variantInt & 0x8) === 0x8) {
                $variant = 'RFC 4122';
            } elseif (($variantInt & 0x4) === 0x4) {
                $variant = 'Microsoft';
            } else {
                $variant = 'Reserved';
            }
        }

        return ['data' => [
            'uuid' => $uuid,
            'valid' => $isValid,
            'version' => $version,
            'variant' => $variant,
        ]];
    }

    /**
     * Generuje NIL UUID (všechny nuly)
     *
     * @return array{data: array<string, mixed>}
     */
    public function generateNil(): array
    {
        return ['data' => [
            'uuid' => '00000000-0000-0000-0000-000000000000',
            'version' => 'NIL',
            'variant' => 'NIL',
        ]];
    }
}
