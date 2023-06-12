<?php
if(isset($_POST["mode"])) {
	$mode = $_POST["mode"];
} else {
	$mode = "production";
}

if(isset($_POST["query"])) {
	$query = $_POST["query"];
} else {
	// FOR TESTING
	$query = "The main character takes a bite out of the taco and recoils in disgust.";
}

if($mode == "simulation") {
	$simJson = "{
		\"error\": \"\",
		\"data\": {
		  \"id\": \"cmpl-7JVIZWcC7kbMYvYwofgMti6IcdTaH\",
		  \"object\": \"text_completion\",
		  \"created\": 1684882899,
		  \"model\": \"text-davinci-003\",
		  \"choices\": [
			{
			  \"text\": \"\\n\\n{\\n   \\\"action\\\": \\\"excited\\\" \\n}\",
			  \"index\": 0,
			  \"logprobs\": null,
			  \"finish_reason\": \"stop\"
			}
		  ],
		  \"usage\": {
			\"prompt_tokens\": 124,
			\"completion_tokens\": 15,
			\"total_tokens\": 139
		  }
		},
		\"debug\": \"{\\n   \\\"action\\\": \\\"excited\\\" \\n}\",
		\"json\": {
		  \"action\": \"standing\"
		}
	}";
	$simResponse = json_decode($simJson);
	$output->json = $simResponse->json;
} else {
	$actions = [
		"none",
		"angry",
		"approval",
		"explaining",
		"joyous",
		"running",
		"sitting",
		"standing",
		"teaching",
		"terrified",
		"typing"
	];

	$instructions = array(
		"The following is a passage describing a scene in a story.",
		add_period($query),
		"Choose, from the following list words, one that best describes the character's action:", 
		implode(", ", $actions) . ".",
		"Only choose the word `none` if no character is present.",
		"Output your response as a json object with a single property, `action`. Set the value of `action` to the chosen word.",
	);

	$prompt = generatePrompt($instructions);
	$response = gptComplete($OPENAI_KEY, $prompt);

	if(OUTPUT_DEBUG_DATA) {
		$output->data = $response->data;
		$output->debug = $response->debug;
	}

	if (!in_array($response->json->action, $actions)) {
		if(!isset($response->json)) $response->json = new stdClass();
		$response->json->action = "standing";
	}

	$output->json = $response->json;
}
?>