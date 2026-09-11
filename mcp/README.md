# Zeta Comic Generator MCP server

The public MCP endpoint is `https://comicgenerator.greenzeta.com/mcp`. It serves both handshake-era Streamable HTTP clients and the stateless MCP `2026-07-28` protocol, and advertises the MCP Apps extension for the inline comic strip.

## Workflow

1. `prepare_comic_generation` checks the existing website `/api/metrics/` endpoint without opening an app or writing a draft. It defaults to the `openai` workflow and also accepts `xai` or `google`.
2. `generate_comic` rechecks availability, creates a short-lived draft, and opens the inline MCP App. The app performs the same concept, script, background, image, character, and continuity sequence as the Generate page through the existing `/api` endpoints.
3. The app calls the app-only `stage_comic` tool after all three panels are complete. It then asks the host to prompt the user about saving.
4. After explicit user confirmation, `save_comic` submits the staged payload to the existing `/api/save/` endpoint and returns the permanent comic URL. Saves are idempotent per draft.

The existing API remains the source of truth for the daily rate limit. Its script-generation step records the generation attempt, whether or not the comic is subsequently saved.

The MCP App resource inlines the shared strip, dialog, and generation progress stylesheets and a server-built bundle of the existing JavaScript modules. This is intentional: ChatGPT renders the resource on a sandbox origin, where external module scripts require CORS headers even when the resource domain is allowed by the app CSP.

The website and MCP App share `GenerationProgressDialog.js`, `dialog.css`, and `generation-progress.css`. The MCP `progress.js` adapter connects workflow stage messages and API completion percentages to the dialog, while `progress.css` fits it to the embedded frame. The modal stays open through draft staging, closes on success or failure, and leaves a visible result message. Generation errors and rate limits also release the modal before asking the host to respond.

## Deployment

1. Run `composer install --no-dev --optimize-autoloader` from the project root. Dependencies are resolved against PHP 8.1 or later.
2. Apply `mcp/migrations/001_create_mcp_drafts.sql` to the application database.
3. Deploy the root and `mcp/.htaccess` rules with Apache rewrite support enabled. The scoped `DirectorySlash Off` and `RewriteOptions AllowNoSlash` rules let `/mcp` execute `mcp/index.php` without a redirect, because a redirect can change an MCP POST into a GET in some clients.
4. Optionally define `MCP_SITE_BASE_URL` in `api/includes/key.php` or the environment. It defaults to `https://comicgenerator.greenzeta.com`.

The MCP endpoint is intentionally public, matching the public generation experience. The HTTP transport allows cross-origin MCP hosts; production host validation is expected at the existing web server or reverse proxy.

## Verification

```sh
php mcp/tests/ComicMcpTest.php
node mcp/tests/workflow.test.mjs
node mcp/tests/progress.test.mjs
```
