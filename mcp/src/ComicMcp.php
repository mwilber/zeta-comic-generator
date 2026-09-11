<?php

declare(strict_types=1);

namespace ZetaComicGenerator\Mcp;

use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Content\TextResourceContents;
use Mcp\Schema\Extension\Apps\McpApps;
use Mcp\Schema\Extension\Apps\UiResourceContentMeta;
use Mcp\Schema\Extension\Apps\UiResourceCsp;
use Mcp\Schema\Result\CallToolResult;
use RuntimeException;
use Throwable;

final class ComicMcp
{
    public const APP_URI = 'ui://zeta-comic-generator/comic-strip-v3';
    public const WORKFLOWS = ['openai', 'xai', 'google'];

    private const APP_SCRIPT_FILES = [
        'scripts/modules/ComicRenderer/CharacterAction.js',
        'scripts/modules/ComicRenderer/DialogBalloon.js',
        'scripts/modules/ComicRenderer/ComicRenderer.js',
        'scripts/modules/ComicGeneratorApi.js',
        'scripts/modules/ComicGenerationWorkflow.js',
        'scripts/modules/GenerationProgressDialog.js',
        'mcp/progress.js',
        'mcp/canvas-balloons.js',
        'mcp/app.js',
    ];

    public function __construct(
        private readonly DraftRepositoryInterface $drafts,
        private readonly WebsiteApiClientInterface $websiteApi,
        private readonly string $siteBaseUrl,
        private readonly string $projectRoot,
    ) {
    }

    public function appResource(): TextResourceContents
    {
        $template = $this->readAppFile('mcp/app.html');
        $styles = implode("\n\n", array_map(fn (string $path): string => $this->readAppFile($path), [
            'styles/strip.css',
            'styles/dialog.css',
            'styles/generation-progress.css',
            'mcp/progress.css',
        ]));
        $script = $this->buildInlineAppScript();

        if (false !== stripos($styles, '</style') || false !== stripos($script, '</script')) {
            throw new RuntimeException('The comic app contains an unsafe inline closing tag.');
        }

        $template = str_replace(
            ['{{SITE_BASE_URL}}', '{{CHARACTER_ACTIONS}}', '{{APP_STYLES}}', '{{APP_SCRIPT}}'],
            [
                htmlspecialchars($this->siteBaseUrl, ENT_QUOTES, 'UTF-8'),
                json_encode($GLOBALS['characterActions'], JSON_THROW_ON_ERROR),
                $styles,
                $script,
            ],
            $template,
        );

        $resourceDomains = [
            $this->siteBaseUrl,
            'https://fonts.gstatic.com',
            'https://imgen.x.ai',
            'https://zeta-comic-generator.s3.us-east-2.amazonaws.com',
        ];

        return new TextResourceContents(
            uri: self::APP_URI,
            mimeType: McpApps::MIME_TYPE,
            text: $template,
            meta: ['ui' => new UiResourceContentMeta(
                csp: new UiResourceCsp(
                    connectDomains: [$this->siteBaseUrl],
                    resourceDomains: $resourceDomains,
                    baseUriDomains: [$this->siteBaseUrl],
                ),
                domain: $this->siteBaseUrl,
                prefersBorder: true,
            )],
        );
    }

    private function buildInlineAppScript(): string
    {
        $bundle = [];
        foreach (self::APP_SCRIPT_FILES as $path) {
            $source = $this->readAppFile($path);
            $source = preg_replace('/^\s*import\s+\{[^}]+}\s+from\s+["\'][^"\']+["\'];\s*$/m', '', $source);
            if (null === $source) {
                throw new RuntimeException('The comic app module imports could not be bundled from '.$path.'.');
            }
            $source = preg_replace('/^export\s+(?=(?:class|const|function)\b)/m', '', $source);
            if (null === $source) {
                throw new RuntimeException('The comic app module exports could not be bundled from '.$path.'.');
            }
            if (preg_match('/^\s*(?:import|export)\b/m', $source)) {
                throw new RuntimeException('The comic app contains an unsupported module statement in '.$path.'.');
            }
            $bundle[] = trim($source);
        }

        return implode("\n\n", $bundle);
    }

    private function readAppFile(string $path): string
    {
        $contents = file_get_contents($this->projectRoot.'/'.$path);
        if (false === $contents) {
            throw new RuntimeException('The comic app resource is unavailable: '.$path.'.');
        }

        return $contents;
    }

    public function prepareComicGeneration(string $premise, string $workflow = 'openai'): CallToolResult
    {
        $premise = trim($premise);
        $workflow = strtolower(trim($workflow));
        if ('' === $premise || mb_strlen($premise) > 210) {
            return $this->error('The premise must contain between 1 and 210 characters.');
        }
        if (!in_array($workflow, self::WORKFLOWS, true)) {
            return $this->error('Choose one workflow: openai, xai, or google.');
        }

        try {
            $metrics = $this->websiteApi->metrics();
        } catch (Throwable $error) {
            return $this->error('Comic generation availability could not be checked. Please try again later.');
        }

        if (!isset($metrics['json']) || !is_array($metrics['json']) || !array_key_exists('limitreached', $metrics['json'])) {
            return $this->error('Comic generation availability could not be verified. Please try again later.');
        }
        if ($metrics['json']['limitreached'] === true) {
            return new CallToolResult(
                [new TextContent('The daily comic generation limit has been reached. Inform the user that they should try again later; do not call generate_comic.')],
                true,
                ['available' => false, 'reason' => 'daily_limit'],
            );
        }

        return new CallToolResult(
            [new TextContent('Generation is available. Call generate_comic with the same premise and workflow to open the comic app.')],
            false,
            ['available' => true, 'premise' => $premise, 'workflow' => $workflow],
        );
    }

    public function generateComic(string $premise, string $workflow = 'openai'): CallToolResult
    {
        $premise = trim($premise);
        $workflow = strtolower(trim($workflow));
        if ('' === $premise || mb_strlen($premise) > 210) {
            return $this->error('The premise must contain between 1 and 210 characters.');
        }
        if (!in_array($workflow, self::WORKFLOWS, true)) {
            return $this->error('Choose one workflow: openai, xai, or google.');
        }

        try {
            $metrics = $this->websiteApi->metrics();
        } catch (Throwable $error) {
            return $this->error('Comic generation availability could not be checked. Please try again later.');
        }

        if (!isset($metrics['json']) || !is_array($metrics['json']) || !array_key_exists('limitreached', $metrics['json'])) {
            return $this->error('Comic generation availability could not be verified. Please try again later.');
        }
        if ($metrics['json']['limitreached'] === true) {
            return new CallToolResult(
                [new TextContent('The daily comic generation limit was reached before generation began. Inform the user that they should try again later.')],
                true,
                ['available' => false, 'reason' => 'daily_limit'],
            );
        }

        try {
            $generationId = $this->drafts->createPrepared($premise, $workflow);
            $draft = $this->drafts->beginGeneration($generationId);
        } catch (Throwable $error) {
            return $this->error('The comic generation draft could not be created. Please try again later.');
        }

        return new CallToolResult(
            [new TextContent('The inline app is generating the comic. Wait for the app to report completion, then ask the user whether they want to save it.')],
            false,
            [
                'available' => true,
                'generation_id' => $generationId,
                'premise' => $draft['premise'],
                'workflow' => $draft['workflow'],
                'site_base_url' => $this->siteBaseUrl,
            ],
        );
    }

    /** @param array<string, mixed> $save_payload */
    public function stageComic(string $generation_id, array $save_payload): CallToolResult
    {
        try {
            $draft = $this->drafts->findActive($generation_id);
            if (!$draft) {
                throw new RuntimeException('This comic draft is invalid or expired.');
            }
            $payload = $this->validateSavePayload($save_payload, (string) $draft['premise']);
            $this->drafts->stage($generation_id, $payload);
        } catch (Throwable $error) {
            return $this->error($this->safeMessage($error, 'The completed comic could not be staged.'));
        }

        return new CallToolResult(
            [new TextContent('Comic draft staged.')],
            false,
            ['draft_id' => $generation_id, 'status' => 'ready'],
        );
    }

    public function saveComic(string $draft_id): CallToolResult
    {
        $reserved = false;
        try {
            $reservation = $this->drafts->reserveSave($draft_id);
            $draft = $reservation['draft'];
            if ('saved' === $reservation['state']) {
                return $this->savedResult((string) $draft['comic_id'], (string) $draft['permalink'], true);
            }
            $reserved = true;

            $payload = json_decode((string) $draft['save_payload'], true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($payload)) {
                throw new RuntimeException('The staged comic payload is invalid.');
            }

            $result = $this->websiteApi->save($payload);

            $comicId = (string) ($result['response']['comicId'] ?? '');
            $permalink = (string) ($result['response']['permalink'] ?? '');
            if ('' === $comicId || !preg_match('/^[a-f0-9]{32}$/', $permalink)) {
                $message = (string) ($result['error'] ?? 'The website save API did not return a saved comic.');
                throw new RuntimeException($message);
            }

            $this->drafts->completeSave($draft_id, $comicId, $permalink);
            return $this->savedResult($comicId, $permalink, false);
        } catch (Throwable $error) {
            if ($reserved) {
                try {
                    $this->drafts->releaseSave($draft_id, $error->getMessage());
                } catch (Throwable) {
                }
            }
            return $this->error('The comic could not be saved: '.$this->safeMessage($error, 'the save service is temporarily unavailable.'));
        }
    }

    /** @param array<string, mixed> $payload
     *  @return array<string, string>
     */
    private function validateSavePayload(array $payload, string $expectedPremise): array
    {
        $required = [
            'prompt', 'title', 'script', 'summary', 'seriesId', 'continuity', 'memory',
            'bkg1', 'bkg2', 'bkg3', 'fg1', 'fg2', 'fg3',
        ];
        $clean = [];
        foreach ($required as $field) {
            if (!array_key_exists($field, $payload) || !is_string($payload[$field])) {
                throw new RuntimeException('The completed comic is missing save field '.$field.'.');
            }
            $clean[$field] = $payload[$field];
        }

        if (!hash_equals($expectedPremise, $clean['prompt'])) {
            throw new RuntimeException('The completed comic does not match its prepared premise.');
        }
        if (mb_strlen($clean['title']) > 255 || strlen($clean['script']) > 100000) {
            throw new RuntimeException('The completed comic payload is too large.');
        }
        if (strlen(json_encode($clean, JSON_THROW_ON_ERROR)) > 150000) {
            throw new RuntimeException('The completed comic payload is too large.');
        }

        $script = json_decode($clean['script'], true);
        if (!is_array($script) || !isset($script['panels']) || !is_array($script['panels']) || 3 !== count($script['panels'])) {
            throw new RuntimeException('The completed comic script must contain exactly three panels.');
        }

        for ($panel = 1; $panel <= 3; ++$panel) {
            if (!$this->isAllowedBackgroundUrl($clean['bkg'.$panel])) {
                throw new RuntimeException('The completed comic contains an unapproved background URL.');
            }
            if (!preg_match('/^[a-z0-9_-]+\.png$/', $clean['fg'.$panel])) {
                throw new RuntimeException('The completed comic contains invalid character artwork.');
            }
        }

        return $clean;
    }

    private function isAllowedBackgroundUrl(string $url): bool
    {
        if (str_starts_with($url, '/assets/backgrounds')) {
            return true;
        }
        if (!filter_var($url, FILTER_VALIDATE_URL) || 'https' !== parse_url($url, PHP_URL_SCHEME)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $siteHost = strtolower((string) parse_url($this->siteBaseUrl, PHP_URL_HOST));
        return in_array($host, [$siteHost, 'imgen.x.ai'], true);
    }

    private function savedResult(string $comicId, string $permalink, bool $alreadySaved): CallToolResult
    {
        $url = rtrim($this->siteBaseUrl, '/').'/detail/'.$permalink;
        $prefix = $alreadySaved ? 'This comic was already saved.' : 'The comic was saved.';
        return new CallToolResult(
            [new TextContent($prefix.' Show the user this link: ['.$url.']('.$url.').')],
            false,
            ['saved' => true, 'comic_id' => $comicId, 'permalink' => $permalink, 'url' => $url],
        );
    }

    private function error(string $message): CallToolResult
    {
        return new CallToolResult([new TextContent($message)], true, ['error' => $message]);
    }

    private function safeMessage(Throwable $error, string $fallback): string
    {
        return RuntimeException::class === $error::class ? $error->getMessage() : $fallback;
    }
}
