<?php
require './key.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header('Content-Type: application/json; charset=utf-8');

if(false){
	echo '{"title":"Test Strip", "panels":[{"character":"standing","background":"A faraway view of Earth and the Sun with a starry night sky","dialog":"The Sun is 150 million kilometers away from Earth"},{"character":"sitting","background":"A mathematical equation written on an old parchment with quill pen","dialog":"That\'s equal to 8 light minutes"},{"character":"standing","background":"A view of Earth with the stars and planets around it","dialog":"So if we wanted to travel there, we would need a spaceship that could move faster than light!"}]}';

	die;
}

function add_period($str) {
	$last_char = substr($str, -1);
	if ($last_char !== '.' && $last_char !== '!' && $last_char !== '?') {
	  $str .= '.';
	}
	return $str;
}

function writePromptLine($prompt, $line) {
	if($line == "") return $prompt;
	$newLine = "";
	if($prompt != "") $newLine = "\\n";

	$newLine .= $line;

	return $prompt . $newLine;
}

function generatePrompt($premise) {

	// $instructions = array(
	// 	"Write a json object that represents a description of a three panel comic strip with a main character doing the following: ",
	// 	add_period($premise),
	// 	"The object root has two properties, `title` which is the title of the comic and `panels` which is an array of objects.",
	// 	"Each object in the panels array has three properties: `character`, `background` & `dialog`.",
	// 	"The content of each of these properties must adhere to the following rules:",
	// 	"- `character`: Describes the main character. It can contain only the values 'standing' or 'sitting'.",
	// 	"- `background`: A description of the environment with which the main character will interact.",
	// 	"- `dialog`: Dialog spoken by the main character. This can be an empty string if the character is not speaking.",
	// 	"- Limit each property value to 200 letters."
	// );

	$instructions = array(
		"Write a description of a three panel comic strip with the following premise:",
		add_period($premise),
		"The description is written as a json object describing the content that makes up the comic strip.", 
		"The object has the following properties: `title` and `panels`.",
		"`panels` is an array of objects with the following properties: `scene`, `setting`, `character` and `dialog`",
		"The following is a description of each property value:",
		"`title`: The title of the comic strip. Limit to 50 letters.",
		"`scene`: A description of the panel scene including all characters. Limit to 200 letters.",
		"`setting`: A unique, detailed, description of what the panel scene would look like without the main character present. Limit to 500 letters.",
		"`character`: A description of the main character's action. It can be one of the following values: `angry`, `approval`, `explaining`, `running`, `sitting`, `standing` or `terrified`.",
		"`dialog`: Dialog spoken by the main character. This can be an empty string if the character is not speaking. Limit to 150 letters.",
	);

	$prompt = "";

	foreach( $instructions as $instruction ) {
		$prompt = writePromptLine($prompt, $instruction);
	}

	return $prompt;
}

$query = $_POST["query"];

if(!isset($query)) $query = urldecode($_GET["query"]);

//print_r($query);

$url = "https://api.openai.com/v1/completions";
$prompt = generatePrompt($query);

$ch = curl_init();
$headers = array(
	'Authorization: Bearer ' . $OPENAI_KEY,
	'Content-Type: application/json',
);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_HEADER, 0);
$body = '{
		"model": "text-davinci-003",
		"prompt": "'.$prompt.'",
		"temperature": 0.9,
		"max_tokens": 500,
		"top_p": 1,
		"frequency_penalty": 0.0,
		"presence_penalty": 1.5
		}';

//echo $body; die;

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
curl_setopt($ch, CURLOPT_POSTFIELDS,$body);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Timeout in seconds
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

if (false) {
	$json = '{"id":"cmpl-78Z5l9aUrg1JiSFVc8FeA7G3WrLBU","object":"text_completion","created":1682275873,"model":"text-davinci-003","choices":[{"text":"{\"title\":\"What is ChatGPT?\",\"panels\":[{\"scene\":\"A woman is looking at a laptop screen.\",\"setting\":\"The room is dimly lit, with the light coming from a laptop sitting on a cluttered desk. A digital clock on the wall indicates it\'s late into the night.\",\"character\":\"Standing\",\"dialog\":\"\"},{\"scene\":\"The woman is typing away at her laptop.\",\"setting\":\"On the laptop\'s display there\'s a chat window open along with various windows and tabs that show coding snippets and documentation.\",\"character\":\"Sitting\",\"dialog\":\"\"},{\"scene\":\"The woman looks satisfied as she reviews the code on her screen.\",\"setting\":\"The laptop is now displaying an output log talking about how ChatGPT has finished training.\",\"character\":\"Sitting\",\"dialog\":\"Ah, so that\'s what ChatGPT does!\"}]}","index":0,"logprobs":null,"finish_reason":"stop"}],"usage":{"prompt_tokens":173,"completion_tokens":179,"total_tokens":352}}';
} else {
	$json = curl_exec($ch);
}


//$json = "{\"hello\": \"world\"}";
$data = json_decode($json);
//echo json_encode($data);

// $data = json_decode('{"id":"cmpl-74HbLqfdiVCZhupdaVzVd1Oci6AOY","object":"text_completion","created":1681255327,"model":"text-davinci-003","choices":[{"text":"\n\n[\n {\n sceneDescription: \"The classroom filled with students, their eyes fixed on the teacher at the front of the room.\", \n mainCharacterDialog: \"Today, we\'re going to talk about the amazing distance between the Earth and the Sun.\", \n mainCharacterAsset: \"standing\"\n },\n {\n sceneDescription: \"The teacher uses a chalkboard to draw a diagram of the planets and their orbit around the Sun.\", \n mainCharacterDialog: \"The massive distance helps keep us safe from too much radiation.\",\n mainCharacterAsset: \"seated at computer\"\n },\n {\n sceneDescription: \"The students look up towards the teacher, their eyes wide with awe.\", \n mainCharacterDialog: \"Can you all believe how far away it is? Pretty amazing, isn\'t it?\", \n mainCharacterAsset: \"standing\"\n }\n]","index":0,"logprobs":null,"finish_reason":"stop"}],"usage":{"prompt_tokens":158,"completion_tokens":197,"total_tokens":355}}');
// $data = json_decode('{"id":"cmpl-74I8EiAF3Ioa2x7rVLHiU4YH5ogay","object":"text_completion","created":1681257366,"model":"text-davinci-003","choices":[{"text":"\n{ \n \"panels\": [\n { \n \"background\": \"A sunny day in the park.\",\n \"dialog\": \"Mom, can I play on the monkey bars?\"\n },\n { \n \"background\": \"The child on the monkey bars.\", \n \"dialog\": \"Whee!\"\n },\n { \n \"background\": \"The child has fallen off the monkey bars.\",\n \"dialog\": \"Ouch!\"\n }\n ]\n}","index":0,"logprobs":null,"finish_reason":"stop"}],"usage":{"prompt_tokens":58,"completion_tokens":115,"total_tokens":173}}');

//echo str_replace("`", "", trim($data->choices[0]->text));
if(!isset($data->choices[0]->text)) {
	echo "ck1";
	echo $data;
	die;
}

$script = trim($data->choices[0]->text);
$script = str_replace("\\n", "", $script);
$script = str_replace("```json", "", $script);
$script = str_replace("```", "", $script);
$script = json_decode($script);

if($script) {
	echo json_encode($script);
}else{
	echo $data;
	die;
}
die;

?>