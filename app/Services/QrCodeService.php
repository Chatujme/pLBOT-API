<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service pro generování QR kódů
 */
final class QrCodeService
{
    private const QR_API_URL = 'https://api.qrserver.com/v1/create-qr-code/';
    private const DEFAULT_SIZE = 200;
    private const MIN_SIZE = 50;
    private const MAX_SIZE = 1000;

    /**
     * Vygeneruje QR kód pro zadaný text/URL
     *
     * @param string $data Text nebo URL pro zakódování
     * @param int $size Velikost QR kódu v pixelech (50-1000)
     * @param string $format Formát výstupu (png, svg, eps)
     * @return array{data: array<string, mixed>}
     */
    public function generate(string $data, int $size = self::DEFAULT_SIZE, string $format = 'png'): array
    {
        if (empty($data)) {
            throw new \RuntimeException('Data pro QR kód nemohou být prázdná');
        }

        if ($size < self::MIN_SIZE || $size > self::MAX_SIZE) {
            throw new \RuntimeException("Velikost musí být mezi " . self::MIN_SIZE . " a " . self::MAX_SIZE . " pixely");
        }

        $allowedFormats = ['png', 'svg', 'eps'];
        if (!in_array($format, $allowedFormats, true)) {
            throw new \RuntimeException("Neplatný formát. Použijte: " . implode(', ', $allowedFormats));
        }

        $url = $this->buildQrUrl($data, $size, $format);

        return [
            'data' => [
                'qr_code_url' => $url,
                'original_data' => $data,
                'size' => $size,
                'format' => $format,
                'length' => strlen($data),
            ],
        ];
    }

    /**
     * Vygeneruje vCard QR kód pro kontakt
     *
     * @param array<string, string> $contact Kontaktní údaje
     * @return array{data: array<string, mixed>}
     */
    public function generateVCard(array $contact, int $size = self::DEFAULT_SIZE): array
    {
        $vcard = $this->buildVCard($contact);
        return $this->generate($vcard, $size);
    }

    /**
     * Vygeneruje WiFi QR kód
     *
     * @param string $ssid Název WiFi sítě
     * @param string $password Heslo
     * @param string $encryption Typ šifrování (WPA, WEP, nopass)
     * @return array{data: array<string, mixed>}
     */
    public function generateWiFi(string $ssid, string $password, string $encryption = 'WPA', int $size = self::DEFAULT_SIZE): array
    {
        $allowedEncryption = ['WPA', 'WEP', 'nopass'];
        if (!in_array($encryption, $allowedEncryption, true)) {
            throw new \RuntimeException("Neplatné šifrování. Použijte: " . implode(', ', $allowedEncryption));
        }

        $wifiString = sprintf('WIFI:T:%s;S:%s;P:%s;;', $encryption, $ssid, $password);

        return $this->generate($wifiString, $size);
    }

    /**
     * Vytvoří URL pro QR kód API
     */
    private function buildQrUrl(string $data, int $size, string $format): string
    {
        $params = [
            'data' => $data,
            'size' => $size . 'x' . $size,
            'format' => $format,
        ];

        return self::QR_API_URL . '?' . http_build_query($params);
    }

    /**
     * Vytvoří vCard formát z kontaktních údajů
     *
     * @param array<string, string> $contact
     */
    private function buildVCard(array $contact): string
    {
        $vcard = "BEGIN:VCARD\nVERSION:3.0\n";

        if (isset($contact['name'])) {
            $vcard .= "FN:{$contact['name']}\n";
        }

        if (isset($contact['organization'])) {
            $vcard .= "ORG:{$contact['organization']}\n";
        }

        if (isset($contact['phone'])) {
            $vcard .= "TEL:{$contact['phone']}\n";
        }

        if (isset($contact['email'])) {
            $vcard .= "EMAIL:{$contact['email']}\n";
        }

        if (isset($contact['url'])) {
            $vcard .= "URL:{$contact['url']}\n";
        }

        if (isset($contact['address'])) {
            $vcard .= "ADR:{$contact['address']}\n";
        }

        $vcard .= "END:VCARD";

        return $vcard;
    }
}
