<?php
	if(isset($_POST["query"])) {
		$query = $_POST["query"];
	} else {
		// FOR TESTING
		$query = "The main character points directly up to the sky.";
	}

	$instructions = array(
		"The following is a passage describing a scene in a story.",
		"Rewrite it as a very detailed description of what the scene would look like without any characters present:",
		add_period($query),
		"Output your response as a json object with a single property, `background`. Set the value of `background` to the scene description.",
	);

	$prompt = generatePrompt($instructions);
	$response = gptComplete($OPENAI_KEY, $prompt);

	if(OUTPUT_DEBUG_DATA) {
		$output->data = $response->data;
		$output->debug = $response->debug;
	}
	$output->json = $response->json;

?>