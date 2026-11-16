<?php
require_once __DIR__ . '/write_security.php';
function comicApiBootstrap() {
    ini_set('display_errors', '0');
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    set_exception_handler(function (Throwable $error) {
        $status = http_response_code();
        if ($status < 400) http_response_code(500);
        echo json_encode(['error' => $error instanceof InvalidArgumentException ? $error->getMessage() : 'Request failed']);
    });
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, ['https://comicgenerator.greenzeta.com', 'https://zcgdev.greenzeta.com'], true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }
}
// Serialize reservations before sending paid requests, including failed calls.
// Both counters are independent of cookies, workflow IDs and forwarded IP headers.
function comicPaidRateLimit($directory = null) {
    $dir = $directory ?? (sys_get_temp_dir() . '/zeta-paid-limits');
    if (!is_dir($dir) && !@mkdir($dir, 0700) && !is_dir($dir)) throw new RuntimeException('Limiter unavailable');
    $file = fopen($dir . '/reservations', 'c+');
    if (!$file || !flock($file, LOCK_EX)) throw new RuntimeException('Limiter unavailable');
    try {
        $state = json_decode(stream_get_contents($file), true) ?: [];
        $now = time();
        $day = gmdate('Y-m-d');
        if (($state['day'] ?? '') !== $day) $state = ['day' => $day, 'count' => 0, 'peers' => []];
        foreach ($state['peers'] as $key => $entry) if ($entry['start'] <= $now - 3600) unset($state['peers'][$key]);
        $peer = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $entry = $state['peers'][$peer] ?? ['start' => $now, 'count' => 0];
        $daily = defined('RATE_LIMIT') ? max(1, (int)RATE_LIMIT) : 100;
        if ($state['count'] >= $daily || $entry['count'] >= 60) {
            http_response_code(429); header('Retry-After: 3600');
            throw new InvalidArgumentException('ratelimit');
        }
        $state['count']++; $entry['count']++; $state['peers'][$peer] = $entry;
        rewind($file);
        if (!ftruncate($file, 0) || fwrite($file, json_encode($state)) === false || !fflush($file)) throw new RuntimeException('Limiter unavailable');
    } finally { flock($file, LOCK_UN); fclose($file); }
}
function comicGenerationRequest() {
    comicWriteRequest();
    foreach (['query', 'panel1', 'panel2', 'panel3', 'model', 'workflowId', 'seriesId'] as $key) {
        if (isset($_POST[$key]) && (!is_string($_POST[$key]) || strlen($_POST[$key]) > 20000)) {
            http_response_code(400); throw new InvalidArgumentException('Invalid generation input');
        }
    }
    comicPaidRateLimit();
}
// A bounded session map preserves server-generated context across comic stages.
function comicWorkflowMessages() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (!session_start(['use_strict_mode' => 1, 'cookie_httponly' => 1, 'cookie_samesite' => 'Strict',
            'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'])) {
            throw new RuntimeException('Workflow unavailable');
        }
    }
    $id = $_POST['workflowId'] ?? '';
    if (!is_string($id) || !preg_match('/\A[a-zA-Z0-9_-]{1,128}\z/', $id)) {
        http_response_code(400); throw new InvalidArgumentException('Invalid workflow ID');
    }
    $GLOBALS['comicWorkflowId'] = $id;
    $flows = $_SESSION['comicWorkflows'] ?? [];
    foreach ($flows as $key => $flow) if ($flow['time'] < time() - 3600) unset($flows[$key]);
    $_SESSION['comicWorkflows'] = array_slice($flows, -10, null, true);
    return $_SESSION['comicWorkflows'][$id]['messages'] ?? [];
}
function comicStoreWorkflowMessages($messages) {
    // Never keep an unbounded chat transcript, even inside an authenticated session.
    if (strlen(json_encode($messages)) > 262144 || count($messages) > 25) {
        unset($_SESSION['comicWorkflows'][$GLOBALS['comicWorkflowId']]);
    } else {
        $_SESSION['comicWorkflows'][$GLOBALS['comicWorkflowId']] = ['time' => time(), 'messages' => $messages];
    }
    session_write_close();
}
