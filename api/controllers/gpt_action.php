<?php
	if(isset($_POST["query"])) {
		$query = $_POST["query"];
	} else {
		// FOR TESTING
		$query = "An empty room filled with party favors.";
	}

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
	$output->json = $response->json;

?>