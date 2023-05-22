<?php
	if(isset($_POST["query"])) {
		$query = $_POST["query"];
	} else {
		// FOR TESTING
		$query = "The main character steps back and points at the turtles, shouting \"Whoa! Who are you guys??\"";
	}

	$instructions = array(
		"The following is a passage describing a scene in a story.",
		"Rewrite it as a very detailed description of what the scene would look like without any characters present:",
		add_period(str_replace('"', "'", $query)),
		"Output your response as a json object with a single property, `background`. Set the value of `background` to the scene description.",
	);

	$prompt = generatePrompt($instructions);
	//print_r($prompt); die;
	$response = gptComplete($OPENAI_KEY, $prompt);

	if(OUTPUT_DEBUG_DATA) {
		$output->data = $response->data;
		$output->debug = $response->debug;
	}
	$output->json = $response->json;

?>