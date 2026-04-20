# Comic Promoter API

This directory contains standalone API endpoints for the `comicpromoter` feature.

## Required Key Configuration

Add the following constants to `api/includes/key.php`:

- `BUFFER_ACCESS_TOKEN`
- `BUFFER_LEGACY_ACCESS_TOKEN` (required for Instagram legacy `/1/updates/create.json` posting; must be a legacy OAuth token, not the new Buffer GraphQL/OIDC token)
- Optional overrides:
  - `BUFFER_TWITTER_PROFILE_ID` (Buffer Channel ID override)
  - `BUFFER_LINKEDIN_PROFILE_ID` (Buffer Channel ID override)
  - `BUFFER_INSTAGRAM_PROFILE_ID` (Buffer Channel ID override)

`OPENAI_KEY` is also required for post text generation.

## Endpoints

- `generate_post_text.php` - Generates social copy using GPT-5.4 with `[URL_HERE]` placeholder.
- `schedule_buffer_posts.php` - Schedules posts to Buffer for X/LinkedIn using Buffer GraphQL API (`https://api.buffer.com`) and Instagram using legacy Buffer API (`https://api.bufferapp.com/1/updates/create.json`).
- `media.php?id=...` - Serves temporary media files generated from base64 data URLs.
