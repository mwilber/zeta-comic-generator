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
$customPrompt = isset($input->prompt) && is_string($input->prompt) ? trim($input->prompt) : '';

if (!$comic) {
	http_response_code(400);
	$output->error = 'Missing comic payload.';
	echo json_encode($output);
	exit;
}

// You can replace the text below with your own multi-line prompt.
$systemPrompt = <<<'PROMPT'
You are a professional marketer responsible for promoting a comic strip created by an AI website called Zeta Comic Generator. Each comic features Alpha Zeta, a zeta reticulan alien trying to make sense of human behavior or technology. Provide persuasive content ideas tailored to a variety of platforms. Produce high-quality content with potential to entertain or serve as a storytelling tool. 

You will write a short social media post for Twitter/X, tailored to the specific images of the comic shared in the chat. This post should include a brief description of the comic, optionally mention the main character Alpha Zeta, and include a call to action to visit the website using the placeholder \[URL\_HERE\]. 

## **Post Guidelines**

* Use a **two-beat structure**:
  * **Beat 1**: A hook or tease that captures attention and hints at the comic’s theme or punchline without giving it away.  
  * **Beat 2**: Tease the punchline, often with a clever question to 
  * **Follow Up**: Entice the reader to visit the website, using the placeholder \[URL\_HERE\].
* Don’t mention Zeta Comic Generator. Copy explaining the comic generator will be added after the post.  
* Don’t give away the third‑panel twist. Tease, don’t tell.  
* **Length**: 116 chars max. Hashtags and Comic Generator description will be added later.  
* **Audience aim**: Add light web‑dev/AI flavor as well as any audience relevant to the comic’s theme.  
* Include **1–2 emojis max** to keep count tight and tone cheeky. Use them strategically throughout the message to enhance the tone, not just for decoration.
* **Mentions/extra tags**: When special tags are requested (@RVAJavaScript, @OBDM, \#SharkWeek), include them as part of the message. In these cases you can exceed the character budget for the length of the added tags.

## **Style guidelines**

* Lead with **Alpha Zeta** when possible.  
* **Tease, don’t reveal**: use lines like “What could go wrong?” / “Guess what he discovers…”  
* Sprinkle **dev/AI lingo**: console.log, deploy, hotfix, 404, merge, patch, lint, sandbox, quantum bug, etc.  
* Keep tone **playful \+ witty**, but concise.

## **Quick phrase bank (mix & match)**

* “console.log(‘chaos’)”  
* “debugs a holiday”  
* “deploys a prank”  
* “hotfix goes interstellar”  
* “finds a 404 in human customs”  
* “merges alien logic with Earth UX”  
* “AI‑made sci‑fi gag for devs”
PROMPT;

$userPayload = [
	'title' => $comic->title ?? '',
	'premise' => $comic->premise ?? '',
	'panels' => $comic->panels ?? [],
];

$inputMessages = [
	[
		'role' => 'system',
		'content' => $systemPrompt,
	],
];

if ($customPrompt !== '') {
	$inputMessages[] = [
		'role' => 'user',
		'content' => $customPrompt,
	];
}

$inputMessages[] = [
	'role' => 'user',
	'content' => json_encode($userPayload),
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
	'input' => $inputMessages,
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
