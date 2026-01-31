# CODEX.md

This file provides guidance to Codex when working with code in this repository.

## Project Overview

Zeta Comic Generator is an AI-powered comic strip creation tool that generates 3-panel comics featuring Alpha Zeta, an alien mascot. The system uses multiple AI models to generate scripts and background images, then combines them with pre-drawn character art to create complete comic strips.

## Architecture

### Backend (PHP/MySQL)
- **API Router**: `/api/index.php` routes requests to controllers under `/api/controllers/`.
- **Controllers**: handle app endpoints (`comic`, `detail`, `gallery`, `series`, `save`, `metrics`, `thumbnail`, `update`, `imgproxy`) and generation endpoints (`concept`, `script`, `background`, `action`, `image`) plus simulation endpoints.
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
- GPT variants: `gpt.php`, `gpt45.php`, `gpt5.php`, `o.php`
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

## Patterns & Conventions

- Use prepared PDO statements for database operations.
- Apply `htmlspecialchars()` on user-facing output.
- JS modules use ES6 imports/exports.
- API endpoints return JSON with `error`, `model`, and `json` payloads (plus optional `debug` data).
