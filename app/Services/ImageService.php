<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service pro manipulaci s obrázky pomocí PHP GD knihovny
 */
final class ImageService
{
    private const MAX_IMAGE_SIZE = 10 * 1024 * 1024; // 10 MB
    private const MAX_DIMENSION = 4000; // Maximum width or height

    public function __construct(
        private readonly HttpClientService $httpClient
    ) {
    }

    /**
     * Změní velikost obrázku
     *
     * @param string $imageSource URL nebo base64 obrázku
     * @param int $width Nová šířka (0 = automaticky podle poměru stran)
     * @param int $height Nová výška (0 = automaticky podle poměru stran)
     * @param string $format Výstupní formát (jpg, png, webp, gif)
     * @param int $quality Kvalita pro JPG/WEBP (1-100)
     * @return array{data: array<string, mixed>}
     */
    public function resize(
        string $imageSource,
        int $width = 0,
        int $height = 0,
        string $format = 'jpg',
        int $quality = 85
    ): array {
        $this->checkGdSupport();

        if ($width === 0 && $height === 0) {
            throw new \RuntimeException('Musíte zadat alespoň šířku nebo výšku');
        }

        if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            throw new \RuntimeException('Maximální rozměr je ' . self::MAX_DIMENSION . 'px');
        }

        $image = $this->loadImage($imageSource);
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        // Vypočítáme nové rozměry se zachováním poměru stran
        if ($width === 0) {
            $width = (int) ($originalWidth * ($height / $originalHeight));
        } elseif ($height === 0) {
            $height = (int) ($originalHeight * ($width / $originalWidth));
        }

        $resized = imagecreatetruecolor($width, $height);
        if ($resized === false) {
            throw new \RuntimeException('Nepodařilo se vytvořit nový obrázek');
        }

        // Zachování průhlednosti pro PNG a GIF
        if ($format === 'png' || $format === 'gif') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
        imagedestroy($image);

        $output = $this->imageToBase64($resized, $format, $quality);
        imagedestroy($resized);

        return [
            'data' => [
                'image' => $output,
                'format' => $format,
                'width' => $width,
                'height' => $height,
                'original_width' => $originalWidth,
                'original_height' => $originalHeight,
                'quality' => $quality,
            ],
        ];
    }

    /**
     * Ořízne obrázek
     *
     * @param string $imageSource URL nebo base64 obrázku
     * @param int $x Počáteční X souřadnice
     * @param int $y Počáteční Y souřadnice
     * @param int $width Šířka ořezu
     * @param int $height Výška ořezu
     * @param string $format Výstupní formát
     * @param int $quality Kvalita
     * @return array{data: array<string, mixed>}
     */
    public function crop(
        string $imageSource,
        int $x,
        int $y,
        int $width,
        int $height,
        string $format = 'jpg',
        int $quality = 85
    ): array {
        $this->checkGdSupport();

        $image = $this->loadImage($imageSource);
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        if ($x < 0 || $y < 0 || $x + $width > $originalWidth || $y + $height > $originalHeight) {
            throw new \RuntimeException('Ořezové souřadnice jsou mimo rozsah obrázku');
        }

        $cropped = imagecrop($image, [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
        ]);

        imagedestroy($image);

        if ($cropped === false) {
            throw new \RuntimeException('Nepodařilo se oříznout obrázek');
        }

        $output = $this->imageToBase64($cropped, $format, $quality);
        imagedestroy($cropped);

        return [
            'data' => [
                'image' => $output,
                'format' => $format,
                'width' => $width,
                'height' => $height,
                'crop_x' => $x,
                'crop_y' => $y,
            ],
        ];
    }

    /**
     * Otočí obrázek
     *
     * @param string $imageSource URL nebo base64 obrázku
     * @param int $degrees Úhel otočení (90, 180, 270)
     * @param string $format Výstupní formát
     * @param int $quality Kvalita
     * @return array{data: array<string, mixed>}
     */
    public function rotate(
        string $imageSource,
        int $degrees,
        string $format = 'jpg',
        int $quality = 85
    ): array {
        $this->checkGdSupport();

        if (!in_array($degrees, [90, 180, 270, -90, -180, -270], true)) {
            throw new \RuntimeException('Úhel otočení musí být 90, 180 nebo 270 stupňů');
        }

        $image = $this->loadImage($imageSource);
        $rotated = imagerotate($image, -$degrees, 0);
        imagedestroy($image);

        if ($rotated === false) {
            throw new \RuntimeException('Nepodařilo se otočit obrázek');
        }

        $output = $this->imageToBase64($rotated, $format, $quality);
        $width = imagesx($rotated);
        $height = imagesy($rotated);
        imagedestroy($rotated);

        return [
            'data' => [
                'image' => $output,
                'format' => $format,
                'width' => $width,
                'height' => $height,
                'rotation' => $degrees,
            ],
        ];
    }

    /**
     * Převrátí obrázek (flip)
     *
     * @param string $imageSource URL nebo base64 obrázku
     * @param string $mode horizontal nebo vertical
     * @param string $format Výstupní formát
     * @param int $quality Kvalita
     * @return array{data: array<string, mixed>}
     */
    public function flip(
        string $imageSource,
        string $mode,
        string $format = 'jpg',
        int $quality = 85
    ): array {
        $this->checkGdSupport();

        $flipMode = match ($mode) {
            'horizontal' => IMG_FLIP_HORIZONTAL,
            'vertical' => IMG_FLIP_VERTICAL,
            'both' => IMG_FLIP_BOTH,
            default => throw new \RuntimeException('Neplatný režim: horizontal, vertical nebo both'),
        };

        $image = $this->loadImage($imageSource);
        imageflip($image, $flipMode);

        $output = $this->imageToBase64($image, $format, $quality);
        $width = imagesx($image);
        $height = imagesy($image);
        imagedestroy($image);

        return [
            'data' => [
                'image' => $output,
                'format' => $format,
                'width' => $width,
                'height' => $height,
                'flip_mode' => $mode,
            ],
        ];
    }

    /**
     * Převede obrázek na jiný formát
     *
     * @param string $imageSource URL nebo base64 obrázku
     * @param string $format Cílový formát (jpg, png, webp, gif)
     * @param int $quality Kvalita (1-100 pro jpg/webp)
     * @return array{data: array<string, mixed>}
     */
    public function convert(string $imageSource, string $format, int $quality = 85): array
    {
        $this->checkGdSupport();

        $image = $this->loadImage($imageSource);
        $output = $this->imageToBase64($image, $format, $quality);
        $width = imagesx($image);
        $height = imagesy($image);
        imagedestroy($image);

        return [
            'data' => [
                'image' => $output,
                'format' => $format,
                'width' => $width,
                'height' => $height,
                'quality' => $quality,
            ],
        ];
    }

    /**
     * Přidá textový vodoznak
     *
     * @param string $imageSource URL nebo base64 obrázku
     * @param string $text Text vodoznaku
     * @param string $position Pozice: topleft, topright, bottomleft, bottomright, center
     * @param int $fontSize Velikost písma (1-5)
     * @param string $format Výstupní formát
     * @param int $quality Kvalita
     * @return array{data: array<string, mixed>}
     */
    public function watermark(
        string $imageSource,
        string $text,
        string $position = 'bottomright',
        int $fontSize = 3,
        string $format = 'jpg',
        int $quality = 85
    ): array {
        $this->checkGdSupport();

        if ($fontSize < 1 || $fontSize > 5) {
            throw new \RuntimeException('Velikost písma musí být mezi 1 a 5');
        }

        $image = $this->loadImage($imageSource);
        $width = imagesx($image);
        $height = imagesy($image);

        // Výpočet pozice textu
        $fontWidth = imagefontwidth($fontSize);
        $fontHeight = imagefontheight($fontSize);
        $textWidth = $fontWidth * strlen($text);
        $textHeight = $fontHeight;

        $padding = 10;

        [$x, $y] = match ($position) {
            'topleft' => [$padding, $padding],
            'topright' => [$width - $textWidth - $padding, $padding],
            'bottomleft' => [$padding, $height - $textHeight - $padding],
            'bottomright' => [$width - $textWidth - $padding, $height - $textHeight - $padding],
            'center' => [($width - $textWidth) / 2, ($height - $textHeight) / 2],
            default => throw new \RuntimeException('Neplatná pozice'),
        };

        // Černý text s bílým stínem pro čitelnost
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        if ($white === false || $black === false) {
            throw new \RuntimeException('Nepodařilo se alokovat barvy');
        }

        // Stín
        imagestring($image, $fontSize, (int) $x + 1, (int) $y + 1, $text, $white);
        // Text
        imagestring($image, $fontSize, (int) $x, (int) $y, $text, $black);

        $output = $this->imageToBase64($image, $format, $quality);
        imagedestroy($image);

        return [
            'data' => [
                'image' => $output,
                'format' => $format,
                'width' => $width,
                'height' => $height,
                'watermark' => $text,
                'position' => $position,
            ],
        ];
    }

    /**
     * Získá informace o obrázku
     *
     * @param string $imageSource URL nebo base64 obrázku
     * @return array{data: array<string, mixed>}
     */
    public function getInfo(string $imageSource): array
    {
        $this->checkGdSupport();

        $imageData = $this->getImageData($imageSource);
        $size = strlen($imageData);

        $image = @imagecreatefromstring($imageData);
        if ($image === false) {
            throw new \RuntimeException('Nepodařilo se načíst obrázek');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        imagedestroy($image);

        // Detekce typu obrázku
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new \RuntimeException('Nepodařilo se inicializovat finfo');
        }

        $mimeType = finfo_buffer($finfo, $imageData);
        finfo_close($finfo);

        $type = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'unknown',
        };

        return [
            'data' => [
                'width' => $width,
                'height' => $height,
                'type' => $type,
                'mime_type' => $mimeType,
                'size_bytes' => $size,
                'size_kb' => round($size / 1024, 2),
                'aspect_ratio' => round($width / $height, 2),
            ],
        ];
    }

    /**
     * Načte obrázek z URL nebo base64
     *
     * @return \GdImage
     */
    private function loadImage(string $source): \GdImage
    {
        $imageData = $this->getImageData($source);
        $image = @imagecreatefromstring($imageData);

        if ($image === false) {
            throw new \RuntimeException('Nepodařilo se načíst obrázek. Zkontrolujte formát.');
        }

        return $image;
    }

    /**
     * Získá binární data obrázku z URL nebo base64
     */
    private function getImageData(string $source): string
    {
        // Base64?
        if (preg_match('/^data:image\/[^;]+;base64,(.+)$/', $source, $matches)) {
            $imageData = base64_decode($matches[1], true);
            if ($imageData === false) {
                throw new \RuntimeException('Neplatná base64 data');
            }
            return $imageData;
        }

        // Přímá base64 bez data URI?
        if (preg_match('/^[A-Za-z0-9+\/]+=*$/', $source)) {
            $imageData = base64_decode($source, true);
            if ($imageData !== false) {
                return $imageData;
            }
        }

        // URL
        if (filter_var($source, FILTER_VALIDATE_URL)) {
            try {
                $imageData = $this->httpClient->get($source);

                if (strlen($imageData) > self::MAX_IMAGE_SIZE) {
                    throw new \RuntimeException('Obrázek je příliš velký (max ' . (self::MAX_IMAGE_SIZE / 1024 / 1024) . ' MB)');
                }

                return $imageData;
            } catch (\Exception $e) {
                throw new \RuntimeException("Nepodařilo se stáhnout obrázek: {$e->getMessage()}", 0, $e);
            }
        }

        throw new \RuntimeException('Neplatný zdroj obrázku (použijte URL nebo base64)');
    }

    /**
     * Převede GdImage na base64 data URI
     */
    private function imageToBase64(\GdImage $image, string $format, int $quality = 85): string
    {
        $format = strtolower($format);

        if ($quality < 1 || $quality > 100) {
            throw new \RuntimeException('Kvalita musí být mezi 1 a 100');
        }

        ob_start();

        $success = match ($format) {
            'jpg', 'jpeg' => imagejpeg($image, null, $quality),
            'png' => imagepng($image, null, (int) ((100 - $quality) / 11)),
            'gif' => imagegif($image),
            'webp' => function_exists('imagewebp') ? imagewebp($image, null, $quality) : throw new \RuntimeException('WebP není podporován'),
            default => throw new \RuntimeException("Nepodporovaný formát: {$format}"),
        };

        $imageData = ob_get_clean();

        if (!$success || $imageData === false) {
            throw new \RuntimeException('Nepodařilo se zakódovat obrázek');
        }

        $mimeType = match ($format) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
    }

    /**
     * Zkontroluje, zda je GD knihovna dostupná
     */
    private function checkGdSupport(): void
    {
        if (!extension_loaded('gd')) {
            throw new \RuntimeException('PHP GD extension není nainstalována');
        }
    }
}
