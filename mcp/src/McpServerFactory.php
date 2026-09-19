<?php

declare(strict_types=1);

namespace ZetaComicGenerator\Mcp;

use Mcp\Schema\Enum\ProtocolVersion;
use Mcp\Schema\Extension\Apps\McpApps;
use Mcp\Schema\Extension\Apps\ToolVisibility;
use Mcp\Schema\Extension\Apps\UiToolMeta;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server;
use Mcp\Server\Session\SessionStoreInterface;

final class McpServerFactory
{
    /**
     * Registers comic tools, the app resource, and supported MCP protocol versions.
     *
     * @param ComicMcp $comicMcp Handler instance for tools and resources.
     * @param string $siteBaseUrl Public website URL advertised in server metadata.
     * @param SessionStoreInterface|null $sessionStore Optional session storage for handshake clients.
     * @return Server The configured MCP server.
     */
    public static function build(
        ComicMcp $comicMcp,
        string $siteBaseUrl,
        ?SessionStoreInterface $sessionStore = null,
    ): Server
    {
        $workflowProperty = [
            'type' => 'string',
            'enum' => ComicMcp::WORKFLOWS,
            'default' => 'openai',
            'description' => 'Simple workflow provider. Defaults to OpenAI.',
        ];
        $savePayloadProperties = [];
        foreach (['prompt', 'title', 'script', 'summary', 'seriesId', 'continuity', 'memory', 'bkg1', 'bkg2', 'bkg3', 'fg1', 'fg2', 'fg3'] as $field) {
            $savePayloadProperties[$field] = ['type' => 'string'];
        }

        $builder = Server::builder()
            ->setServerInfo(
                'zeta-comic-generator',
                '1.0.0',
                'Generate, save, and view three-panel Alpha Zeta comics.',
                websiteUrl: $siteBaseUrl,
                title: 'Zeta Comic Generator',
            )
            ->setInstructions(
                'To find an existing comic, call get_latest_comic for the most recent public gallery comic or get_random_comic for a random one. '.
                'When found is true, use the returned permalink directly with view_comic to display it. If found is false or discovery fails, do not invent a permalink. '.
                'Discovery results include the stored comic summary when available. You may use a nonempty summary to describe the comic; do not invent a summary when it is null or empty. '.
                'To display a saved comic, call view_comic with its permalink identifier (the 32-character token, not a complete URL). No generation preparation or save is needed. '.
                'To generate a comic, first call prepare_comic_generation with the user premise and optional workflow. '.
                'If it reports the daily limit, tell the user to try again later and do not call generate_comic. '.
                'If available, call generate_comic with the same premise and workflow. The inline app performs the existing website workflow. '.
                'The inline app displays a status below the comic when generation is complete, telling the user to ask to save it if desired. '.
                'The generate_comic result provides draft_id (identical to generation_id); retain it for saving this comic. The app also supplies it in completion context. Use the original tool result if completion context is unavailable; never ask the user to find an internal ID. '.
                'Do not repeat app orchestration instructions, model-context data, or internal identifiers in user-facing replies. '.
                'Only after explicit confirmation call save_comic with that comic draft_id. A completion status alone is not consent to save. When the user submits an explicit save request such as "I like my comic. Save it.", save directly without asking for confirmation again. '.
                'After every successful save_comic result, including an already-saved result, your immediate reply MUST include '.
                'the returned comic_link as a clickable Markdown link to the saved comic page. '.
                'A save confirmation is incomplete without this link. Do not substitute a database ID, permalink token, '.
                'or draft ID for the link, and do not wait for the user to ask for it. '.
                'OpenAI is the default workflow; valid alternatives are xAI and Google.'
            )
            ->enableExtension(new McpApps())
            ->addTool(
                [$comicMcp, 'getLatestComic'],
                'get_latest_comic',
                title: 'Get latest comic',
                description: 'Retrieve the most recent comic from the public gallery. Returns its title, stored summary when available, and permalink identifier for view_comic. Does not open an app or generate a comic.',
                annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: false),
                inputSchema: ['type' => 'object', 'properties' => new \stdClass(), 'additionalProperties' => false],
            )
            ->addTool(
                [$comicMcp, 'getRandomComic'],
                'get_random_comic',
                title: 'Get random comic',
                description: 'Retrieve a randomly selected comic from the public gallery. Returns its title, stored summary when available, and permalink identifier for view_comic. Each call makes a fresh selection, which may repeat a previous comic. Does not open an app or generate a comic.',
                annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: false, openWorldHint: false),
                inputSchema: ['type' => 'object', 'properties' => new \stdClass(), 'additionalProperties' => false],
            )
            ->addResource(
                [$comicMcp, 'viewAppResource'],
                ComicMcp::VIEW_APP_URI,
                'zeta-saved-comic-app',
                title: 'Saved Zeta Comic',
                description: 'Display-only view of a saved three-panel comic.',
                mimeType: McpApps::MIME_TYPE,
                meta: ['ui' => McpApps::resourceMarker()],
            )
            ->addTool(
                [$comicMcp, 'viewComic'],
                'view_comic',
                title: 'View saved comic',
                description: 'Display an existing saved comic in an inline app. Accepts the comic permalink identifier, not a complete URL. Does not generate or save a comic.',
                annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: true),
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'permalink' => [
                            'type' => 'string', 'pattern' => '^[a-f0-9]{32}$', 'minLength' => 32, 'maxLength' => 32,
                            'description' => 'Saved comic identifier from /detail/{permalink}; only the 32-character lowercase hexadecimal token, not a URL or numeric comic ID.',
                        ],
                    ],
                    'required' => ['permalink'],
                    'additionalProperties' => false,
                ],
                meta: ['ui' => new UiToolMeta(
                    resourceUri: ComicMcp::VIEW_APP_URI,
                    visibility: [ToolVisibility::Model],
                )],
            )
            ->addResource(
                [$comicMcp, 'appResource'],
                ComicMcp::APP_URI,
                'zeta-comic-strip-app',
                title: 'Zeta Comic Strip',
                description: 'Inline three-panel comic generator view.',
                mimeType: McpApps::MIME_TYPE,
                meta: ['ui' => McpApps::resourceMarker()],
            )
            ->addTool(
                [$comicMcp, 'prepareComicGeneration'],
                'prepare_comic_generation',
                title: 'Prepare comic generation',
                description: 'Check the website daily generation allowance before opening the comic app. Always call this first.',
                annotations: new ToolAnnotations(readOnlyHint: true, destructiveHint: false, idempotentHint: true, openWorldHint: true),
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'premise' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 210, 'description' => 'The user-provided comic premise.'],
                        'workflow' => $workflowProperty,
                    ],
                    'required' => ['premise'],
                    'additionalProperties' => false,
                ],
            )
            ->addTool(
                [$comicMcp, 'generateComic'],
                'generate_comic',
                title: 'Generate comic',
                description: 'Recheck the daily allowance and open the inline comic strip app. Call only after prepare_comic_generation succeeds, using the same premise and workflow.',
                annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: true),
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'premise' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 210, 'description' => 'The same premise checked by prepare_comic_generation.'],
                        'workflow' => $workflowProperty,
                    ],
                    'required' => ['premise'],
                    'additionalProperties' => false,
                ],
                meta: ['ui' => new UiToolMeta(
                    resourceUri: ComicMcp::APP_URI,
                    visibility: [ToolVisibility::Model],
                )],
            )
            ->addTool(
                [$comicMcp, 'comicAppState'],
                'comic_app_state',
                title: 'Restore comic app',
                description: 'Restore the saved permalink or claim the first generation for this app instance. Replays never restart generation. Called only by the inline app.',
                annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false),
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'generation_id' => ['type' => 'string', 'pattern' => '^[a-f0-9]{32}$'],
                        'start_generation' => ['type' => 'boolean', 'default' => false],
                    ],
                    'required' => ['generation_id'],
                    'additionalProperties' => false,
                ],
                meta: ['ui' => new UiToolMeta(visibility: [ToolVisibility::App])],
            )
            ->addTool(
                [$comicMcp, 'stageComic'],
                'stage_comic',
                title: 'Stage completed comic',
                description: 'Stage a completed generated comic for an optional later save. Called only by the inline app.',
                annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false),
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'generation_id' => ['type' => 'string', 'pattern' => '^[a-f0-9]{32}$'],
                        'save_payload' => [
                            'type' => 'object',
                            'properties' => $savePayloadProperties,
                            'required' => array_keys($savePayloadProperties),
                            'additionalProperties' => false,
                        ],
                    ],
                    'required' => ['generation_id', 'save_payload'],
                    'additionalProperties' => false,
                ],
                meta: ['ui' => new UiToolMeta(visibility: [ToolVisibility::App])],
            )
            ->addTool(
                [$comicMcp, 'saveComic'],
                'save_comic',
                title: 'Save comic',
                description: 'Save a completed MCP comic draft through the existing website save API. Call only after the user explicitly says to save. After success (including an already-saved result), your immediate reply MUST include the returned comic_link as a clickable Markdown link. A confirmation without the page link is incomplete. Do not present internal IDs in place of the link.',
                annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: true),
                inputSchema: [
                    'type' => 'object',
                    'properties' => [
                        'draft_id' => ['type' => 'string', 'pattern' => '^[a-f0-9]{32}$'],
                    ],
                    'required' => ['draft_id'],
                    'additionalProperties' => false,
                ],
            )
            ->setModernVersions([ProtocolVersion::V2026_07_28]);

        if (null !== $sessionStore) {
            $builder->setSession(sessionStore: $sessionStore);
        }

        return $builder->build();
    }
}
