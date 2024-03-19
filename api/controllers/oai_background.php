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
        "You are a talented artist who draws background art for animated cartoons. ",
        "Write Dall-E prompts to draw backgrounds for three animation cells. These animation cells depict our main character, Alpha Zeta, in a scene.",
        "Descriptions of the three scenes are as follows:",
		"- " . add_period($panel1),
        "- " . add_period($panel2),
        "- " . add_period($panel3),
        //"Generate a Dall-E prompt that creates an image for each scene in cartoon style. ",
		//"The main character, Alpha Zeta, will not appear in the background, his image will be overlaid on top of the background.",
		" ",
        "Your Dall-E prompts will be written within the following rules: ",
		"- Describe each scene as it would look if the main character, Alpha Zeta, is not present.",
		"- No characters will speak to each other.",
		"- Do not include any items that contain readable text.",
		"- Do not reference a comic strip panel.",
		"Write the prompts as a json object with a single property `descriptions`, which is an array of strings containing each of the prompts."
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

	// Record the model that was used
	$output->model = OAI_MODEL;
?>