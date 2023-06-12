<?php
	if(isset($_POST["query"])) {
		$query = $_POST["query"];
	} else {
		// FOR TESTING
		$query = "The figure stares as the light gets closer and sees it is a flashlight.";
	}

	if(isset($_POST["premise"])) {
		$premise = $_POST["premise"];
	} else {
		// FOR TESTING
		$premise = "a walk through the woods at night. a light in the sky. Thinks it's a ufo. The light is actually a flashlight.";
	}

	if(isset($_POST["part"])) {
		$part = $_POST["part"];
	} else {
		// FOR TESTING
		$part = "third";
	}

	// $instructions = array(
	// 	"The following is a passage describing a scene in a story.",
	// 	add_period($query),
	// 	"If it is appropriate for the main character to speak in the scene, write the words spoken by the main character.",
	// 	"Keep the speech short. Limit to 100 letters.",
	// 	"Output your response as a json object with a single property, `dialog`. Set the value of `dialog` to the written words.",
	// );

	$instructions = array(
		"The following is a passage describing a scene in a story.",
		add_period($query),
		"Determine if it is appropriate for the main character to speak in the scene, if so write the words spoken by the main character.",
		"Keep the speech short. Limit to 100 letters.",
		"Output your response as a json object with a single property, `dialog`. Set the value of `dialog` to the written words.",
	);

	// $instructions = array(
	// 	"The following is the premise for a three part story: ",
	// 	"`".add_period($premise)."`",
	// 	"The following describes the ".$part." part of the story:",
	// 	"`".add_period($query)."`",
	// 	"Determine any words should be spoken by the main character in this part of the story. If so, write the words spoken by the main character.",
	// 	"If words are written, keep the speech short. Limit to 100 letters.",
	// 	"Output your response as a json object with a single property, `dialog`. Set the value of `dialog` to the written words, or an empty string if no words are spoken.",
	// );

	$prompt = generatePrompt($instructions);
	//print_r($prompt); die;
	$response = gptComplete($OPENAI_KEY, $prompt);

	if(isset($response->data->error)) $output->error = $response->data->error;

	if(OUTPUT_DEBUG_DATA) {
		$output->data = $response->data;
		$output->debug = $response->debug;
	}
	$output->json = $response->json;

?>