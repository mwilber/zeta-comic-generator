<?php
ini_set('display_errors', 0);
error_reporting(E_ERROR);

$id = isset($_GET['id']) ? basename($_GET['id']) : '';
if (!$id) {
	http_response_code(400);
	echo 'Missing id';
	exit;
}

$allowedExt = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp'];
$ext = strtolower(pathinfo($id, PATHINFO_EXTENSION));
if (!isset($allowedExt[$ext])) {
	http_response_code(400);
	echo 'Invalid file type';
	exit;
}

$path = __DIR__ . '/tmp/' . $id;
if (!is_file($path)) {
	http_response_code(404);
	echo 'Not found';
	exit;
}

header('Content-Type: ' . $allowedExt[$ext]);
header('Cache-Control: public, max-age=86400');
readfile($path);
