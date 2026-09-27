# Zeta Comic Generator MCP server

The public MCP endpoint is `https://comicgenerator.greenzeta.com/mcp`. It serves both handshake-era Streamable HTTP clients and the stateless MCP `2026-07-28` protocol, and advertises the MCP Apps extension for the inline comic strip.

## Workflow

Series selection happens through conversation and tool calls; there is no series picker UI. Match the user's chosen title to the `get_series` catalog and retain its series permalink. A follow-up such as "part 1" means call `get_series_comic` with that permalink and `index: 0`, then immediately call `view_comic` with the returned **comic** permalink. If the series permalink is no longer in context, retrieve the catalog again. Ask only when the intended series is ambiguous. Complete the display request without asking the user for identifiers, offering to show it later, or substituting a series-page link. For a random comic within a series, choose an index from `0` through `comic_count - 1` and use the same lookup/display sequence.

Browse series with `get_series` (no arguments). It returns `series`, a list of entries with `title`, `description` (the site's `series.premise` text), `comic_count`, `permalink`, and the series-page `url`. Visibility matches the production series page: active series with at least one published (`gallery = 1`) comic. Counts include only published comics. Series are listed newest first; an empty catalog returns `series: []`.

Call `get_series_comic` with `{"series":"<series permalink from get_series>","index":0}` to retrieve the oldest published comic in that series. Indices are zero-based, ordered by comic timestamp ascending with ID ascending as a tie-breaker; Part N corresponds to index N minus 1. The result includes `found`, `title`, `summary`, comic `permalink`, detail-page `url`, `series`, and `index`. Pass the comic permalink to `view_comic` to display it. Missing/inactive series and out-of-range indices return `found: false`; invalid inputs and API failures return tool errors. Both tools are read-only and require no generation allowance.

The unused `api/controllers/series.php` has been repurposed and routed as `GET /api/series/`. With no query parameters it returns `json.series`; `GET /api/series/?series={series_permalink}&index=0` returns `json.found` and the selected comic metadata. Both use the standard `error`, `model`, and `json` envelope. The MCP API client uses these endpoints; the main site's series page continues its existing direct database queries. No database migration is required.

Discover comics with `get_latest_comic` or `get_random_comic` (both take no arguments). Each returns `found`, `title`, `summary`, `permalink`, and the detail-page `url`; pass the returned `permalink` directly to `view_comic`. These tools only read the database, do not open an app, and do not consume generation allowance. They use the same eligibility rules as the home page and public gallery: `gallery = 1` and `seriesId` equal to `0` or `5`. Latest sorts by timestamp descending, with ID as a tie-breaker. Random selects across the entire eligible gallery on every call and may repeat a comic. An empty gallery returns `found: false`; database failures return a tool error without exposing database details. The `summary` comes directly from `comics.summary` and is also exposed by `/api/detail/{permalink}/` and each `/api/gallery/` entry. The LLM may use a nonempty summary to describe the comic; null or empty summaries remain unchanged and are not generated. No new database migration is required.

Use `view_comic` to display an existing saved comic without generating or saving anything. Pass `permalink` as the 32-character lowercase hexadecimal comic identifier, for example `{"permalink":"a1d0c6e83f027327d8461063f4ac58a6"}`. Despite its name, this parameter is not a URL or a numeric database ID. The separate read-only viewer app fetches `/api/detail/{permalink}/` and reconstructs the background and character images using the same loader as the generator's saved-comic restoration. It requires no MCP draft or generation allowance, reloads on iframe refresh, and shows an error for unavailable comics.

1. `prepare_comic_generation` checks the existing website `/api/metrics/` endpoint without opening an app or writing a draft. It defaults to the `openai` workflow and also accepts `xai` or `google`.
2. `generate_comic` rechecks availability, creates a short-lived draft, and opens the inline MCP App. The app atomically claims the prepared draft once, then performs the same concept, script, background, image, character, and continuity sequence as the Generate page through the existing `/api` endpoints.
3. The app calls the app-only `stage_comic` tool after all three panels are complete. It then displays a persistent status below the comic telling the user to ask to save it if desired.
4. After explicit user confirmation, `save_comic` submits the staged payload to the existing `/api/save/` endpoint and returns the permanent comic URL. Saves are idempotent per draft.

On iframe reload, the comic is hidden by default and the app checks `comic_app_state` using the original `generation_id`. Only the first claim of a prepared draft permits generation. Generating, ready, saving, expired, and missing drafts remain empty on replay; a failed state lookup never starts generation. The same protection applies when multiple frames open the same tool result.

Saving stores the permanent `permalink` against the MCP generation ID in `mcp_drafts`. Saved references survive draft expiration and cleanup, and the temporary save payload is discarded after saving. On refresh, the app retrieves that permalink and loads `/api/detail/{permalink}/`, reconstructing background and character images in the same way as the main site's detail page. This works without browser storage or host-specific widget-state APIs, including when the host never forwards the later `save_comic` result to the original iframe. Old references already deleted before this change cannot be recovered; those apps stay empty. No database migration is required.

The existing API remains the source of truth for the daily rate limit. Its script-generation step records the generation attempt, whether or not the comic is subsequently saved.

The MCP App resource inlines the shared strip, dialog, and generation progress stylesheets and a server-built bundle of the existing JavaScript modules. This is intentional: ChatGPT renders the resource on a sandbox origin, where external module scripts require CORS headers even when the resource domain is allowed by the app CSP.

The website and MCP App share `GenerationProgressDialog.js`, `dialog.css`, and `generation-progress.css`. The MCP `progress.js` adapter connects workflow stage messages and API completion percentages to the dialog, while `progress.css` fits it to the embedded frame. The modal stays open through draft staging, closes on success or failure, and leaves a visible result message. Generation errors and rate limits also release the modal before asking the host to respond.

The app reports its intrinsic body height using `ui/notifications/size-changed` after initialization and responsive layout changes. Measurements are coalesced into animation frames so the renderer can update panel dimensions first. Only height is requested; the host controls width. Hosts that honor the requested height can grow for the mobile layout and shrink for the wide layout without a vertical scrollbar, subject to any host height limits.

The MCP-only `canvas-balloons.js` adapter replaces the shared renderer's `DialogBalloon.RenderImage` hook inside the iframe. It returns an accessible inline canvas and reuses `DialogBalloon.drawBalloon`, avoiding data URL images under restrictive host CSPs. The main website retains its image-based balloons. The adapter caches the font load and falls back to the browser font if loading fails.

## Deployment

1. Run `composer install --no-dev --optimize-autoloader` from the project root. Dependencies are resolved against PHP 8.1 or later.
2. Apply `mcp/migrations/001_create_mcp_drafts.sql` to the application database.
3. Deploy the root and `mcp/.htaccess` rules with Apache rewrite support enabled. The scoped `DirectorySlash Off` and `RewriteOptions AllowNoSlash` rules let `/mcp` execute `mcp/index.php` without a redirect, because a redirect can change an MCP POST into a GET in some clients.
4. Optionally define `MCP_SITE_BASE_URL` in `api/includes/key.php` or the environment. It defaults to `https://comicgenerator.greenzeta.com`.

The MCP endpoint is intentionally public, matching the public generation experience. The HTTP transport allows cross-origin MCP hosts; production host validation is expected at the existing web server or reverse proxy.

## Verification

```sh
php mcp/tests/ComicMcpTest.php
php mcp/tests/DraftRepositoryTest.php
php mcp/tests/ComicRepositoryTest.php
php mcp/tests/SeriesApiTest.php
node mcp/tests/app.test.mjs
node mcp/tests/view.test.mjs
node mcp/tests/workflow.test.mjs
node mcp/tests/progress.test.mjs
node mcp/tests/canvas-balloons.test.mjs
```
