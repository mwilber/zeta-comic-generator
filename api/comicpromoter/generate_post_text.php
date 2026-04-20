<?php
ini_set('display_errors', 1);
error_reporting(E_ERROR);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/key.php';

$output = new stdClass();
$output->error = '';
$output->postText = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	$output->error = 'Method not allowed.';
	echo json_encode($output);
	exit;
}

if (!defined('OPENAI_KEY') || !OPENAI_KEY) {
	http_response_code(500);
	$output->error = 'OpenAI API key not configured.';
	echo json_encode($output);
	exit;
}

$input = json_decode(file_get_contents('php://input'));
$comic = isset($input->comic) ? $input->comic : null;

if (!$comic) {
	http_response_code(400);
	$output->error = 'Missing comic payload.';
	echo json_encode($output);
	exit;
}

// You can replace the text below with your own multi-line prompt.
$systemPrompt = <<<'PROMPT'
Write a short (max 230 characters) social media post for a 3-panel comic starring Alpha Zeta, an analytical alien interpreting human or Earth scenarios.

* Write from a slightly detached, curious perspective (like a scientist studying humans)
* Lean into **misinterpretation, over-analysis, or “wrong but logical” conclusions**
* Use clever phrasing, not literal description—imply the joke rather than stating it
* Blend in light **dev/AI language** (e.g., protocol, calibration, debug, anomaly, optimization) when it fits naturally
* Keep tone witty, dry, and a bit cheeky—not slapstick
* Use 1–2 emojis
* End with a subtle hook or twist that invites curiosity (not the punchline)
* Include a call to action ending with: “See it at [URL_HERE]”

Avoid:

* Explaining the joke directly
* Generic phrasing (“hilarious”, “funny comic”, etc.)
* Overly literal summaries of each panel
PROMPT;

$userPayload = [
	'title' => $comic->title ?? '',
	'premise' => $comic->premise ?? '',
	'panels' => $comic->panels ?? [],
];

$body = [
	'model' => 'gpt-5.4',
	'stream' => false,
	'reasoning' => [
		'effort' => 'medium',
	],
	'text' => [
		'verbosity' => 'medium',
	],
	'input' => [
		[
			'role' => 'system',
			'content' => $systemPrompt,
		],
		[
			'role' => 'user',
			'content' => json_encode($userPayload),
		],
	],
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/responses');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
	'Authorization: Bearer ' . OPENAI_KEY,
	'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$responseRaw = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$response = json_decode($responseRaw);

if ($httpCode >= 400 || !$response) {
	http_response_code(500);
	$output->error = 'OpenAI request failed.';
	$output->debug = $responseRaw;
	echo json_encode($output);
	exit;
}

if (isset($response->error)) {
	http_response_code(500);
	$output->error = is_string($response->error) ? $response->error : ($response->error->message ?? 'OpenAI error.');
	$output->debug = $response;
	echo json_encode($output);
	exit;
}

$text = '';
if (isset($response->output) && is_array($response->output)) {
	foreach ($response->output as $item) {
		if (!is_object($item) || !isset($item->content) || !is_array($item->content)) continue;
		foreach ($item->content as $contentItem) {
			if (isset($contentItem->text) && is_string($contentItem->text)) {
				$text = trim($contentItem->text);
				break 2;
			}
		}
	}
}

if (!$text) {
	http_response_code(500);
	$output->error = 'No post text returned by model.';
	$output->debug = $response;
	echo json_encode($output);
	exit;
}

if (strpos($text, '[URL_HERE]') === false) {
	$text .= "\n\n[URL_HERE]";
}

$output->postText = $text;

echo json_encode($output);
