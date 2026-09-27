<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\ServerRequest;
use Mcp\Server;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Schema\Wire\McpHeader;
use Mcp\Server\Transport\StreamableHttpTransport;
use ZetaComicGenerator\Mcp\ComicMcp;
use ZetaComicGenerator\Mcp\ComicRepositoryInterface;
use ZetaComicGenerator\Mcp\DraftRepositoryInterface;
use ZetaComicGenerator\Mcp\McpServerFactory;
use ZetaComicGenerator\Mcp\WebsiteApiClientInterface;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
require $root.'/mcp/src/WebsiteApiClient.php';
require $root.'/mcp/src/DraftRepositoryInterface.php';
require $root.'/mcp/src/ComicRepository.php';
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

final class FakeComics implements ComicRepositoryInterface
{
    public ?array $latestComic = ['permalink' => '11111111111111111111111111111111', 'title' => 'Latest comic', 'summary' => 'Alpha explores a planet.'];
    public ?array $randomComic = ['permalink' => '22222222222222222222222222222222', 'title' => 'Random comic', 'summary' => 'Alpha learns to cook.'];
    public array $calls = [];
    public bool $fail = false;

    public function latest(): ?array
    {
        $this->calls[] = 'latest';
        if ($this->fail) throw new RuntimeException('Private database connection details');
        return $this->latestComic;
    }

    public function random(): ?array
    {
        $this->calls[] = 'random';
        if ($this->fail) throw new RuntimeException('Private database connection details');
        return $this->randomComic;
    }
}

final class FakeWebsiteApi implements WebsiteApiClientInterface
{
    public bool $limitReached = false;
    public bool $validMetrics = true;
    public int $saveCalls = 0;
    public int $metricsCalls = 0;
    public array $seriesResponse = ['error' => '', 'json' => ['series' => [
        ['permalink' => 'space-adventures', 'title' => 'Space Adventures', 'description' => 'Alpha explores space.', 'comic_count' => 3],
    ]]];
    public array $seriesComicResponse = ['error' => '', 'json' => [
        'found' => true, 'permalink' => '33333333333333333333333333333333', 'title' => 'Series comic', 'summary' => null,
    ]];
    public array $seriesCalls = [];
    public bool $failSeries = false;

    public function series(): array
    {
        $this->seriesCalls[] = 'list';
        if ($this->failSeries) throw new RuntimeException('Private API details');
        return $this->seriesResponse;
    }

    public function seriesComic(string $series, int $index): array
    {
        $this->seriesCalls[] = [$series, $index];
        if ($this->failSeries) throw new RuntimeException('Private API details');
        return $this->seriesComicResponse;
    }

    /**
     * Returns valid, limited, or malformed metrics according to test flags.
     *
     * @return array<string, mixed> Simulated metrics response.
     */
    public function metrics(): array
    {
        ++$this->metricsCalls;
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
function protocolRequest(Server $server, string $method, array $params = [], ?string $name = null, int $expectedStatus = 200): array
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
    expect($expectedStatus === $response->getStatusCode(), 'Expected HTTP '.$expectedStatus.', got '.$response->getStatusCode().'.');
    $decoded = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    expect(is_array($decoded), 'Protocol response was not an object.');
    return $decoded;
}

$drafts = new MemoryDrafts();
$api = new FakeWebsiteApi();
$comics = new FakeComics();
$comicMcp = new ComicMcp($drafts, $api, 'https://comicgenerator.greenzeta.com', $root, $comics);

foreach (['getLatestComic' => 'latestComic', 'getRandomComic' => 'randomComic'] as $method => $field) {
    $discovered = $comicMcp->$method();
    expect(!$discovered->isError && $discovered->structuredContent['found'], 'Discovery must find a public comic.');
    expect($comics->$field['permalink'] === $discovered->structuredContent['permalink'], 'Discovery returned the wrong identifier.');
    expect($comics->$field['title'] === $discovered->structuredContent['title'], 'Discovery must return the title.');
    expect($comics->$field['summary'] === $discovered->structuredContent['summary'], 'Discovery must preserve the stored summary.');
    expect($comics->$field['summary'] === json_decode(explode("\n", $discovered->content[0]->text, 2)[1], true)['summary'], 'Text-only hosts must also receive the summary.');
    expect(str_contains($discovered->content[0]->text, $discovered->structuredContent['permalink']), 'Text-only hosts need the permalink too.');
    expect(!$comicMcp->viewComic($discovered->structuredContent['permalink'])->isError, 'Discovered identifier must be accepted by the viewer.');
    $original = $comics->$field;
    $comics->$field = null;
    $empty = $comicMcp->$method();
    expect(!$empty->isError && false === $empty->structuredContent['found'], 'Empty galleries must report found=false.');
    expect(!isset($empty->structuredContent['permalink']), 'Empty galleries cannot supply a fabricated identifier.');
    $comics->$field = ['permalink' => 'https://example.test/detail/invalid', 'title' => 'Invalid'];
    expect($comicMcp->$method()->isError, 'Invalid stored permalinks must not be forwarded.');
    $comics->fail = true;
    $failure = $comicMcp->$method();
    expect($failure->isError && !str_contains($failure->content[0]->text, 'Private'), 'Database failures must produce a safe error.');
    $comics->fail = false;
    $comics->$field = $original;
}
expect(['latest', 'latest', 'latest', 'latest', 'random', 'random', 'random', 'random'] === $comics->calls, 'Each tool must use its own selection method on every call.');
expect([] === $drafts->drafts && 0 === $api->metricsCalls && 0 === $api->saveCalls, 'Discovery must not generate, check limits, or save.');

foreach (['getLatestComic' => 'latestComic', 'getRandomComic' => 'randomComic'] as $method => $field) {
    $original = $comics->$field;
    foreach ([null, ''] as $summary) {
        $comics->$field['summary'] = $summary;
        $result = $comicMcp->$method();
        expect(!$result->isError && $result->structuredContent['summary'] === $summary, 'Missing or empty summaries must not be invented or prevent discovery.');
    }
    $comics->$field = $original;
}

// Viewing does not depend on generation availability or create a draft.
// Series discovery also bypasses generation allowance and returns viewer-ready results.
$seriesResponse = $api->seriesResponse;
$seriesComicResponse = $api->seriesComicResponse;
$metricsCalls = $api->metricsCalls;
$listed = $comicMcp->getSeries();
expect(!$listed->isError && 3 === $listed->structuredContent['series'][0]['comic_count'], 'Series listing must retain published counts.');
expect('Alpha explores space.' === $listed->structuredContent['series'][0]['description'], 'Series descriptions must come from the API.');
expect('https://comicgenerator.greenzeta.com/series/space-adventures' === $listed->structuredContent['series'][0]['url'], 'Series must include a website link.');
$seriesComic = $comicMcp->getSeriesComic('space-adventures', 0);
expect(!$seriesComic->isError && $seriesComic->structuredContent['found'], 'Series lookup must find the oldest comic.');
expect(['space-adventures', 0] === end($api->seriesCalls), 'Series lookup must preserve index zero.');
expect(null === $seriesComic->structuredContent['summary'], 'Series lookup must preserve a null summary.');
expect(!$comicMcp->viewComic($seriesComic->structuredContent['permalink'])->isError, 'Series comic must feed the existing viewer.');
$callCount = count($api->seriesCalls);
foreach ([['', 0], [' ', 0], [str_repeat('x', 256), 0], ['space-adventures', -1]] as [$series, $index]) {
    expect($comicMcp->getSeriesComic($series, $index)->isError, 'Invalid series inputs must fail.');
}
expect($callCount === count($api->seriesCalls), 'Invalid inputs must not call the API.');
$api->seriesResponse = ['error' => '', 'json' => ['series' => []]];
expect([] === $comicMcp->getSeries()->structuredContent['series'], 'An empty series list must be successful.');
$api->seriesComicResponse = ['error' => '', 'json' => ['found' => false]];
expect(false === $comicMcp->getSeriesComic('missing', 99)->structuredContent['found'], 'Unavailable series comics must return found=false.');
foreach ([[], ['error' => 'Private database details'], ['json' => ['series' => [['permalink' => 'broken']]]]] as $badResponse) {
    $api->seriesResponse = $badResponse;
    $api->seriesComicResponse = $badResponse;
    expect($comicMcp->getSeries()->isError, 'Malformed or failed listing API response must be a tool error.');
    expect($comicMcp->getSeriesComic('space-adventures', 0)->isError, 'Malformed or failed comic API response must be a tool error.');
}
$api->failSeries = true;
foreach ([$comicMcp->getSeries(), $comicMcp->getSeriesComic('space-adventures', 0)] as $failed) {
    expect($failed->isError && !str_contains($failed->content[0]->text, 'Private'), 'Transport failures must not expose API details.');
}
$api->failSeries = false;
$api->seriesResponse = $seriesResponse;
$api->seriesComicResponse = $seriesComicResponse;
expect($metricsCalls === $api->metricsCalls && [] === $drafts->drafts && 0 === $api->saveCalls, 'Series discovery must not check generation allowance or create/save a draft.');

$api->validMetrics = false;
$viewed = $comicMcp->viewComic(md5('42'));
expect(!$viewed->isError && md5('42') === $viewed->structuredContent['permalink'], 'Viewer must accept a permalink identifier.');
expect([] === $drafts->drafts && 0 === $api->saveCalls, 'Viewing must not create or save a draft.');
foreach (['', '42', 'invalid', 'https://comicgenerator.greenzeta.com/detail/'.md5('42'), md5('42')."\n"] as $invalidPermalink) {
    expect($comicMcp->viewComic($invalidPermalink)->isError, 'Viewer must reject invalid identifiers and full URLs.');
}
$api->validMetrics = true;

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
foreach (['get_latest_comic', 'get_random_comic'] as $name) {
    $tool = array_values(array_filter($tools['result']['tools'], fn ($tool) => $tool['name'] === $name))[0] ?? [];
    expect(true === ($tool['annotations']['readOnlyHint'] ?? false), $name.' must be registered as read-only.');
    expect(!isset($tool['_meta']['ui']['resourceUri']), 'Discovery tools must not open an app.');
    expect(false === $tool['inputSchema']['additionalProperties'], 'Discovery must not accept arbitrary input.');
    $result = protocolRequest($server, 'tools/call', ['name' => $name, 'arguments' => new stdClass()], $name);
    expect(true === ($result['result']['structuredContent']['found'] ?? false), $name.' failed through the protocol.');
    $view = protocolRequest($server, 'tools/call', [
        'name' => 'view_comic', 'arguments' => ['permalink' => $result['result']['structuredContent']['permalink']],
    ], 'view_comic');
    expect(false === ($view['result']['isError'] ?? false) && isset($view['result']['structuredContent']['permalink']), 'Discovery must feed the viewer through the protocol.');
}
$generateTool = null;
$seriesTools = array_column($tools['result']['tools'], null, 'name');
foreach (['get_series', 'get_series_comic'] as $name) {
    expect(isset($seriesTools[$name]), $name.' must be registered.');
    expect(true === $seriesTools[$name]['annotations']['readOnlyHint'], 'Series tools must be read-only.');
    expect(!isset($seriesTools[$name]['_meta']['ui']['resourceUri']), 'Series discovery must not open an app.');
    expect(false === $seriesTools[$name]['inputSchema']['additionalProperties'], 'Series tools must reject arbitrary input.');
}
$seriesList = protocolRequest($server, 'tools/call', ['name' => 'get_series', 'arguments' => new stdClass()], 'get_series');
$seriesPermalink = $seriesList['result']['structuredContent']['series'][0]['permalink'];
$seriesResult = protocolRequest($server, 'tools/call', ['name' => 'get_series_comic', 'arguments' => ['series' => $seriesPermalink, 'index' => 0]], 'get_series_comic');
expect(true === ($seriesResult['result']['structuredContent']['found'] ?? false), 'Series lookup must work through the protocol.');
$seriesView = protocolRequest($server, 'tools/call', ['name' => 'view_comic', 'arguments' => ['permalink' => $seriesResult['result']['structuredContent']['permalink']]], 'view_comic');
expect(isset($seriesView['result']['structuredContent']['permalink']), 'Series protocol result must feed view_comic.');
foreach ([['series' => $seriesPermalink], ['series' => $seriesPermalink, 'index' => -1], ['series' => $seriesPermalink, 'index' => 0.5], ['series' => $seriesPermalink, 'index' => '0'], ['series' => $seriesPermalink, 'index' => 0, 'extra' => true]] as $arguments) {
    $invalid = protocolRequest($server, 'tools/call', ['name' => 'get_series_comic', 'arguments' => $arguments], 'get_series_comic', 400);
    expect(isset($invalid['error']) || ($invalid['result']['isError'] ?? false), 'Protocol must reject missing, negative, fractional, string, or extra inputs.');
}
$viewTool = null;
$stageTool = null;
$stateTool = null;
foreach ($tools['result']['tools'] ?? [] as $tool) {
    if (($tool['name'] ?? null) === 'view_comic') $viewTool = $tool;
    if (($tool['name'] ?? null) === 'generate_comic') {
        $generateTool = $tool;
    }
    if (($tool['name'] ?? null) === 'comic_app_state') $stateTool = $tool;
    if (($tool['name'] ?? null) === 'stage_comic') {
        $stageTool = $tool;
    }
}
expect(ComicMcp::APP_URI === ($generateTool['_meta']['ui']['resourceUri'] ?? null), 'Generate tool is not linked to the comic app.');
expect(ComicMcp::VIEW_APP_URI === ($viewTool['_meta']['ui']['resourceUri'] ?? null), 'Viewer must have a separate app resource.');
expect(true === ($viewTool['annotations']['readOnlyHint'] ?? false), 'Viewing must be marked read-only.');
expect(['permalink'] === ($viewTool['inputSchema']['required'] ?? []), 'Viewer must require a permalink.');
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

$viewResource = protocolRequest($server, 'resources/read', ['uri' => ComicMcp::VIEW_APP_URI], ComicMcp::VIEW_APP_URI);
$viewContent = $viewResource['result']['contents'][0] ?? [];
$viewHtml = $viewContent['text'] ?? '';
expect('text/html;profile=mcp-app' === ($viewContent['mimeType'] ?? null), 'Viewer must be an MCP app.');
expect(isset($viewContent['_meta']['ui']['csp']['connectDomains']), 'Viewer requires API CSP permissions.');
expect(str_contains($viewHtml, 'class ComicRenderer') && str_contains($viewHtml, 'async function loadSavedComic'), 'Viewer must bundle shared rendering and loading.');
foreach (['ComicGeneratorApi', 'ComicGenerationWorkflow', 'comic_app_state', 'stage_comic', 'statusdialog', '{{'] as $excluded) {
    expect(!str_contains($viewHtml, $excluded), 'Viewer unexpectedly includes '.$excluded.'.');
}
expect(!preg_match('/^\s*(?:import|export)\b/m', $viewHtml), 'Viewer must have no unresolved module statements.');
$protocolView = protocolRequest($server, 'tools/call', [
    'name' => 'view_comic', 'arguments' => ['permalink' => md5('42')],
], 'view_comic');
expect(md5('42') === ($protocolView['result']['structuredContent']['permalink'] ?? null), 'View tool failed through the protocol.');

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
