<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\ServerRequest;
use Mcp\Server\Transport\Http\Middleware\CorsMiddleware;
use Mcp\Server\Transport\StatelessHttpTransport;
use ZetaComicGenerator\Mcp\ComicMcp;
use ZetaComicGenerator\Mcp\DraftRepository;
use ZetaComicGenerator\Mcp\McpServerFactory;
use ZetaComicGenerator\Mcp\WebsiteApiClient;

ini_set('display_errors', '0');
error_reporting(E_ALL);

$projectRoot = dirname(__DIR__);

try {
    require $projectRoot.'/vendor/autoload.php';
    require $projectRoot.'/api/includes/key.php';
    require $projectRoot.'/api/includes/characteractions.php';
    require __DIR__.'/src/WebsiteApiClient.php';
    require __DIR__.'/src/DraftRepositoryInterface.php';
    require __DIR__.'/src/DraftRepository.php';
    require __DIR__.'/src/ComicMcp.php';
    require __DIR__.'/src/McpServerFactory.php';

    $configuredBaseUrl = defined('MCP_SITE_BASE_URL') ? MCP_SITE_BASE_URL : getenv('MCP_SITE_BASE_URL');
    $siteBaseUrl = rtrim(is_string($configuredBaseUrl) && '' !== $configuredBaseUrl
        ? $configuredBaseUrl
        : 'https://comicgenerator.greenzeta.com', '/');

    $db = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    );

    $comicMcp = new ComicMcp(
        new DraftRepository($db),
        new WebsiteApiClient($siteBaseUrl),
        $siteBaseUrl,
        $projectRoot,
    );

    $protocol = McpServerFactory::build($comicMcp, $siteBaseUrl);
    $transport = new StatelessHttpTransport(
        $protocol,
        middleware: [new CorsMiddleware(['*'])],
    );
    $response = $transport->handle(ServerRequest::fromGlobals());

    http_response_code($response->getStatusCode());
    foreach ($response->getHeaders() as $name => $values) {
        foreach ($values as $value) {
            header($name.': '.$value, false);
        }
    }
    echo $response->getBody();
} catch (Throwable $error) {
    error_log('Zeta Comic Generator MCP error: '.$error->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'jsonrpc' => '2.0',
        'error' => ['code' => -32603, 'message' => 'The MCP server is temporarily unavailable.'],
        'id' => null,
    ]);
}
