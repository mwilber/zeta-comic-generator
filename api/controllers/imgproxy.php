<?php
require_once __DIR__ . '/../includes/image_security.php';
try {
    $bytes = comicReadImage($_GET['url'] ?? null);
    $info = comicImageInfo($bytes);
    header('Content-Type: ' . $info['mime']);
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
} catch (Throwable $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo '{"error":"Invalid image source"}';
}
exit;
