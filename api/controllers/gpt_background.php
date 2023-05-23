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
	$query = "The main character steps back and points at the turtles, shouting \"Whoa! Who are you guys??\"";
}

if($mode == "simulation") {
	$simJson = "{
		\"error\": \"\",
		\"data\": {
		  \"id\": \"cmpl-7JVI6ET0WpqonI286deQZZgiFiHSv\",
		  \"object\": \"text_completion\",
		  \"created\": 1684882870,
		  \"model\": \"text-davinci-003\",
		  \"choices\": [
			{
			  \"text\": \"\\n\\n{\\n    \\\"background\\\": \\\"The sewer is dark and damp, with a stale smell of mildew and decay. The walls are covered in slimy green moss and fungus, and limp strands of neon-tinted algae hang from the grimy ceiling above. The murky water below lurches slowly, carrying away bits of trash and forgotten debris. In the distance, four giant shapes loom out of the shadows, their hard shells glimmering in the darkness.\\\"\\n}\",
			  \"index\": 0,
			  \"logprobs\": null,
			  \"finish_reason\": \"stop\"
			}
		  ],
		  \"usage\": {
			\"prompt_tokens\": 90,
			\"completion_tokens\": 99,
			\"total_tokens\": 189
		  }
		},
		\"debug\": \"{\\n    \\\"background\\\": \\\"The sewer is dark and damp, with a stale smell of mildew and decay. The walls are covered in slimy green moss and fungus, and limp strands of neon-tinted algae hang from the grimy ceiling above. The murky water below lurches slowly, carrying away bits of trash and forgotten debris. In the distance, four giant shapes loom out of the shadows, their hard shells glimmering in the darkness.\\\"\\n}\",
		\"json\": {
		  \"background\": \"The sewer is dark and damp, with a stale smell of mildew and decay. The walls are covered in slimy green moss and fungus, and limp strands of neon-tinted algae hang from the grimy ceiling above. The murky water below lurches slowly, carrying away bits of trash and forgotten debris. In the distance, four giant shapes loom out of the shadows, their hard shells glimmering in the darkness.\"
		}
	}";
	$simResponse = json_decode($simJson);
	$output->json = $simResponse->json;
	sleep(1);
} else {
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
}
?>