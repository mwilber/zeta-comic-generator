# CODEX.md

This file provides guidance to Codex when working with code in this repository.

## Project Overview

Zeta Comic Generator is an AI-powered comic strip creation tool that generates 3-panel comics featuring Alpha Zeta, an alien mascot. The system uses multiple AI models to generate scripts and background images, then combines them with pre-drawn character art to create complete comic strips.

## Architecture

### Backend (PHP/MySQL)
- **API Router**: `/api/index.php` routes requests to controllers under `/api/controllers/`.
- **Controllers**: handle app endpoints (`comic`, `detail`, `gallery`, `series`, `save`, `metrics`, `thumbnail`, `update`, `imgproxy`) and generation endpoints (`concept`, `script`, `background`, `action`, `image`) plus simulation endpoints.
- **Comic Promoter API (standalone)**: `/api/comicpromoter/` contains feature-specific endpoints that are intentionally not routed through `/api/index.php`.
- **Database**: MySQL tables include `comics`, `series`, `continuity`, `comic_continuity`, `backgrounds`, `requestlog`, `metrics`, `categories`.
- **AI Integration**: Models in `/api/models/` implement text and image generation.
- **Utilities**: `/api/includes/` provides DB, S3, prompt templates, logging, character actions, and API keys.
- **Bedrock test harness**: `/api/bedrock.php` is a standalone script for AWS Bedrock experiments (not routed through `/api/index.php`).

### Frontend (Vanilla JS + PHP templates)
- **Views**: `/views/` templates for `home`, `generate`, `gallery`, `detail`, `series`, `edit` (dev-only), `debugger`, `about`.
- **Templates**: `/templates/header.php` shared layout.
- **Scripts**: `/scripts/` page controllers (generate, gallery, detail, home, edit, debugger).
- **Modules**: `/scripts/modules/` shared classes for rendering, exporting, API access, and script display.
- **Styles**: `/styles/` page-specific CSS and shared layout styles (`main.css`, `strip.css`, `script.css`, etc.).
- **Comic Promoter frontend (standalone)**: `/comicpromoter/` contains a separate index page, scripts, and styles for social posting workflow.

## Key Components

### Comic Rendering
- **ComicRenderer**: `/scripts/modules/ComicRenderer/ComicRenderer.js` renders comic strips from JSON scripts.
- **DialogBalloon + CharacterAction**: manage balloon positioning and character actions from `/assets/character_art/`.

### Script & Export
- **ScriptRenderer**: `/scripts/modules/ScriptRenderer.js` renders script breakdowns (credits, panels, dialog).
- **ComicExporter**: `/scripts/modules/ComicExporter.js` exports panels or strips using `html2canvas` (loaded via `/scripts/html2canvas.min.js`).

### API Client
- **ComicGeneratorApi**: `/scripts/modules/ComicGeneratorApi.js` orchestrates concept/script/background/action generation, image creation, and saving.

## AI Model Integration

### Text Models (`/api/models/`)
- GPT variants: `gpt.php`, `gpt45.php`, `gpt5.php`
- Claude: `claude.php`
- Gemini: `gem.php`, `gemthink.php`
- Other: `deepseek.php`, `deepseekr.php`, `grok.php`, `llama.php`

### Image Models
- DALL-E: `dall.php`
- Google Imagen: `imagen.php`
- AWS Bedrock: `sdf.php` (Stable Diffusion), `ttn.php` (Titan)

### Model Architecture
- Base classes: `_base_model.php`, `base_model.php`, `_aws_model.php`
- Shared interface for `generateText()` and `generateImage()` via model base classes
- Request logging in `requestlog` and usage tracking in `metrics`

## Comic Script Format

Scripts use a JSON structure similar to:
```json
{
  "title": "Comic Title",
  "panels": [
    {
      "scene": "Panel description",
      "dialog": [{"character": "alpha", "text": "Dialog text"}],
      "background": "Background description",
      "action": "character_action_name",
      "altAction": "fallback_action_name",
      "images": [...]
    }
  ],
  "credits": {"concept": "", "script": "", "image": ""}
}
```

## Character Actions

Pre-drawn Alpha Zeta artwork lives in `/assets/character_art/`.
- Balloon coordinates are defined in `scripts/modules/ComicRenderer/CharacterAction.js`.
- New art requires updating balloon positioning data.

## Admin Panel

The admin panel is in `/admin/` and has its own `CLAUDE.md` with scope restrictions and UI conventions. The root `admin-continuity.php` is a legacy standalone continuity admin page (inline styles, direct DB access) and is not part of the `/admin/` system.

## Development Notes

- **Database schema**: `/admin/gz_comic_generator.sql`
- **Configuration**: `/api/includes/key.php` (copy from `key_example.php`)
- **S3 Assets**: `zeta-comic-generator.s3.us-east-2.amazonaws.com`
- **Production**: `comicgenerator.greenzeta.com`
- **Development**: `zcgdev.greenzeta.com`

## Comic Promoter Feature

The Comic Promoter is designed as a separable feature module and lives only in:
- `/comicpromoter/`
- `/api/comicpromoter/`

### Entry Point
- Frontend URL: `/comicpromoter/?permalink={comic_permalink}`

### Workflow
1. Read `permalink` from query string.
2. Fetch comic detail from the public API URL:
   - `https://comicgenerator.greenzeta.com/api/detail/{permalink}/`
3. Render the comic with existing shared modules:
   - `ComicRenderer` (`/scripts/modules/ComicRenderer/ComicRenderer.js`)
   - `ComicExporter` (`/scripts/modules/ComicExporter.js`)
4. Generate image data in-memory (base64):
   - full strip image
   - three individual panel images
5. User manually generates social post copy via OpenAI GPT-5.4 by clicking **Generate Post Text** (it does not auto-run on page load):
   - `/api/comicpromoter/generate_post_text.php`
   - Optional UI field: `Prompt` (short text input). If non-empty, it is sent as an additional `user` message immediately after the `system` message.
   - The system prompt remains unchanged regardless of optional prompt input.
   - Output must include `[URL_HERE]` placeholder for final link substitution.
6. Submit scheduling payload to:
   - `/api/comicpromoter/schedule_buffer_posts.php`
   - Use `multipart/form-data` with fields: `permalink`, `postTextTemplate`, `additionalText`, `hashtags`, `date`
   - Upload media files as `strip` (single file) and `panels[]` (three files)
7. Schedule Buffer posts for:
   - Twitter/X (full strip image)
   - LinkedIn (full strip image)
   - Instagram (single post with all three panel images attached)
8. Scheduled time is fixed at **11:59am America/New_York** on the selected date.

### API Endpoints in `/api/comicpromoter/`
- `generate_post_text.php`: Generates post text with GPT-5.4; accepts `comic` plus optional `prompt` and inserts optional prompt as an additional `user` message after `system`.
- `schedule_buffer_posts.php`: Creates scheduled Buffer posts.
- `media.php`: Serves temporary generated image files for Buffer media URLs.

### Comic Promoter Debugging Learnings (April 2026)
- Large JSON payloads containing inline base64 image data were unreliable in production and repeatedly failed with `Invalid request payload` / JSON control-character parsing errors.
- Wrapping payload JSON as base64/base64url did not reliably fix transport corruption in this environment.
- Stable approach: send text fields + binary image files via `multipart/form-data`, then persist uploads server-side and pass resulting media URLs to Buffer.
- Buffer `CreatePostInput.mode` must be `customScheduled` for this account/schema. `customSchedule` fails with GraphQL enum validation errors.
- Buffer GraphQL `createPost` currently does not expose a URL-shortening toggle (no documented `shorten` field in `CreatePostInput` or per-service metadata inputs); the legacy REST API had `shorten`, but this integration is GraphQL-based.
- Follow-up item: periodically check Buffer GraphQL docs/changelog for a URL-shortening setting and implement it if/when officially added.
- Keep deep payload byte-dump diagnostics out of steady-state code; add them only as temporary troubleshooting instrumentation and remove once root cause is confirmed.

### Required Keys in `/api/includes/key.php`
- `OPENAI_KEY` (existing)
- `BUFFER_ACCESS_TOKEN`
- Optional Buffer profile overrides:
  - `BUFFER_TWITTER_PROFILE_ID`
  - `BUFFER_LINKEDIN_PROFILE_ID`
  - `BUFFER_INSTAGRAM_PROFILE_ID`

### Isolation Rules
- Do not modify legacy pages/controllers to support Comic Promoter.
- Keep all Comic Promoter code isolated to `/comicpromoter` and `/api/comicpromoter`.
- Reuse existing shared front-end modules where possible instead of duplicating logic.

## Patterns & Conventions

- Use prepared PDO statements for database operations.
- Apply `htmlspecialchars()` on user-facing output.
- JS modules use ES6 imports/exports.
- API endpoints return JSON with `error`, `model`, and `json` payloads (plus optional `debug` data).
