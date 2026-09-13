<?php

declare(strict_types=1);

namespace ZetaComicGenerator\Mcp;

use RuntimeException;

interface WebsiteApiClientInterface
{
    /**
     * Reads the website generation allowance and usage response.
     *
     * @return array<string, mixed> Decoded metrics API response.
     */
    public function metrics(): array;

    /**
     * Submits a completed comic to the existing website save API.
     *
     * @param array<string, string> $payload Website save form fields.
     * @return array<string, mixed> Decoded save API response.
     */
    public function save(array $payload): array;
}

final class WebsiteApiClient implements WebsiteApiClientInterface
{
    /**
     * Sets the website origin used by the server-side API client.
     *
     * @param string $siteBaseUrl Website base URL.
     */
    public function __construct(private readonly string $siteBaseUrl)
    {
    }

    /**
     * Reads the website generation allowance and usage response.
     *
     * @return array<string, mixed> Decoded metrics API response.
     */
    public function metrics(): array
    {
        return $this->request('metrics', []);
    }

    /**
     * Submits a completed comic to the existing website save API.
     *
     * @param array<string, string> $payload Website save form fields.
     * @return array<string, mixed> Decoded save API response.
     */
    public function save(array $payload): array
    {
        return $this->request('save', $payload, 180);
    }

    /**
     * Posts URL-encoded fields to a website API endpoint and decodes its response.
     *
     * @param string $action Endpoint name beneath /api/.
     * @param array<string, string> $payload Form fields to submit.
     * @param int $timeout Maximum request duration in seconds; defaults to 20.
     * @return array<string, mixed> Decoded JSON response.
     * @throws RuntimeException If transport, HTTP status, or response decoding fails.
     */
    private function request(string $action, array $payload, int $timeout = 20): array
    {
        $url = rtrim($this->siteBaseUrl, '/').'/api/'.$action.'/';
        $handle = curl_init($url);
        if (false === $handle) {
            throw new RuntimeException('Unable to initialize the website API request.');
        }

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => 'ZetaComicGenerator-MCP/1.0',
        ]);

        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if (false === $body || '' !== $error) {
            throw new RuntimeException('The website API is temporarily unavailable.');
        }
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('The website API returned HTTP '.$status.'.');
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('The website API returned an invalid response.');
        }

        return $decoded;
    }
}
