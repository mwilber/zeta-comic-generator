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

$rawInput = file_get_contents('php://input');
$originalRawInput = $rawInput;
$input = json_decode($rawInput);
$jsonErrorCode = json_last_error();
$jsonErrorMsg = json_last_error_msg();

if (!$input && $jsonErrorCode === JSON_ERROR_CTRL_CHAR && is_string($rawInput) && $rawInput !== '') {
	$sanitizedRawInput = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $rawInput);
	if (is_string($sanitizedRawInput) && $sanitizedRawInput !== $rawInput) {
		$input = json_decode($sanitizedRawInput);
		$jsonErrorCode = json_last_error();
		$jsonErrorMsg = json_last_error_msg();
		if ($input) {
			$rawInput = $sanitizedRawInput;
		}
	}
}

if (!$input) {
	http_response_code(400);
	$output->error = 'Invalid request payload.';
	$output->debug = [
		'jsonError' => $jsonErrorMsg,
		'jsonErrorCode' => $jsonErrorCode,
		'contentType' => $_SERVER['CONTENT_TYPE'] ?? '',
		'contentLength' => isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0,
		'rawLength' => is_string($rawInput) ? strlen($rawInput) : 0,
		'postMaxSize' => ini_get('post_max_size'),
		'uploadMaxFilesize' => ini_get('upload_max_filesize'),
		'memoryLimit' => ini_get('memory_limit'),
		'controlCharSamples' => sampleControlChars($originalRawInput, 8),
	];
	$contentLength = $output->debug['contentLength'];
	$postMaxBytes = parseIniSizeToBytes((string)$output->debug['postMaxSize']);
	if ($contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
		$output->debug['hint'] = 'Request body appears larger than post_max_size; PHP may discard or truncate input.';
	}
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

$scheduledAtIso = buildScheduleIso8601($date);
if (!$scheduledAtIso) {
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

$channelIds = findBufferChannelIds(BUFFER_ACCESS_TOKEN, [
	'twitter' => defined('BUFFER_TWITTER_PROFILE_ID') ? BUFFER_TWITTER_PROFILE_ID : '',
	'linkedin' => defined('BUFFER_LINKEDIN_PROFILE_ID') ? BUFFER_LINKEDIN_PROFILE_ID : '',
	'instagram' => defined('BUFFER_INSTAGRAM_PROFILE_ID') ? BUFFER_INSTAGRAM_PROFILE_ID : '',
]);

if (isset($channelIds['error'])) {
	http_response_code(500);
	$output->error = 'Failed to retrieve Buffer channels.';
	$output->debug = $channelIds;
	echo json_encode($output);
	exit;
}

foreach (['twitter', 'linkedin', 'instagram'] as $service) {
	if (empty($channelIds[$service])) {
		http_response_code(400);
		$output->error = 'Missing connected Buffer channels for twitter/linkedin/instagram.';
		$output->channels = $channelIds;
		echo json_encode($output);
		exit;
	}
}

$results = new stdClass();
$results->twitter = createBufferScheduledPost(BUFFER_ACCESS_TOKEN, [
	'channelId' => $channelIds['twitter'],
	'text' => $finalPostText,
	'dueAt' => $scheduledAtIso,
	'service' => 'twitter',
	'imageUrls' => [$stripMediaUrl],
]);

$results->linkedin = createBufferScheduledPost(BUFFER_ACCESS_TOKEN, [
	'channelId' => $channelIds['linkedin'],
	'text' => $finalPostText,
	'dueAt' => $scheduledAtIso,
	'service' => 'linkedin',
	'imageUrls' => [$stripMediaUrl],
]);

$results->instagram = createBufferScheduledPost(BUFFER_ACCESS_TOKEN, [
	'channelId' => $channelIds['instagram'],
	'text' => $finalPostText,
	'dueAt' => $scheduledAtIso,
	'service' => 'instagram',
	'imageUrls' => $panelMediaUrls,
]);

foreach (['twitter', 'linkedin', 'instagram'] as $network) {
	$item = $results->{$network};
	if (!is_object($item) || isset($item->error) || !isset($item->post) || !isset($item->post->id)) {
		http_response_code(500);
		$networkError = (is_object($item) && isset($item->error) && is_string($item->error))
			? ': ' . $item->error
			: '.';
		$output->error = 'Failed creating Buffer post for ' . $network . $networkError;
		$output->result = $results;
		echo json_encode($output);
		exit;
	}
}

$output->result = $results;

echo json_encode($output);

function buildScheduleIso8601($date) {
	try {
		$local = new DateTime($date . ' 11:59:00');
		$utc = clone $local;
		$utc->setTimezone(new DateTimeZone('UTC'));
		return $utc->format('Y-m-d\\TH:i:s.000\\Z');
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

	return getPublicBaseUrl() . '/api/comicpromoter/media.php?id=' . rawurlencode($id . $ext);
}

function findBufferChannelIds($accessToken, $overrides = []) {
	$result = [
		'twitter' => trim($overrides['twitter'] ?? ''),
		'linkedin' => trim($overrides['linkedin'] ?? ''),
		'instagram' => trim($overrides['instagram'] ?? ''),
	];

	if ($result['twitter'] && $result['linkedin'] && $result['instagram']) {
		return $result;
	}

	$orgQuery = <<<'GQL'
query GetOrganizations {
  account {
    organizations {
      id
    }
  }
}
GQL;

	$orgResponse = callBufferGraphQL($accessToken, $orgQuery, []);
	if (isset($orgResponse['error'])) {
		return $orgResponse;
	}

	$orgs = $orgResponse['data']['account']['organizations'] ?? [];
	if (!is_array($orgs) || count($orgs) === 0) {
		return ['error' => 'No organizations returned for this Buffer account.'];
	}

	$channelsQuery = <<<'GQL'
query GetChannels($organizationId: OrganizationId!) {
  channels(input: { organizationId: $organizationId }) {
    id
    service
  }
}
GQL;

	foreach ($orgs as $org) {
		$orgId = $org['id'] ?? '';
		if (!$orgId) continue;

		$channelsResponse = callBufferGraphQL($accessToken, $channelsQuery, [
			'organizationId' => $orgId,
		]);
		if (isset($channelsResponse['error'])) {
			return $channelsResponse;
		}

		$channels = $channelsResponse['data']['channels'] ?? [];
		if (!is_array($channels)) continue;

		foreach ($channels as $channel) {
			$channelId = $channel['id'] ?? '';
			$service = normalizeBufferService($channel['service'] ?? '');
			if (!$channelId || !$service) continue;
			if (empty($result[$service])) {
				$result[$service] = $channelId;
			}
		}

		if ($result['twitter'] && $result['linkedin'] && $result['instagram']) {
			break;
		}
	}

	return $result;
}

function normalizeBufferService($service) {
	$service = strtolower(trim((string)$service));
	if (!$service) return '';
	if ($service === 'x' || $service === 'twitter' || strpos($service, 'twitter') !== false) return 'twitter';
	if ($service === 'linkedin' || strpos($service, 'linkedin') !== false) return 'linkedin';
	if ($service === 'instagram' || strpos($service, 'instagram') !== false) return 'instagram';
	return '';
}

function createBufferScheduledPost($accessToken, $params) {
	$images = [];
	foreach (($params['imageUrls'] ?? []) as $url) {
		if (is_string($url) && $url) {
			$images[] = ['url' => $url];
		}
	}

	$mutation = <<<'GQL'
mutation CreatePost($input: CreatePostInput!) {
  createPost(input: $input) {
    __typename
    ... on PostActionSuccess {
      post {
        id
        text
        assets {
          id
          mimeType
        }
      }
    }
    ... on MutationError {
      message
    }
  }
}
GQL;

	$input = [
		'text' => $params['text'],
		'channelId' => $params['channelId'],
		'schedulingType' => 'automatic',
		'mode' => 'customSchedule',
		'dueAt' => $params['dueAt'],
		'assets' => [
			'images' => $images,
		],
	];

	if (($params['service'] ?? '') === 'instagram') {
		$input['metadata'] = [
			'instagram' => [
				'type' => 'post',
				'shouldShareToFeed' => true,
			],
		];
	}

	$response = callBufferGraphQL($accessToken, $mutation, ['input' => $input]);
	if (isset($response['error'])) {
		$err = new stdClass();
		$err->error = $response['error'];
		$err->response = $response;
		return $err;
	}

	$createPost = $response['data']['createPost'] ?? null;
	if (!is_array($createPost)) {
		$err = new stdClass();
		$err->error = 'Buffer GraphQL createPost response missing.';
		$err->response = $response;
		return $err;
	}

	if (($createPost['__typename'] ?? '') === 'MutationError') {
		$err = new stdClass();
		$err->error = $createPost['message'] ?? 'Buffer createPost failed.';
		$err->response = $createPost;
		return $err;
	}

	if (!isset($createPost['post']['id'])) {
		$err = new stdClass();
		$err->error = 'Buffer createPost did not return post id.';
		$err->response = $createPost;
		return $err;
	}

	$ok = new stdClass();
	$ok->post = json_decode(json_encode($createPost['post']));
	return $ok;
}

function getPublicBaseUrl() {
	$scheme = 'https';
	if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
		$scheme = explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0];
	} elseif (!empty($_SERVER['REQUEST_SCHEME'])) {
		$scheme = $_SERVER['REQUEST_SCHEME'];
	} elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
		$scheme = 'https';
	} else {
		$scheme = 'http';
	}

	$host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? 'comicgenerator.greenzeta.com');
	$host = trim(explode(',', $host)[0]);

	return $scheme . '://' . $host;
}

function callBufferGraphQL($accessToken, $query, $variables = []) {
	$payload = [
		'query' => $query,
		'variables' => $variables,
	];

	$ch = curl_init('https://api.buffer.com');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Authorization: Bearer ' . $accessToken,
	]);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 45);

	$responseRaw = curl_exec($ch);
	$curlErr = curl_error($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($responseRaw === false) {
		return [
			'error' => 'Buffer request failed: ' . ($curlErr ?: 'unknown cURL error'),
		];
	}

	$response = json_decode($responseRaw, true);
	if (!is_array($response)) {
		return [
			'error' => 'Buffer response was not valid JSON.',
			'httpCode' => $httpCode,
			'raw' => $responseRaw,
		];
	}

	if ($httpCode >= 400) {
		return [
			'error' => 'Buffer API HTTP ' . $httpCode,
			'httpCode' => $httpCode,
			'response' => $response,
		];
	}

	if (isset($response['errors']) && is_array($response['errors']) && count($response['errors']) > 0) {
		return [
			'error' => 'Buffer GraphQL returned errors.',
			'errors' => $response['errors'],
			'response' => $response,
		];
	}

	return $response;
}

function parseIniSizeToBytes($size) {
	$size = trim(strtolower((string)$size));
	if ($size === '') return 0;

	$unit = substr($size, -1);
	$value = (float)$size;

	if ($unit === 'g') return (int)($value * 1024 * 1024 * 1024);
	if ($unit === 'm') return (int)($value * 1024 * 1024);
	if ($unit === 'k') return (int)($value * 1024);

	return (int)$value;
}

function sampleControlChars($input, $maxSamples = 8) {
	if (!is_string($input) || $input === '') return [];
	$samples = [];
	$len = strlen($input);
	$maxSamples = max(1, (int)$maxSamples);
	for ($i = 0; $i < $len; $i++) {
		$byte = ord($input[$i]);
		if ($byte < 32 || $byte === 127) {
			$start = max(0, $i - 16);
			$snippet = substr($input, $start, min(32, $len - $start));
			$samples[] = [
				'pos' => $i,
				'byte' => $byte,
				'hex' => strtoupper(str_pad(dechex($byte), 2, '0', STR_PAD_LEFT)),
				'contextHex' => strtoupper(bin2hex($snippet)),
			];
			if (count($samples) >= $maxSamples) break;
		}
	}
	return $samples;
}
