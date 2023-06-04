<?php
	if(isset($_POST["panel1"])) {
		$panel1 = $_POST["panel1"];
	} else {
		// FOR TESTING
		$panel1 = "The main character is inside the Apple headquarters. On stage a new virtual reality headset is unveiled to a pleasantly surprised audience.";
	}

	if(isset($_POST["panel2"])) {
		$panel2 = $_POST["panel2"];
	} else {
		// FOR TESTING
		$panel2 = "The main character, awestruck, stares at the headset with wide eyes full of excitement.";
	}

	if(isset($_POST["panel3"])) {
		$panel3 = $_POST["panel3"];
	} else {
		// FOR TESTING
		$panel3 = "The main character clenches his fists and grins as he begins to consider all of the imaginative possibilities that the new headset offers.";
	}

    
	$instructions = array(
        "The following statements describe a three part story.",
		"- " . add_period($panel1),
        "- " . add_period($panel2),
        "- " . add_period($panel3),
        "For each of the three parts, if it is appropriate for the main character to speak, determine what they should say.",
		"Write your response as a json object with a single property `panels`, which is an array of strings containing each of the the main character speech, or an empty string if they should remain silent."
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