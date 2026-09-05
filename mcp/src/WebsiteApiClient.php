<?php

declare(strict_types=1);

namespace ZetaComicGenerator\Mcp;

use RuntimeException;

interface WebsiteApiClientInterface
{
    /** @return array<string, mixed> */
    public function metrics(): array;

    /** @param array<string, string> $payload
     *  @return array<string, mixed>
     */
    public function save(array $payload): array;
}

final class WebsiteApiClient implements WebsiteApiClientInterface
{
    public function __construct(private readonly string $siteBaseUrl)
    {
    }

    public function metrics(): array
    {
        return $this->request('metrics', []);
    }

    public function save(array $payload): array
    {
        return $this->request('save', $payload, 180);
    }

    /** @param array<string, string> $payload
     *  @return array<string, mixed>
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
