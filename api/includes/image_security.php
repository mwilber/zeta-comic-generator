<?php
// Shared by save and imgproxy. Never pass untrusted sources to PHP stream wrappers.
const COMIC_IMAGE_MAX_BYTES = 12582912;
function comicImageInfo($bytes) {
    if (!is_string($bytes) || strlen($bytes) > COMIC_IMAGE_MAX_BYTES) throw new InvalidArgumentException('Image exceeds size limit');
    $info = @getimagesizefromstring($bytes);
    if (!$info || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)
        || $info[0] > 4096 || $info[1] > 4096 || $info[0] * $info[1] > 16777216) {
        throw new InvalidArgumentException('Unsupported image type or dimensions');
    }
    return $info;
}
function comicAssetFile($directory, $name) {
    if (!is_string($name) || !preg_match('/\A[a-zA-Z0-9_.-]+\.(png|jpg|jpeg|webp)\z/i', $name)) throw new InvalidArgumentException('Invalid asset name');
    $root = realpath(dirname(__DIR__, 2) . '/assets/' . $directory);
    $file = $root ? realpath($root . '/' . $name) : false;
    if (!$file || dirname($file) !== $root || !is_file($file)) throw new InvalidArgumentException('Asset not found');
    return $file;
}
function comicPublicIp($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
        && !str_contains($ip, ':') && (int)explode('.', $ip)[0] < 224; // IPv4 only; avoids mapped IPv6 and transition ranges.
}
function comicRemoteTarget($url) {
    if (!is_string($url) || strlen($url) > 8192) throw new InvalidArgumentException('Invalid image URL');
    $p = parse_url($url);
    if (!$p || ($p['scheme'] ?? '') !== 'https' || empty($p['host']) || isset($p['user']) || isset($p['pass'])
        || isset($p['fragment']) || (isset($p['port']) && $p['port'] !== 443)
        || !preg_match('/\A[a-zA-Z0-9.-]+\z/', $p['host'])) throw new InvalidArgumentException('HTTPS image URL required');
    return $p['host'];
}
function comicReadImage($source) {
    if (is_string($source) && str_starts_with($source, '/assets/')) {
        if (!preg_match('~\A/assets/(backgrounds|backgrounds-full)/([^/]+)\z~', $source, $m)) throw new InvalidArgumentException('Invalid local image');
        $file = comicAssetFile($m[1], $m[2]);
        if (filesize($file) > COMIC_IMAGE_MAX_BYTES) throw new InvalidArgumentException('Image exceeds size limit');
        $bytes = file_get_contents($file);
    } else {
        $host = comicRemoteTarget($source);
        $ips = gethostbynamel($host);
        if (!$ips) throw new InvalidArgumentException('Image host unavailable');
        foreach ($ips as $ip) if (!comicPublicIp($ip)) throw new InvalidArgumentException('Non-public image host');
        $bytes = '';
        $ch = curl_init($source);
        curl_setopt_array($ch, [CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_PROXY => '',
            CURLOPT_RESOLVE => [$host . ':443:' . $ips[0]], CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20,
            CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (&$bytes) {
                if (strlen($bytes) + strlen($chunk) > COMIC_IMAGE_MAX_BYTES) return 0;
                $bytes .= $chunk;
                return strlen($chunk);
            }]);
        $ok = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!$ok || $status !== 200) throw new InvalidArgumentException('Image download failed');
    }
    comicImageInfo($bytes);
    return $bytes;
}
