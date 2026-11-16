Save and image hardening
========================

Creation remains anonymous, including server-to-server MCP save calls. This is
intentional: there is no creator account or ownership system. Stored text still
requires safe rendering sinks after JSON parsing; validation does not replace
the existing XSS fixes.

Editing uses HTTP Basic authentication, username `editor`. Before enabling it,
set `COMIC_EDITOR_PASSWORD_HASH` in the server environment or as a constant in
untracked `api/includes/key.php`. Generate the hash with PHP `password_hash`
using a securely supplied password. No password or hash is included in the repo.
Use HTTPS. An unset hash disables editor access. The existing DEV_SITE page gate
still applies. Update and thumbnail requests require authentication regardless
of DEV_SITE. Browser writes check Origin and Fetch Metadata; authenticated
non-browser clients must send `X-Comic-Editor: 1` if they have neither header.

Save accepts exactly three panels, bounded text/JSON fields, existing character
art, and validated background images. Local sources are confined by realpath to
assets/backgrounds and assets/backgrounds-full. Remote sources require HTTPS,
public IPv4 DNS results pinned for the connection, verified TLS, status 200,
no redirects, a 5-second connect timeout and 20-second total timeout. Each image
is limited to 12 MiB, 4096 pixels per side, and PNG/JPEG/WebP. Save decodes and
re-encodes images before publishing to S3. imgproxy shares the source restrictions
and byte/type/dimension checks. Provider signed query strings remain supported.
Providers that start returning redirect URLs will need an explicit integration
change rather than permitting unchecked redirect destinations.

Save permits 20 attempts per peer IP per hour, with locked counters in a private
system-temp directory. This is per server, survives browser session resets, and
does not trust forwarded IP headers. Shared proxies (including an MCP service)
share a quota. Multi-server hosting needs a shared limiter; temp-directory
cleanup may reset counters. No production configuration was changed.

Database writes are rolled back on save failure. Files already written or uploaded
to S3 may remain after a later failure; database and S3 cannot share a transaction.
Public generation endpoints retain their existing model-generation quota; this
change does not authenticate anonymous generation or eliminate automated spam.

Validation: `php tests/write-image-security.test.php`,
`php tests/api-json-escaping.test.php`, and
`node tests/rendering-xss.test.mjs`. No live database/S3/provider integration or
production request was performed. Deployment should smoke-test each image model,
the website and MCP creation flows, and authenticated editing in staging.
