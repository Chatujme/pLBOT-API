<?php

declare(strict_types=1);

namespace App\Services;

use Nette\Utils\Json;
use Nette\Utils\JsonException;

/**
 * Service pro HTTP požadavky
 */
final class HttpClientService
{
    private const DEFAULT_TIMEOUT = 30;
    private const DEFAULT_USER_AGENT = 'pLBOT-API/2.0';

    /**
     * Provede GET požadavek a vrátí obsah
     *
     * @throws \RuntimeException
     */
    public function get(string $url, array $options = []): string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException("Failed to initialize cURL");
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $options['timeout'] ?? self::DEFAULT_TIMEOUT,
            CURLOPT_USERAGENT => $options['user_agent'] ?? self::DEFAULT_USER_AGENT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        if (isset($options['headers'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $options['headers']);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException("cURL request failed: {$error}");
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException("HTTP request failed with code {$httpCode}");
        }

        return $response;
    }

    /**
     * Provede GET požadavek a vrátí dekódovaný JSON
     *
     * @return array<mixed>|object
     * @throws \RuntimeException
     * @throws JsonException
     */
    public function getJson(string $url, array $options = []): array|object
    {
        $content = $this->get($url, $options);

        try {
            return Json::decode($content, forceArrays: true);
        } catch (JsonException $e) {
            throw new \RuntimeException("Failed to decode JSON response: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Provede POST požadavek
     *
     * @param array<mixed>|string $data
     * @throws \RuntimeException
     */
    public function post(string $url, array|string $data, array $options = []): string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException("Failed to initialize cURL");
        }

        $postData = is_array($data) ? http_build_query($data) : $data;

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $options['timeout'] ?? self::DEFAULT_TIMEOUT,
            CURLOPT_USERAGENT => $options['user_agent'] ?? self::DEFAULT_USER_AGENT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        if (isset($options['headers'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $options['headers']);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException("cURL request failed: {$error}");
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException("HTTP request failed with code {$httpCode}");
        }

        return $response;
    }

    /**
     * Provede POST požadavek s JSON daty a vrátí dekódovaný JSON
     *
     * @param array<mixed> $data
     * @return array<mixed>|object
     * @throws \RuntimeException
     * @throws JsonException
     */
    public function postJson(string $url, array $data, array $options = []): array|object
    {
        $jsonData = Json::encode($data);

        $headers = $options['headers'] ?? [];
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/json';

        $options['headers'] = $headers;

        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException("Failed to initialize cURL");
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $options['timeout'] ?? self::DEFAULT_TIMEOUT,
            CURLOPT_USERAGENT => $options['user_agent'] ?? self::DEFAULT_USER_AGENT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => $options['headers'],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException("cURL request failed: {$error}");
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException("HTTP request failed with code {$httpCode}");
        }

        try {
            return Json::decode($response, forceArrays: true);
        } catch (JsonException $e) {
            throw new \RuntimeException("Failed to decode JSON response: {$e->getMessage()}", 0, $e);
        }
    }
}
