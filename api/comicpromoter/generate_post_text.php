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
Write a short (max 230 characters) social post for a 3-panel comic starring Alpha Zeta, an analytical alien trying to make sense of human behavior or technology.

* Use a **two-beat structure**:
  • First sentence: a confident, curious observation or setup
  • Second sentence: a **clever reinterpretation or conclusion** that’s slightly off, ironic, or unexpected

* Tone: **dry, conversational, and witty**—not formal or scientific

* Think: an alien casually explaining something *almost* correctly

* Favor **specific, human details** over abstract language

* Humor should come from:
  • misreading human intent
  • applying logic in the wrong context
  • treating normal things like strange systems

* Use 1–2 emojis naturally (not at the start)

* End with: “See it at [URL_HERE]”

Avoid:

* Words like “protocol,” “hypothesis,” “conclusion,” “analysis,” or percentages
* Calling out "Zeta Comic Generator" directly in the post
* Overly technical or robotic phrasing
* Explaining the punchline directly
* Marketing or promotional tone
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

if (true) {

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

} else {
	$response = json_decode('{"output":[{"content":[{"text":"Testing: [URL_HERE]"}]}]}');
}

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
