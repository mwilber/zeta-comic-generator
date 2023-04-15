<?php
require './key.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header('Content-Type: application/json; charset=utf-8');

if(false){
echo '{"panels":[{"character":"standing","background":"A faraway view of Earth and the Sun with a starry night sky","dialog":"The Sun is 150 million kilometers away from Earth"},{"character":"sitting","background":"A mathematical equation written on an old parchment with quill pen","dialog":"That\'s equal to 8 light minutes"},{"character":"standing","background":"A view of Earth with the stars and planets around it","dialog":"So if we wanted to travel there, we would need a spaceship that could move faster than light!"}]}';

die;
}

$query = $_POST["query"];

if(!isset($query)) $query = urldecode($_GET["query"]);

//print_r($query);

$url = "https://api.openai.com/v1/completions";

$prompt = "
Using the following json object as a template, generate a json array of objects that describes the panels in a cartoon. 
The premise of the cartoon is a classroom where a teacher is the main character. 
The teacher is explains the distance between the earth and the sun. 
The `sceneDescription` property describes only the setting, it does not mention the main character. 
The `mainCharacterAsset` property contains one of two options: standing, seated at computer. 
The main character is always in the foreground, does not interact with the background and is the only character that speaks. 
Keep the dialog short. Limit to three panels.
{panels: [{ \"sceneDescription\": INSERT, \"mainCharacterDialog\": INSERT, \"mainCharacterAsset\": INSERT }]}";

$prompt = "Write a json object that represents a description of a three panel comic strip about the following: ";
$prompt .= $query;
$prompt .= " The object root has only one property, `panels` which is an array of objects. 
Each object in the panels array has three properties: `character`, `background` & `dialog`. 
The content of each of these properties must adhere to the following rules: 
- `character` describes the main character. It can contain only the values 'standing' or 'sitting'. 
- `background` is a description of the panel background image. It cannot make any reference to the main character. 
- `dialog` is dialog spoken by the main character. This can be an empty string if the character is not speaking.
";

$prompt = preg_replace('~[\r\n]+~', '\\n', $prompt);


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
		"presence_penalty": 0.6
		}';

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
curl_setopt($ch, CURLOPT_POSTFIELDS,$body);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Timeout in seconds
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$json = curl_exec($ch);


//$json = "{\"hello\": \"world\"}";
$data = json_decode($json);
//echo json_encode($data);

// $data = json_decode('{"id":"cmpl-74HbLqfdiVCZhupdaVzVd1Oci6AOY","object":"text_completion","created":1681255327,"model":"text-davinci-003","choices":[{"text":"\n\n[\n {\n sceneDescription: \"The classroom filled with students, their eyes fixed on the teacher at the front of the room.\", \n mainCharacterDialog: \"Today, we\'re going to talk about the amazing distance between the Earth and the Sun.\", \n mainCharacterAsset: \"standing\"\n },\n {\n sceneDescription: \"The teacher uses a chalkboard to draw a diagram of the planets and their orbit around the Sun.\", \n mainCharacterDialog: \"The massive distance helps keep us safe from too much radiation.\",\n mainCharacterAsset: \"seated at computer\"\n },\n {\n sceneDescription: \"The students look up towards the teacher, their eyes wide with awe.\", \n mainCharacterDialog: \"Can you all believe how far away it is? Pretty amazing, isn\'t it?\", \n mainCharacterAsset: \"standing\"\n }\n]","index":0,"logprobs":null,"finish_reason":"stop"}],"usage":{"prompt_tokens":158,"completion_tokens":197,"total_tokens":355}}');
// $data = json_decode('{"id":"cmpl-74I8EiAF3Ioa2x7rVLHiU4YH5ogay","object":"text_completion","created":1681257366,"model":"text-davinci-003","choices":[{"text":"\n{ \n \"panels\": [\n { \n \"background\": \"A sunny day in the park.\",\n \"dialog\": \"Mom, can I play on the monkey bars?\"\n },\n { \n \"background\": \"The child on the monkey bars.\", \n \"dialog\": \"Whee!\"\n },\n { \n \"background\": \"The child has fallen off the monkey bars.\",\n \"dialog\": \"Ouch!\"\n }\n ]\n}","index":0,"logprobs":null,"finish_reason":"stop"}],"usage":{"prompt_tokens":58,"completion_tokens":115,"total_tokens":173}}');

//echo str_replace("`", "", trim($data->choices[0]->text));
$script = json_decode(trim($data->choices[0]->text));
echo json_encode($script);

// print_r($script);

die;

?>

<ul>
	<?php foreach($script->panels as $panel): ?>
	<li>
		Background: <a href="/image.php?query=<?php echo $panel->background ?>" target="blank"><?php echo $panel->background ?></a>
	</li>
	<?php endforeach; ?>
</ul>