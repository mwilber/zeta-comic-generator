<?php
// Password protection is temporarily disabled. Re-enable in key.php with
// define('COMIC_EDITOR_AUTH_ENABLED', true) after server authentication is fixed.
// Other write validation and same-origin checks remain active.
function comicRequireEditor() {
    if (!defined('COMIC_EDITOR_AUTH_ENABLED') || !COMIC_EDITOR_AUTH_ENABLED) {
        return true;
    }
    $hash = defined('COMIC_EDITOR_PASSWORD_HASH') ? COMIC_EDITOR_PASSWORD_HASH : getenv('COMIC_EDITOR_PASSWORD_HASH');
    $username = $_SERVER['PHP_AUTH_USER'] ?? '';
    $password = $_SERVER['PHP_AUTH_PW'] ?? '';
    if (!$hash || $username !== 'editor' || !password_verify($password, $hash)) {
        http_response_code(401);
        header('WWW-Authenticate: Basic realm="Comic editor", charset="UTF-8"');
        return false;
    }
    return true;
}
