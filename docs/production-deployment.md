# Production SFTP deployment

The workflow uses `pull_request_target` when a pull request targeting `master` is closed, and the deploy
job runs only if it was merged. Direct pushes, unmerged PR closures and manual
workflow dispatches do not deploy. The actual branch name is lowercase `master`.
It uploads the current protected branch tip; serialized jobs cannot overwrite
production with an older PR merge snapshot.

## GitHub setup

1. Create an environment named `production` under Settings → Environments.
2. Restrict its deployment branches to the selected branch `master`. Keep branch
   protection requiring reviewed pull requests and disable bypasses. Require
   review of changes to `.github/` as part of your normal PR review.
3. Create the following **environment secrets** with these entirely new names.
   Do not reuse the removed `HOST`, `USER`, `PASSWORD` or `PRODUCTION_DEPLOY_PATH`.

| Secret | Value |
| --- | --- |
| `ZCG_LIVE_SFTP_USERNAME` | Dedicated production SFTP account username |
| `ZCG_LIVE_SFTP_PASSWORD` | New, unique, randomly generated account password |
| `ZCG_LIVE_SFTP_HOST` | SFTP hostname, e.g. `sftp.example.com`; no `https://`, `sftp://`, port or path |
| `ZCG_LIVE_SOURCE_PATH` | Local source directory relative to the checkout; use `.` for this repository |
| `ZCG_LIVE_REMOTE_PATH` | Existing absolute production document-root path as seen by the SFTP account, e.g. `/home/account/example.com`; `/` is rejected |
| `ZCG_LIVE_SFTP_PORT` | SSH/SFTP port, normally `22` |
| `ZCG_LIVE_HOST_FINGERPRINT` | Server SHA256 host fingerprint, e.g. `SHA256:...`, verified with your hosting provider |

The local path refers to the GitHub runner checkout, not a path on your computer.
Remote paths containing spaces are supported. Paths must not contain newlines.

Authentication uses only username and password. No private key, public login key,
or SSH agent is required. Obtain the server's SHA256 host fingerprint from your
hosting provider's trusted control panel or support channel. The workflow checks
the server's public identity against that fingerprint before sending the password.
This prevents sending credentials to an impersonated server. Do not trust an
unverified fingerprint gathered over the same connection. A legitimate server
host-key rotation requires updating this fingerprint secret after verification.

Use a dedicated account restricted to the site's directory, with SFTP-only access
and no shell, port forwarding or access to other sites where your host supports
those controls. Rotate the actual server password as well as the GitHub secret.
Do not add application API keys or database credentials to this workflow: they
remain in the production `api/includes/key.php` file.

If GitHub disabled this workflow, re-enable **Deploy production** in Actions after
merging the replacement and configuring the secrets. Also confirm repository
Actions settings allow the pinned `actions/checkout` action. No credentials need
to be committed or shared in chat.

## Upload behavior

Only tracked files under the source directory are staged. Hidden files and
folders are excluded except `.htaccess`; documentation, tests, SQL dumps,
private key files, logs, `admin/`, `vendor/`, backups, example credentials and
`api/includes/key.php` are excluded. Production dependencies in `vendor/` must
already be installed on the server. Review these exclusions when adding a new
runtime directory. Existing server files are never deleted, including uploads
and credentials; files removed from Git must be removed from production separately.
Files use temporary transfer names before replacement, but this is a file-by-file
upload, not an atomic site-wide release. A failed upload can leave a partial release;
fix the problem and re-run the merged PR's failed workflow job from Actions.

The token has read-only repository permissions, checkout credentials are not
persisted, and the password is passed in the process environment rather than
command-line arguments. Deployment jobs run one at a time without cancelling an
active upload. GitHub may replace pending jobs during a burst of merges; each
job checks out the latest `master` so the latest accepted site is deployed.

## Environment branch rejection

Keep the production environment restricted to `master`. GitHub evaluates ordinary
`pull_request` runs against `refs/pull/<number>/merge`, which that rule rejects.
The `pull_request_target` trigger uses the trusted default-branch context instead;
this repository's default branch must remain `master`. The merged-only job guard
and explicit `refs/heads/master` checkout are required. Never change that checkout
to the PR head or execute code from an unmerged PR in this deployment workflow.

Re-running an old failed run retains its old event and workflow definition. After
this trigger fix reaches the default branch, a subsequent PR merge will use the
new trigger. The merge introducing the fix may still use the previous definition.
