<?php
ini_set('display_errors', 1);
error_reporting(E_ERROR);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/key.php';

$output = new stdClass();
$output->error = '';
$output->result = null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	$output->error = 'Method not allowed.';
	echo json_encode($output);
	exit;
}

if (!defined('BUFFER_ACCESS_TOKEN') || !BUFFER_ACCESS_TOKEN) {
	http_response_code(500);
	$output->error = 'Buffer access token not configured. Set BUFFER_ACCESS_TOKEN in api/includes/key.php';
	echo json_encode($output);
	exit;
}

$input = json_decode(file_get_contents('php://input'));
if (!$input) {
	http_response_code(400);
	$output->error = 'Invalid request payload.';
	echo json_encode($output);
	exit;
}

$permalink = trim($input->permalink ?? '');
$postTextTemplate = trim($input->postTextTemplate ?? '');
$additionalText = trim($input->additionalText ?? '');
$hashtags = trim($input->hashtags ?? '');
$date = trim($input->date ?? '');
$images = $input->images ?? null;

if (!$permalink || !$postTextTemplate || !$date || !$images || !isset($images->strip) || !isset($images->panels)) {
	http_response_code(400);
	$output->error = 'Missing required post fields.';
	echo json_encode($output);
	exit;
}

$detailUrl = 'https://comicgenerator.greenzeta.com/detail/' . rawurlencode($permalink);
$finalPostText = str_replace('[URL_HERE]', $detailUrl, $postTextTemplate);
$finalPostText .= "\n\n" . $additionalText;
$finalPostText .= "\n\n" . $hashtags;

$scheduledAtTs = buildScheduleTimestamp($date);
if (!$scheduledAtTs) {
	http_response_code(400);
	$output->error = 'Invalid date. Expected YYYY-MM-DD.';
	echo json_encode($output);
	exit;
}

$stripMediaUrl = storeImageDataUrl($images->strip, 'strip');
$panelMediaUrls = [];
if (is_array($images->panels)) {
	foreach ($images->panels as $idx => $panelDataUrl) {
		$panelMediaUrls[] = storeImageDataUrl($panelDataUrl, 'panel_' . ($idx + 1));
	}
}

if (!$stripMediaUrl || count($panelMediaUrls) < 3) {
	http_response_code(400);
	$output->error = 'Could not process image media.';
	echo json_encode($output);
	exit;
}

$profiles = getBufferProfiles(BUFFER_ACCESS_TOKEN);
if (!$profiles || isset($profiles->error)) {
	http_response_code(500);
	$output->error = 'Failed to retrieve Buffer profiles.';
	$output->debug = $profiles;
	echo json_encode($output);
	exit;
}

$profileIds = [
	'twitter' => findProfileId($profiles, 'twitter', defined('BUFFER_TWITTER_PROFILE_ID') ? BUFFER_TWITTER_PROFILE_ID : ''),
	'linkedin' => findProfileId($profiles, 'linkedin', defined('BUFFER_LINKEDIN_PROFILE_ID') ? BUFFER_LINKEDIN_PROFILE_ID : ''),
	'instagram' => findProfileId($profiles, 'instagram', defined('BUFFER_INSTAGRAM_PROFILE_ID') ? BUFFER_INSTAGRAM_PROFILE_ID : ''),
];

if (!$profileIds['twitter'] || !$profileIds['linkedin'] || !$profileIds['instagram']) {
	http_response_code(400);
	$output->error = 'Missing connected Buffer profiles for twitter/linkedin/instagram.';
	$output->profiles = $profileIds;
	echo json_encode($output);
	exit;
}

$results = new stdClass();
$results->twitter = createBufferUpdate(BUFFER_ACCESS_TOKEN, [
	'profile_ids' => [$profileIds['twitter']],
	'text' => $finalPostText,
	'scheduled_at' => $scheduledAtTs,
	'media' => [
		'photo' => $stripMediaUrl,
	],
]);

$results->linkedin = createBufferUpdate(BUFFER_ACCESS_TOKEN, [
	'profile_ids' => [$profileIds['linkedin']],
	'text' => $finalPostText,
	'scheduled_at' => $scheduledAtTs,
	'media' => [
		'photo' => $stripMediaUrl,
	],
]);

$results->instagram = createBufferUpdate(BUFFER_ACCESS_TOKEN, [
	'profile_ids' => [$profileIds['instagram']],
	'text' => $finalPostText,
	'scheduled_at' => $scheduledAtTs,
	'media' => [
		'photo' => $panelMediaUrls,
	],
]);

foreach (['twitter', 'linkedin', 'instagram'] as $network) {
	$item = $results->{$network};
	if (!$item || isset($item->error) || (isset($item->success) && $item->success !== true)) {
		http_response_code(500);
		$output->error = 'Failed creating Buffer update for ' . $network;
		$output->result = $results;
		echo json_encode($output);
		exit;
	}
}

$output->result = $results;

echo json_encode($output);

function buildScheduleTimestamp($date) {
	try {
		$dt = new DateTime($date . ' 11:59:00');
		return $dt->getTimestamp();
	} catch (Exception $e) {
		return null;
	}
}

function storeImageDataUrl($dataUrl, $prefix) {
	if (!is_string($dataUrl) || strpos($dataUrl, 'data:image/') !== 0) {
		return null;
	}

	$parts = explode(',', $dataUrl, 2);
	if (count($parts) !== 2) return null;

	$meta = $parts[0];
	$base64 = $parts[1];
	$mime = 'image/png';

	if (preg_match('/^data:(image\\/[a-zA-Z0-9.+-]+);base64$/', $meta, $matches)) {
		$mime = $matches[1];
	}

	$ext = '.png';
	if ($mime === 'image/jpeg') $ext = '.jpg';
	if ($mime === 'image/webp') $ext = '.webp';

	$decoded = base64_decode($base64);
	if ($decoded === false) return null;

	$id = $prefix . '_' . bin2hex(random_bytes(12));
	$dir = __DIR__ . '/tmp';
	if (!is_dir($dir)) {
		mkdir($dir, 0775, true);
	}
	$path = $dir . '/' . $id . $ext;

	if (file_put_contents($path, $decoded) === false) {
		return null;
	}

	return 'https://comicgenerator.greenzeta.com/api/comicpromoter/media.php?id=' . rawurlencode($id . $ext);
}

function getBufferProfiles($accessToken) {
	$url = 'https://api.bufferapp.com/1/profiles.json?access_token=' . rawurlencode($accessToken);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	$responseRaw = curl_exec($ch);
	curl_close($ch);
	return json_decode($responseRaw);
}

function findProfileId($profiles, $service, $overrideId = '') {
	if ($overrideId) return $overrideId;
	if (!is_array($profiles)) return '';
	foreach ($profiles as $profile) {
		if (!is_object($profile)) continue;
		if (($profile->service ?? '') === $service) {
			return $profile->id ?? '';
		}
	}
	return '';
}

function createBufferUpdate($accessToken, $payload) {
	$body = [
		'access_token' => $accessToken,
		'text' => $payload['text'],
		'scheduled_at' => $payload['scheduled_at'],
		'shorten' => 'false',
	];

	foreach ($payload['profile_ids'] as $idx => $profileId) {
		$body['profile_ids[' . $idx . ']'] = $profileId;
	}

	if (isset($payload['media']) && isset($payload['media']['photo'])) {
		if (is_array($payload['media']['photo'])) {
			foreach ($payload['media']['photo'] as $idx => $photoUrl) {
				$body['media[photo][' . $idx . ']'] = $photoUrl;
			}
		} else {
			$body['media[photo]'] = $payload['media']['photo'];
		}
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://api.bufferapp.com/1/updates/create.json');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/x-www-form-urlencoded',
		'Authorization: Bearer ' . $accessToken,
	]);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 45);

	$responseRaw = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	$response = json_decode($responseRaw);
	if ($httpCode >= 400) {
		$err = new stdClass();
		$err->error = 'Buffer API HTTP ' . $httpCode;
		$err->response = $response ? $response : $responseRaw;
		return $err;
	}

	return $response;
}
