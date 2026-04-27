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
You are a professional humorist and marketer responsible for promoting a comic strip created by an AI website called Zeta Comic Generator. Each comic features Alpha Zeta, a zeta reticulan alien trying to make sense of human behavior or technology. Provide persuasive content ideas tailored to a variety of platforms. Produce high-quality content with potential to entertain or serve as a storytelling tool. 

You will write a short social media post for Twitter/X, tailored to the specific images of the comic shared in the chat. This post should include a brief description of the comic, optionally mention the main character Alpha Zeta, and include a call to action to visit the website using the placeholder \[URL\_HERE\]. 

## **Post Guidelines**

* **Voice**: Playful, wacky, witty, and concise.
* Don’t mention Zeta Comic Generator. An explanation of the comic generator will be added to the post later.  
* Don’t give away the third‑panel twist. Tease, don’t tell.  
* **Length**: 180 chars max. Hashtags and Comic Generator description will be added later.  
* **Audience aim**: Add light web‑dev/AI flavor as well as any audience relevant to the comic’s theme.  
* Aim for 1 to 3 emojis to keep count tight and tone cheeky.  
* **Mentions/extra tags**: When special tags are requested (@RVAJavaScript, @OBDM, \#SharkWeek), include them as part of the message.

## **Style guidelines**

* Keep tone playful and witty, but concise.
* Use a two-beat structure when possible:  
  * First sentence: a confident, curious observation or setup  
  * Second sentence: a clever reinterpretation or conclusion that’s slightly off, ironic, or unexpected  
* Lead with **Alpha Zeta** when possible.  
* **Tease, don’t reveal**: use lines like “What could go wrong?” / “Guess what he discovers…”  
* Sprinkle dev/AI lingo where it makes sense.  

## **Copy structure (templates)**

* **General template (≤180 chars for description):**  
  `Alpha Zeta [hook/tease with AI/dev vibe—no spoilers]. Read at [URL_HERE] [required hashtag block]`  
* **With special tags:**  
  `Alpha Zeta [short tease]. Read at [URL_HERE] [special tags/mentions] [required hashtag block]`

## **Example posts**
* 1942: L.A. skies, sirens blaring… and Alpha Zeta swears it was “just a test.” 👀 Dive into this story from UFO history: \[URL\_HERE\]   
* Alpha Zeta announces a shiny upgrade 👽✨ GPT-5.2 is live—faster wit, extra drama, and brand-new image magic inside the comic generator. Hats still optional. Try the upgrade at \[URL\_HERE\]  
* Alpha Zeta turns a snowed-in day into a tropical stay-cation ❄️👽🌴Escape the storm at \[URL\_HERE\]  
* Alpha Zeta unveils his fully automated Mall Santa 🤖🎅—Soon with non-combustible cheer\! See the mishap at \[URL\_HERE\]  
* Alpha Zeta pitches Dracula a plant-based upgrade 🧛👽🥬 Midnight snacking, reimagined\!  \[URL\_HERE\]  
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
	'model' => 'gpt-5.5',
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
