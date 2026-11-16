<?php
require_once __DIR__ . '/../includes/key.php';
require_once __DIR__ . '/../includes/editor_auth.php';
require_once __DIR__ . '/../includes/request_security.php';
function comicPromoterAuth($write) {
    comicApiBootstrap();
    if (!comicRequireEditor()) {
        echo json_encode(['error' => 'Editor authentication required']); exit;
    }
    if ($write) comicEditorWriteRequest();
}
