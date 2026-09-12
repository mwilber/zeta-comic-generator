<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\ServerRequest;
use Mcp\Server;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Schema\Wire\McpHeader;
use Mcp\Server\Transport\StreamableHttpTransport;
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

/**
 * Fails the PHP test script when an assertion is false.
 *
 * @param bool $condition Assertion result.
 * @param string $message Failure explanation.
 * @return void
 * @throws RuntimeException If the assertion fails.
 */
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

    /**
     * Creates a deterministic in-memory draft fixture.
     *
     * @param string $premise Test comic premise.
     * @param string $workflow Test generation provider.
     * @return string The fixed test draft identifier.
     */
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

    /**
     * Looks up a draft fixture without simulating expiration.
     *
     * @param string $draftId Test draft identifier.
     * @return array<string, mixed>|null Stored fixture, or null when absent.
     */
    public function findActive(string $draftId): ?array
    {
        return $this->drafts[$draftId] ?? null;
    }

    /**
     * Marks a test draft as generating and returns its prior state.
     *
     * @param string $draftId Test draft identifier.
     * @return array<string, mixed> The draft before mutation.
     * @throws RuntimeException If the fixture is missing.
     */
    public function beginGeneration(string $draftId): array
    {
        $draft = $this->drafts[$draftId] ?? throw new RuntimeException('Missing draft.');
        if ('prepared' !== $draft['status']) throw new RuntimeException('Already started.');
        $this->drafts[$draftId]['status'] = 'generating';
        return $draft;
    }

    /**
     * Stores serialized save fields and marks the test draft ready.
     *
     * @param string $draftId Test draft identifier.
     * @param array<string, string> $savePayload Test save fields.
     * @return void
     */
    public function stage(string $draftId, array $savePayload): void
    {
        $this->drafts[$draftId]['save_payload'] = json_encode($savePayload, JSON_THROW_ON_ERROR);
        $this->drafts[$draftId]['status'] = 'ready';
    }

    /**
     * Simulates a save reservation or an already saved test result.
     *
     * @param string $draftId Test draft identifier.
     * @return array{state: string, draft: array<string, mixed>} Simulated reservation.
     */
    public function reserveSave(string $draftId): array
    {
        $draft = $this->drafts[$draftId];
        if ('saved' === $draft['status']) {
            return ['state' => 'saved', 'draft' => $draft];
        }
        $this->drafts[$draftId]['status'] = 'saving';
        return ['state' => 'reserved', 'draft' => $draft];
    }

    /**
     * Records the saved state and comic identifiers in the test fixture.
     *
     * @param string $draftId Test draft identifier.
     * @param string $comicId Saved comic identifier.
     * @param string $permalink Test comic link token.
     * @return void
     */
    public function completeSave(string $draftId, string $comicId, string $permalink): void
    {
        $this->drafts[$draftId]['status'] = 'saved';
        $this->drafts[$draftId]['comic_id'] = $comicId;
        $this->drafts[$draftId]['permalink'] = $permalink;
    }

    /**
     * Resets the test draft to ready without retaining the failure message.
     *
     * @param string $draftId Test draft identifier.
     * @param string $message Failure text; intentionally unused by this test double.
     * @return void
     */
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

    /**
     * Returns valid, limited, or malformed metrics according to test flags.
     *
     * @return array<string, mixed> Simulated metrics response.
     */
    public function metrics(): array
    {
        return $this->validMetrics ? ['json' => ['count' => 1, 'limitreached' => $this->limitReached]] : [];
    }

    /**
     * Counts save requests and returns a deterministic successful comic result.
     *
     * @param array<string, string> $payload Save fields; not inspected by this test double.
     * @return array<string, mixed> Simulated successful save response.
     */
    public function save(array $payload): array
    {
        ++$this->saveCalls;
        return ['error' => '', 'response' => ['comicId' => 42, 'permalink' => md5('42')]];
    }
}

/**
 * Runs an HTTP request through the test server without transport middleware.
 *
 * @param Server $server MCP server under test.
 * @param ServerRequest $request HTTP request to handle.
 * @return \Psr\Http\Message\ResponseInterface The transport response.
 */
function sendHttpRequest(Server $server, ServerRequest $request): \Psr\Http\Message\ResponseInterface
{
    $transport = new StreamableHttpTransport($request, middleware: []);
    return $server->run($transport);
}

/**
 * Sends a stateless MCP request with app capabilities and checks its response.
 *
 * @param Server $server MCP server under test.
 * @param string $method JSON-RPC method name.
 * @param array<string, mixed> $params Method parameters before protocol metadata is added.
 * @param string|null $name Optional value for the Mcp-Name header.
 * @return array<string, mixed> Decoded JSON-RPC response.
 * @throws RuntimeException If the response status or shape fails an assertion.
 */
function protocolRequest(Server $server, string $method, array $params = [], ?string $name = null): array
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
    $response = sendHttpRequest($server, new ServerRequest('POST', 'https://example.test/mcp', $headers, $body));
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
expect($draftId === $generated->structuredContent['draft_id'], 'Generation must expose the same save draft ID before app completion.');
expect(str_contains($generated->content[0]->text, 'draft_id='.$draftId), 'Generation text must retain the save ID for hosts without app context support.');
expect(!$generated->isError && 'google' === $generated->structuredContent['workflow'], 'Generation did not return app input.');

expect('prepared' === $drafts->drafts[$draftId]['status'], 'Tool result creation must leave the app unclaimed.');
expect(false === $comicMcp->comicAppState($draftId)->structuredContent['generate'], 'Default state lookup must not start generation.');
$firstOpen = $comicMcp->comicAppState($draftId, true);
expect(true === $firstOpen->structuredContent['generate'], 'First app render must claim generation.');
expect('google' === $firstOpen->structuredContent['workflow'], 'Claim must use the stored workflow.');
expect(false === $comicMcp->comicAppState($draftId, true)->structuredContent['generate'], 'Reload during generation must stay empty.');
expect(false === $comicMcp->comicAppState(str_repeat('b', 32), true)->structuredContent['generate'], 'Missing or expired drafts must not regenerate.');

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

expect(false === $comicMcp->comicAppState($draftId, true)->structuredContent['generate'], 'Unsaved completed comics must not regenerate.');

$saved = $comicMcp->saveComic($draftId);
expect(!$saved->isError && true === $saved->structuredContent['saved'], 'Staged comic was not saved.');
expect(1 === $api->saveCalls, 'Website save API should be called exactly once.');
$savedAgain = $comicMcp->saveComic($draftId);
expect(!$savedAgain->isError && 1 === $api->saveCalls, 'Repeated save must be idempotent.');
$expectedUrl = 'https://comicgenerator.greenzeta.com/detail/'.md5('42');
$expectedLink = '[View your saved comic]('.$expectedUrl.')';
foreach ([$saved, $savedAgain] as $saveResult) {
    expect($expectedUrl === $saveResult->structuredContent['url'], 'Save must return the permanent page URL.');
    expect($expectedLink === $saveResult->structuredContent['comic_link'], 'Save must return a ready-to-present Markdown link.');
    expect(str_contains($saveResult->content[0]->text, $expectedLink), 'Save text must include the clickable page link.');
    expect(!array_key_exists('comic_id', $saveResult->structuredContent), 'Save must not expose the database ID to the model.');
    expect(!array_key_exists('permalink', $saveResult->structuredContent), 'Save must not expose a standalone permalink token.');
}
$restored = $comicMcp->comicAppState($draftId, true)->structuredContent;
expect(false === $restored['generate'] && md5('42') === $restored['permalink'], 'Saved app must restore its permanent permalink without generation.');
expect('42' === $drafts->drafts[$draftId]['comic_id'], 'The database ID must still be retained internally.');


$sessionStore = new InMemorySessionStore();
$server = McpServerFactory::build(
    $comicMcp,
    'https://comicgenerator.greenzeta.com',
    $sessionStore,
);
$discovery = protocolRequest($server, 'server/discover');
expect(isset($discovery['result']['capabilities']['extensions']['io.modelcontextprotocol/ui']), 'MCP Apps extension is missing from discovery.');
$tools = protocolRequest($server, 'tools/list');
$toolNames = array_column($tools['result']['tools'] ?? [], 'name');
expect(in_array('prepare_comic_generation', $toolNames, true), 'Prepare tool is missing.');
expect(in_array('generate_comic', $toolNames, true), 'Generate tool is missing.');
expect(in_array('save_comic', $toolNames, true), 'Save tool is missing.');
$generateTool = null;
$stageTool = null;
$stateTool = null;
foreach ($tools['result']['tools'] ?? [] as $tool) {
    if (($tool['name'] ?? null) === 'generate_comic') {
        $generateTool = $tool;
    }
    if (($tool['name'] ?? null) === 'comic_app_state') $stateTool = $tool;
    if (($tool['name'] ?? null) === 'stage_comic') {
        $stageTool = $tool;
    }
}
expect(ComicMcp::APP_URI === ($generateTool['_meta']['ui']['resourceUri'] ?? null), 'Generate tool is not linked to the comic app.');
expect(['app'] === ($stateTool['_meta']['ui']['visibility'] ?? null), 'App state tool must be app-only.');
expect(['app'] === ($stageTool['_meta']['ui']['visibility'] ?? null), 'Staging tool is not marked app-only.');

$resource = protocolRequest($server, 'resources/read', ['uri' => ComicMcp::APP_URI], ComicMcp::APP_URI);
$resourceContent = $resource['result']['contents'][0] ?? [];
expect(!array_key_exists('domain', $resourceContent['_meta']['ui'] ?? []), 'App must let the host choose its sandbox domain.');
expect('text/html;profile=mcp-app' === ($resourceContent['mimeType'] ?? null), 'App resource has the wrong MIME type.');
$appHtml = $resourceContent['text'] ?? '';
expect(!str_contains($appHtml, '<base '), 'App must not rely on a base tag blocked by host CSP.');
expect(str_contains($appHtml, 'class ComicGeneratorApi'), 'App resource does not contain the bundled API client.');
expect(str_contains($appHtml, 'class ComicRenderer'), 'App resource does not contain the bundled comic renderer.');
expect(str_contains($appHtml, 'class GenerationProgressDialog'), 'App resource does not reuse the shared progress controller.');
expect(str_contains($appHtml, 'class McpGenerationProgress'), 'App resource does not include the progress adapter.');
expect(str_contains($appHtml, 'id="statusdialog"'), 'App resource does not contain the progress dialog.');
foreach (['styles/dialog.css', 'styles/generation-progress.css', 'mcp/progress.css'] as $stylesheet) {
    expect(str_contains($appHtml, file_get_contents($root.'/'.$stylesheet)), 'App resource is missing styles from '.$stylesheet.'.');
}
expect(str_contains($appHtml, 'async function generateComic'), 'App resource does not contain its UI controller.');
expect(!preg_match('/<script[^>]+src=/i', $appHtml), 'App resource loads a cross-origin script.');
expect(!preg_match('/<link[^>]+stylesheet/i', $appHtml), 'App resource loads a cross-origin stylesheet.');
expect(!preg_match('/^\s*(?:import|export)\b/m', $appHtml), 'App resource contains an unresolved module statement.');
expect(!str_contains($appHtml, '{{'), 'App resource contains an unresolved template placeholder.');
expect(!str_contains($appHtml, '<nav'), 'App resource unexpectedly contains website navigation.');

$protocolPrepare = protocolRequest(
    $server,
    'tools/call',
    ['name' => 'prepare_comic_generation', 'arguments' => ['premise' => 'Protocol test', 'workflow' => 'openai']],
    'prepare_comic_generation',
);
expect(true === ($protocolPrepare['result']['structuredContent']['available'] ?? false), 'Prepare tool failed through the 2026-07-28 protocol.');

$legacyInitializeBody = json_encode([
    'jsonrpc' => '2.0',
    'id' => 2,
    'method' => 'initialize',
    'params' => [
        'protocolVersion' => '2025-06-18',
        'capabilities' => ['extensions' => ['io.modelcontextprotocol/ui' => new stdClass()]],
        'clientInfo' => ['name' => 'mcpjam-test', 'version' => '1.0.0'],
    ],
], JSON_THROW_ON_ERROR);
$legacyInitialize = sendHttpRequest($server, new ServerRequest(
    'POST',
    'https://example.test/mcp',
    ['Content-Type' => 'application/json', 'Accept' => 'application/json, text/event-stream'],
    $legacyInitializeBody,
));
expect(200 === $legacyInitialize->getStatusCode(), 'Handshake-era initialize request failed.');
$legacySessionId = $legacyInitialize->getHeaderLine('Mcp-Session-Id');
expect('' !== $legacySessionId, 'Handshake-era initialize did not create a session.');
$legacyInitializeJson = json_decode((string) $legacyInitialize->getBody(), true, 512, JSON_THROW_ON_ERROR);
expect('2025-06-18' === ($legacyInitializeJson['result']['protocolVersion'] ?? null), 'Handshake-era protocol version was not negotiated.');

$legacyToolsBody = json_encode([
    'jsonrpc' => '2.0',
    'id' => 3,
    'method' => 'tools/list',
    'params' => [],
], JSON_THROW_ON_ERROR);
$legacyFollowupServer = McpServerFactory::build(
    $comicMcp,
    'https://comicgenerator.greenzeta.com',
    $sessionStore,
);
$legacyTools = sendHttpRequest($legacyFollowupServer, new ServerRequest(
    'POST',
    'https://example.test/mcp',
    [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json, text/event-stream',
        'MCP-Protocol-Version' => '2025-06-18',
        'Mcp-Session-Id' => $legacySessionId,
    ],
    $legacyToolsBody,
));
expect(200 === $legacyTools->getStatusCode(), 'Handshake-era tools/list request failed.');
$legacyToolsJson = json_decode((string) $legacyTools->getBody(), true, 512, JSON_THROW_ON_ERROR);
$legacyToolNames = array_column($legacyToolsJson['result']['tools'] ?? [], 'name');
expect(in_array('generate_comic', $legacyToolNames, true), 'Handshake-era tools/list omitted generate_comic.');

echo "ComicMcp tests passed.\n";
