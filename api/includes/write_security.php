<?php
function comicWriteRequest() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); throw new InvalidArgumentException('POST required'); }
    // Browsers supply Origin/Fetch Metadata; server-to-server MCP callers do not.
    if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site') { http_response_code(403); throw new InvalidArgumentException('Cross-site write rejected'); }
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        $origin = $_SERVER['HTTP_ORIGIN'];
        $allowed = ['https://comicgenerator.greenzeta.com', 'https://zcgdev.greenzeta.com'];
        if (!in_array($origin, $allowed, true) && !preg_match('~\Ahttp://(localhost|127\.0\.0\.1)(:[0-9]+)?\z~', $origin)) {
            http_response_code(403); throw new InvalidArgumentException('Origin not allowed');
        }
    }
}
function comicValidateScript($post) {
    foreach (['title' => 1000, 'prompt' => 20000, 'script' => 262144] as $key => $limit) {
        if (!isset($post[$key]) || !is_string($post[$key]) || strlen($post[$key]) > $limit) throw new InvalidArgumentException('Invalid ' . $key);
    }
    $script = json_decode($post['script']);
    if (!is_object($script) || !isset($script->panels) || !is_array($script->panels) || count($script->panels) !== 3) throw new InvalidArgumentException('Three-panel script required');
    foreach ($script->panels as $panel) {
        if (!is_object($panel) || !isset($panel->dialog) || !is_array($panel->dialog) || count($panel->dialog) > 20) throw new InvalidArgumentException('Invalid panel');
        foreach ($panel->dialog as $dialog) if (!is_object($dialog) || !isset($dialog->text) || !is_string($dialog->text)) throw new InvalidArgumentException('Invalid dialog');
    }
}
function comicSaveRateLimit() {
    // Per web-server, keyed only by the actual peer; never trust forwarded IP headers.
    $dir = sys_get_temp_dir() . '/zeta-save-limits';
    if (!is_dir($dir) && !@mkdir($dir, 0700) && !is_dir($dir)) throw new RuntimeException('Rate limiter unavailable');
    $file = fopen($dir . '/' . hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown'), 'c+');
    if (!$file || !flock($file, LOCK_EX)) throw new RuntimeException('Rate limiter unavailable');
    $state = json_decode(stream_get_contents($file), true);
    $now = time();
    if (!is_array($state) || ($state['start'] ?? 0) < $now - 3600) $state = ['start' => $now, 'count' => 0];
    if ($state['count'] >= 20) { fclose($file); http_response_code(429); header('Retry-After: 3600'); throw new InvalidArgumentException('Save rate limit reached'); }
    $state['count']++;
    rewind($file); ftruncate($file, 0); fwrite($file, json_encode($state)); fclose($file);
}
function comicEditorWriteRequest() {
    comicWriteRequest();
    // Basic credentials are ambient browser authority: require browser evidence
    // of same-origin intent, or an explicit header for authenticated API clients.
    if (!isset($_SERVER['HTTP_ORIGIN']) && ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') !== 'same-origin'
        && ($_SERVER['HTTP_X_COMIC_EDITOR'] ?? '') !== '1') {
        http_response_code(403); throw new InvalidArgumentException('Editor request header required');
    }
}
