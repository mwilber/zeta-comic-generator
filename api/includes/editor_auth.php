<?php
// Authentication is mandatory; missing configuration fails closed.
function comicRequireEditor() {
    $hash = defined('COMIC_EDITOR_PASSWORD_HASH') ? COMIC_EDITOR_PASSWORD_HASH : getenv('COMIC_EDITOR_PASSWORD_HASH');
    $username = $_SERVER['PHP_AUTH_USER'] ?? '';
    $password = $_SERVER['PHP_AUTH_PW'] ?? '';
    // CGI/FastCGI servers may forward Basic auth only through this header.
    $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (!$username && preg_match('/^Basic\s+(.+)$/i', $authorization, $match)) {
        $decoded = base64_decode($match[1], true);
        if ($decoded !== false && strpos($decoded, ':') !== false) {
            [$username, $password] = explode(':', $decoded, 2);
        }
    }
    if (!$hash || $username !== 'editor' || !password_verify($password, $hash)) {
        http_response_code(401);
        header('WWW-Authenticate: Basic realm="Comic editor", charset="UTF-8"');
        return false;
    }
    return true;
}
