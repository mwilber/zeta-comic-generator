<?php
	if(isset($_POST["query"])) {
		$query = $_POST["query"];
	} else {
		// FOR TESTING
		$query = "The figure stares as the light gets closer and sees it is a flashlight.";
	}

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
        "The following statements are passages in a story.",
		"- " . add_period($panel1),
        "- " . add_period($panel2),
        "- " . add_period($panel3),
        "Rewrite each of the three passages as a detailed description of what the scene would look like without any characters present.",
		"Write your response as a json object with a single property `descriptions`, which is an array of strings containing each of the descriptions.",
		"Do not reference a panel in the description."
    );

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