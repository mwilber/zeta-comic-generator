<?php
	if(isset($_POST["query"])) {
		$query = $_POST["query"];
	} else {
		// FOR TESTING
		$query = "an encounter with four mutant turtles in a sewer - The mutant turtles are wary of the main character.";
	}

	$instructions = array(
		"The following is a passage describing a scene in a story.",
		add_period($query),
		"If it is appropriate for the main character to speak in the scene, write the words spoken by the main character.",
		"Keep the speech short. Limit to 100 letters.",
		"Output your response as a json object with a single property, `dialog`. Set the value of `dialog` to the written words.",
	);

	$prompt = generatePrompt($instructions);
	$response = gptComplete($OPENAI_KEY, $prompt);

	if(OUTPUT_DEBUG_DATA) {
		$output->data = $response->data;
		$output->debug = $response->debug;
	}
	$output->json = $response->json;

?>