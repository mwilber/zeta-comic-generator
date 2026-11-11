<?php
require_once __DIR__ . '/../includes/editor_auth.php';
require_once __DIR__ . '/../includes/write_security.php';
$output->response = new stdClass;
if (!comicRequireEditor()) { $output->error = 'Editor authentication required'; return; }
try {
    comicEditorWriteRequest();
    comicValidateScript($_POST);
    if (!isset($_POST['id']) || !is_string($_POST['id']) || !ctype_digit($_POST['id']) || (int)$_POST['id'] < 1) throw new InvalidArgumentException('Invalid comic ID');
} catch (InvalidArgumentException $e) {
    if (http_response_code() < 400) http_response_code(400);
    $output->error = $e->getMessage(); return;
}
$db = (new Database())->getConnection();
$stmt = $db->prepare('UPDATE comics SET title = ?, prompt = ?, json = ? WHERE id = ?');
$stmt->execute([$_POST['title'], $_POST['prompt'], $_POST['script'], $_POST['id']]);
$output->response->values = $_POST['id'];
