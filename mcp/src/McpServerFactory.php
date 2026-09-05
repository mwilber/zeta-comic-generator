<?php

declare(strict_types=1);

namespace ZetaComicGenerator\Mcp;

use Mcp\Schema\Enum\ProtocolVersion;
use Mcp\Schema\Extension\Apps\McpApps;
use Mcp\Schema\Extension\Apps\ToolVisibility;
use Mcp\Schema\Extension\Apps\UiToolMeta;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server;
use Mcp\Server\Stateless\StatelessProtocol;

final class McpServerFactory
{
    public static function build(ComicMcp $comicMcp, string $siteBaseUrl): StatelessProtocol
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

        return Server::builder()
            ->setServerInfo(
                'zeta-comic-generator',
                '1.0.0',
                'Generate and optionally save three-panel Alpha Zeta comics.',
                websiteUrl: $siteBaseUrl,
                title: 'Zeta Comic Generator',
            )
            ->setInstructions(
                'To generate a comic, first call prepare_comic_generation with the user premise and optional workflow. '.
                'If it reports the daily limit, tell the user to try again later and do not call generate_comic. '.
                'If available, call generate_comic with the same premise and workflow. The inline app performs the existing website workflow. '.
                'Wait for the app completion message, then ask whether the user wants to save. '.
                'Only after explicit confirmation call save_comic with the reported draft_id, and show the returned URL. '.
                'OpenAI is the default workflow; valid alternatives are xAI and Google.'
            )
            ->enableExtension(new McpApps())
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
                description: 'Save a completed MCP comic draft through the existing website save API. Call only after the user explicitly says to save.',
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
            ->buildStateless([ProtocolVersion::V2026_07_28]);
    }
}
