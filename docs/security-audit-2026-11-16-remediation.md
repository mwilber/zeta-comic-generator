# November 16 security audit remediation

Code changes:

- Editor writes and every Comic Promoter entry point require Basic authentication as `editor`. Missing password hash fails closed; the old enable/disable flag no longer bypasses login. CGI authorization headers are supported.
- Promoter writes retain browser origin/CSRF checks. Authenticated non-browser clients must send `X-Comic-Editor: 1`.
- Promoter uploads validate actual PNG, JPEG or WebP bytes, dimensions and size, including legacy data URLs. Arbitrary HTML and SVG cannot be uploaded.
- All public paid generation requests and promoter text generation reserve quota before calling providers. File locking serializes reservations across PHP workers: 60 calls per peer per hour and RATE_LIMIT globally per UTC day (default 100). Failed calls consume reservations. This single-server limiter uses private temporary storage and must remain writable and persistent across workers. Provider-level spend caps remain necessary.
- Text and image calls both contribute to database metrics. Browser-supplied message arrays are ignored. Server sessions retain bounded workflow history; clients must retain the session cookie for multi-step generation.
- New anonymous continuity records are inactive until reviewed.
- Continuity-filtered galleries honor gallery visibility.
- Database/model exceptions return generic errors. Display errors is disabled at application entry points. API CORS permits only the production/development origins.
- Legacy comic/log routes and test/Bedrock scripts are disabled. Apache rules deny repository metadata, tests, dependency artifacts, internal PHP files, migration SQL and promoter temporary files.
- S3 upload failures now return false instead of being overwritten by a finally return.
- Existing image proxy, save-path, SQL and rendering protections remain in place and their regression tests pass.

Deployment and IT handoff:

1. Deploy the complete change, including PHP and dotfiles; this checkout does not change production.
2. Configure COMIC_EDITOR_PASSWORD_HASH in api/includes/key.php or the environment. Generate a password hash locally using PHP password_hash. Login username is editor. The promoter and editor stay locked until configured.
3. Verify Apache honors the deny/rewrite rules and forwards Authorization on DreamHost. Confirm anonymous editor/promoter requests receive 401, blocked artifact URLs cannot be fetched, and invalid imgproxy URLs return a JSON 400.
4. Rotate/revoke exposed credentials, review Buffer queues and S3 probe objects, and set provider spending caps. Handle historical secrets in the other repositories separately.
5. Remove stale files from the deployed tree. Remove vulnerable DNS before deleting its bucket; secure or retire proxygpt and unrelated demo sites, update Ampache, and block exposed Git metadata on other hosts.
6. Review existing active continuity rows for earlier poisoning; new rows require moderation.

Validation: PHP syntax checks, write/image security tests, API JSON escaping tests, rendering XSS tests, and paid quota/server-history regression checks. Live providers, database, Buffer scheduling and production Apache configuration were not exercised.
