<?php
require_once __DIR__ . '/../api/includes/json_response.php';

function check($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$payload = '<img src=x onerror="window.probed=1"> & \' </script><script>alert(1)</script>';
$response = (object) [
    'prompt' => $payload,
    'script' => (object) [
        'title' => $payload,
        'panels' => [(object) [
            'scene' => $payload,
            'dialog' => [(object) ['character' => 'alpha', 'text' => $payload]],
        ]],
        'credits' => (object) ['script' => $payload],
    ],
];
$encoded = encodeApiResponse($response);
check($encoded !== false, 'Response must encode successfully');
foreach (['<', '>', '&', "'"] as $character) {
    check(strpos($encoded, $character) === false, 'HTML characters must be escaped');
}
check(strpos($encoded, '\\u003Cimg') !== false, 'Tags must use JSON Unicode escapes');
check(strpos($encoded, '\\u0022') !== false, 'Content quotes must be escaped');
check(json_decode($encoded) == $response, 'Parsing must preserve all original text and structure');
check($response->prompt === $payload, 'Encoding must not mutate saved content');
check(json_decode(encodeApiResponse(['text' => '&lt;img&gt;']))->text === '&lt;img&gt;', 'Existing entities must round-trip unchanged');
echo "API JSON escaping checks passed.\n";
