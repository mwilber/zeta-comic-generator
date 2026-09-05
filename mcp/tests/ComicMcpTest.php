<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\ServerRequest;
use Mcp\Schema\Wire\McpHeader;
use Mcp\Server\Transport\StatelessHttpTransport;
use ZetaComicGenerator\Mcp\ComicMcp;
use ZetaComicGenerator\Mcp\DraftRepositoryInterface;
use ZetaComicGenerator\Mcp\McpServerFactory;
use ZetaComicGenerator\Mcp\WebsiteApiClientInterface;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
require $root.'/mcp/src/WebsiteApiClient.php';
require $root.'/mcp/src/DraftRepositoryInterface.php';
require $root.'/mcp/src/ComicMcp.php';
require $root.'/mcp/src/McpServerFactory.php';
require $root.'/api/includes/characteractions.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

final class MemoryDrafts implements DraftRepositoryInterface
{
    /** @var array<string, array<string, mixed>> */
    public array $drafts = [];

    public function createPrepared(string $premise, string $workflow): string
    {
        $id = str_repeat('a', 32);
        $this->drafts[$id] = [
            'draft_id' => $id,
            'premise' => $premise,
            'workflow' => $workflow,
            'status' => 'prepared',
            'save_payload' => null,
            'comic_id' => null,
            'permalink' => null,
        ];
        return $id;
    }

    public function findActive(string $draftId): ?array
    {
        return $this->drafts[$draftId] ?? null;
    }

    public function beginGeneration(string $draftId): array
    {
        $draft = $this->drafts[$draftId] ?? throw new RuntimeException('Missing draft.');
        $this->drafts[$draftId]['status'] = 'generating';
        return $draft;
    }

    public function stage(string $draftId, array $savePayload): void
    {
        $this->drafts[$draftId]['save_payload'] = json_encode($savePayload, JSON_THROW_ON_ERROR);
        $this->drafts[$draftId]['status'] = 'ready';
    }

    public function reserveSave(string $draftId): array
    {
        $draft = $this->drafts[$draftId];
        if ('saved' === $draft['status']) {
            return ['state' => 'saved', 'draft' => $draft];
        }
        $this->drafts[$draftId]['status'] = 'saving';
        return ['state' => 'reserved', 'draft' => $draft];
    }

    public function completeSave(string $draftId, string $comicId, string $permalink): void
    {
        $this->drafts[$draftId]['status'] = 'saved';
        $this->drafts[$draftId]['comic_id'] = $comicId;
        $this->drafts[$draftId]['permalink'] = $permalink;
    }

    public function releaseSave(string $draftId, string $message): void
    {
        $this->drafts[$draftId]['status'] = 'ready';
    }
}

final class FakeWebsiteApi implements WebsiteApiClientInterface
{
    public bool $limitReached = false;
    public bool $validMetrics = true;
    public int $saveCalls = 0;

    public function metrics(): array
    {
        return $this->validMetrics ? ['json' => ['count' => 1, 'limitreached' => $this->limitReached]] : [];
    }

    public function save(array $payload): array
    {
        ++$this->saveCalls;
        return ['error' => '', 'response' => ['comicId' => 42, 'permalink' => md5('42')]];
    }
}

/** @return array<string, mixed> */
function protocolRequest(object $protocol, string $method, array $params = [], ?string $name = null): array
{
    $params['_meta'] = [
        'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
        'io.modelcontextprotocol/clientCapabilities' => [
            'extensions' => ['io.modelcontextprotocol/ui' => new stdClass()],
        ],
        'io.modelcontextprotocol/clientInfo' => ['name' => 'mcp-test', 'version' => '1.0.0'],
    ];
    $body = json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params], JSON_THROW_ON_ERROR);
    $headers = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'MCP-Protocol-Version' => '2026-07-28',
        'Mcp-Method' => $method,
    ];
    if (null !== $name) {
        $headers['Mcp-Name'] = McpHeader::encode($name);
    }
    $transport = new StatelessHttpTransport($protocol, middleware: []);
    $response = $transport->handle(new ServerRequest('POST', 'https://example.test/mcp', $headers, $body));
    expect(200 === $response->getStatusCode(), 'Expected a successful protocol response, got '.$response->getStatusCode().'.');
    $decoded = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    expect(is_array($decoded), 'Protocol response was not an object.');
    return $decoded;
}

$drafts = new MemoryDrafts();
$api = new FakeWebsiteApi();
$comicMcp = new ComicMcp($drafts, $api, 'https://comicgenerator.greenzeta.com', $root);

$api->limitReached = true;
$limited = $comicMcp->prepareComicGeneration('A comic about tests');
expect($limited->isError, 'Rate-limited preparation must fail.');
expect(false === $limited->structuredContent['available'], 'Rate-limited preparation must report unavailable.');

$api->limitReached = false;
$api->validMetrics = false;
$unknown = $comicMcp->prepareComicGeneration('A comic about tests');
expect($unknown->isError, 'Unverifiable metrics must fail closed.');

$api->validMetrics = true;
$prepared = $comicMcp->prepareComicGeneration('A comic about tests', 'google');
expect(!$prepared->isError && [] === $drafts->drafts, 'Preparation should be read-only.');

$generated = $comicMcp->generateComic('A comic about tests', 'google');
$draftId = $generated->structuredContent['generation_id'];
expect(!$generated->isError && 'google' === $generated->structuredContent['workflow'], 'Generation did not return app input.');

$script = json_encode([
    'title' => 'Test Comic',
    'panels' => [
        ['scene' => 'One', 'dialog' => [], 'images' => []],
        ['scene' => 'Two', 'dialog' => [], 'images' => []],
        ['scene' => 'Three', 'dialog' => [], 'images' => []],
    ],
], JSON_THROW_ON_ERROR);
$payload = [
    'prompt' => 'A comic about tests',
    'title' => 'Test Comic',
    'script' => $script,
    'summary' => '',
    'seriesId' => '',
    'continuity' => '{}',
    'memory' => '[]',
    'bkg1' => '/assets/backgrounds-full/one.png',
    'bkg2' => '/assets/backgrounds-full/two.png',
    'bkg3' => '/assets/backgrounds-full/three.png',
    'fg1' => 'standing.png',
    'fg2' => 'thinking.png',
    'fg3' => 'joyous.png',
];
$staged = $comicMcp->stageComic($draftId, $payload);
expect(!$staged->isError && 'ready' === $staged->structuredContent['status'], 'Completed comic was not staged.');

$saved = $comicMcp->saveComic($draftId);
expect(!$saved->isError && true === $saved->structuredContent['saved'], 'Staged comic was not saved.');
expect(1 === $api->saveCalls, 'Website save API should be called exactly once.');
$savedAgain = $comicMcp->saveComic($draftId);
expect(!$savedAgain->isError && 1 === $api->saveCalls, 'Repeated save must be idempotent.');

$protocol = McpServerFactory::build($comicMcp, 'https://comicgenerator.greenzeta.com');
$discovery = protocolRequest($protocol, 'server/discover');
expect(isset($discovery['result']['capabilities']['extensions']['io.modelcontextprotocol/ui']), 'MCP Apps extension is missing from discovery.');
$tools = protocolRequest($protocol, 'tools/list');
$toolNames = array_column($tools['result']['tools'] ?? [], 'name');
expect(in_array('prepare_comic_generation', $toolNames, true), 'Prepare tool is missing.');
expect(in_array('generate_comic', $toolNames, true), 'Generate tool is missing.');
expect(in_array('save_comic', $toolNames, true), 'Save tool is missing.');
$generateTool = null;
$stageTool = null;
foreach ($tools['result']['tools'] ?? [] as $tool) {
    if (($tool['name'] ?? null) === 'generate_comic') {
        $generateTool = $tool;
    }
    if (($tool['name'] ?? null) === 'stage_comic') {
        $stageTool = $tool;
    }
}
expect(ComicMcp::APP_URI === ($generateTool['_meta']['ui']['resourceUri'] ?? null), 'Generate tool is not linked to the comic app.');
expect(['app'] === ($stageTool['_meta']['ui']['visibility'] ?? null), 'Staging tool is not marked app-only.');

$resource = protocolRequest($protocol, 'resources/read', ['uri' => ComicMcp::APP_URI], ComicMcp::APP_URI);
$resourceContent = $resource['result']['contents'][0] ?? [];
expect('text/html;profile=mcp-app' === ($resourceContent['mimeType'] ?? null), 'App resource has the wrong MIME type.');
expect(str_contains($resourceContent['text'] ?? '', '/mcp/app.js?v=1.0.0'), 'App resource does not load its UI script.');
expect(!str_contains($resourceContent['text'] ?? '', '<nav'), 'App resource unexpectedly contains website navigation.');

$protocolPrepare = protocolRequest(
    $protocol,
    'tools/call',
    ['name' => 'prepare_comic_generation', 'arguments' => ['premise' => 'Protocol test', 'workflow' => 'openai']],
    'prepare_comic_generation',
);
expect(true === ($protocolPrepare['result']['structuredContent']['available'] ?? false), 'Prepare tool failed through the 2026-07-28 protocol.');

echo "ComicMcp tests passed.\n";
