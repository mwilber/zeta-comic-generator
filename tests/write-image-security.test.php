<?php
require __DIR__ . '/../api/includes/image_security.php';
require __DIR__ . '/../api/includes/write_security.php';
require __DIR__ . '/../api/includes/editor_auth.php';
function check($ok, $message) { if (!$ok) throw new RuntimeException($message); }
function rejects($fn) { try { $fn(); } catch (InvalidArgumentException $e) { return; } throw new RuntimeException('Expected rejection'); }
foreach (['file:///etc/passwd', 'http://example.com/a.png', 'https://user:pass@example.com/x', 'https://example.com:8443/x', 'https://example.com/x#f', '//example.com/a'] as $url) rejects(fn() => comicRemoteTarget($url));
foreach (['127.0.0.1','10.0.0.1','169.254.169.254','192.168.1.1','::1','::ffff:127.0.0.1','224.0.0.1'] as $ip) check(!comicPublicIp($ip), 'Private address allowed');
check(comicPublicIp('8.8.8.8'), 'Public IP rejected');
check(comicRemoteTarget('https://example.com/image.png?sig=abc') === 'example.com', 'Signed URL rejected');
foreach (['/etc/passwd','/assets/backgrounds/../../api/includes/key.php','/assets/character_art/writing.png','/assets/backgrounds/%2e%2e%2fkey.png'] as $path) rejects(fn() => comicReadImage($path));
rejects(fn() => comicAssetFile('character_art', '../writing.png'));
check(is_file(comicAssetFile('character_art','writing.png')), 'Character art rejected');
rejects(fn() => comicImageInfo('<svg xmlns="http://www.w3.org/2000/svg"/>'));
rejects(fn() => comicImageInfo(str_repeat('x', COMIC_IMAGE_MAX_BYTES + 1)));
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aN1sAAAAASUVORK5CYII=');
check(comicImageInfo($png)[0] === 1, 'PNG rejected');
$post = ['title'=>'Test', 'prompt'=>'Test', 'script'=>json_encode(['panels'=>array_fill(0,3,(object)['dialog'=>[(object)['text'=>'<img src=x onerror=alert(1)>']]])])];
comicValidateScript($post); // Content remains text; rendering sinks provide XSS protection.
rejects(fn() => comicValidateScript(array_replace($post, ['script'=>'{}'])));
rejects(fn() => comicValidateScript(array_replace($post, ['title'=>[]])));
$_SERVER = ['REQUEST_METHOD'=>'GET']; rejects(fn() => comicWriteRequest());
$_SERVER = ['REQUEST_METHOD'=>'POST','HTTP_ORIGIN'=>'https://evil.example']; rejects(fn() => comicWriteRequest());
$_SERVER = ['REQUEST_METHOD'=>'POST']; comicWriteRequest(); // MCP has no browser Origin.
rejects(fn() => comicEditorWriteRequest());
$_SERVER['HTTP_SEC_FETCH_SITE']='same-origin'; comicEditorWriteRequest();
$_SERVER['HTTP_SEC_FETCH_SITE']='cross-site'; rejects(fn() => comicEditorWriteRequest());
$_SERVER = []; check(!comicRequireEditor(), 'Anonymous editor allowed');
putenv('COMIC_EDITOR_PASSWORD_HASH=' . password_hash('test-only', PASSWORD_DEFAULT));
$_SERVER = ['PHP_AUTH_USER'=>'editor','PHP_AUTH_PW'=>'wrong']; check(!comicRequireEditor(), 'Wrong password allowed');
$_SERVER['PHP_AUTH_PW']='test-only'; check(comicRequireEditor(), 'Editor denied');
putenv('COMIC_EDITOR_PASSWORD_HASH');
foreach (['update','thumbnail'] as $controller) {
    $_SERVER = []; $output = new stdClass;
    include __DIR__ . '/../api/controllers/' . $controller . '.php';
    check(http_response_code() === 401 && $output->error !== '', 'Unauthenticated controller reached database');
}
$_SERVER = ['REMOTE_ADDR' => 'test-' . bin2hex(random_bytes(12))];
$rateFile = sys_get_temp_dir() . '/zeta-save-limits/' . hash('sha256', $_SERVER['REMOTE_ADDR']);
try {
    for ($i = 0; $i < 20; $i++) comicSaveRateLimit();
    rejects(fn() => comicSaveRateLimit());
    check(http_response_code() === 429, 'Missing rate limit status');
} finally { @unlink($rateFile); }
echo "Write/image security tests passed\n";
