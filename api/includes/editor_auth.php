<?php
// Configure outside version control (key.php or environment). No default password.
function comicRequireEditor() {
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
