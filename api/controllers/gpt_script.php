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
	$query = "An explanation of the distance between Earth and the Sun";
}

if($mode == "simulation") {
	$simJson = "{
		\"error\": \"\",
		\"data\": {
		  \"id\": \"cmpl-7JVHq7m4BmOkrkPKF13z57aKeuB2b\",
		  \"object\": \"text_completion\",
		  \"created\": 1684882854,
		  \"model\": \"text-davinci-003\",
		  \"choices\": [
			{
			  \"text\": \" \\n\\n{\\n    \\\"title\\\": \\\"Turtle Surprise\\\", \\n    \\\"panels\\\": [\\n        {\\n            \\\"scene\\\": \\\"The main character is walking through a dark sewer. Suddenly, four mutant turtles surround him.\\\", \\n            \\\"dialog\\\": \\\"\\\"\\n        }, \\n        {\\n            \\\"scene\\\": \\\"The turtles are all wearing masks and brandishing various pieces of equipment. The main character looks slightly fearful but excited at the same time.\\\", \\n            \\\"dialog\\\": \\\"What… what have I stumbled upon?\\\"\\n        }, \\n        {\\n            \\\"scene\\\": \\\"The turtles start talking to the main character in their high-pitched voices.\\\", \\n            \\\"dialog\\\": \\\"'Greetings! We are the mutant turtle squad! Are you ready for an adventure?'\\\"\\n        }\\n    ]\\n}\",
			  \"index\": 0,
			  \"logprobs\": null,
			  \"finish_reason\": \"stop\"
			}
		  ],
		  \"usage\": {
			\"prompt_tokens\": 170,
			\"completion_tokens\": 175,
			\"total_tokens\": 345
		  }
		},
		\"debug\": \"{\\n    \\\"title\\\": \\\"Turtle Surprise\\\", \\n    \\\"panels\\\": [\\n        {\\n            \\\"scene\\\": \\\"The main character is walking through a dark sewer. Suddenly, four mutant turtles surround him.\\\", \\n            \\\"dialog\\\": \\\"\\\"\\n        }, \\n        {\\n            \\\"scene\\\": \\\"The turtles are all wearing masks and brandishing various pieces of equipment. The main character looks slightly fearful but excited at the same time.\\\", \\n            \\\"dialog\\\": \\\"What… what have I stumbled upon?\\\"\\n        }, \\n        {\\n            \\\"scene\\\": \\\"The turtles start talking to the main character in their high-pitched voices.\\\", \\n            \\\"dialog\\\": \\\"'Greetings! We are the mutant turtle squad! Are you ready for an adventure?'\\\"\\n        }\\n    ]\\n}\",
		\"json\": {
		  \"title\": \"Turtle Surprise\",
		  \"panels\": [
			{
			  \"scene\": \"The main character is walking through a dark sewer. Suddenly, four mutant turtles surround him.\",
			  \"dialog\": \"\"
			},
			{
			  \"scene\": \"The turtles are all wearing masks and brandishing various pieces of equipment. The main character looks slightly fearful but excited at the same time.\",
			  \"dialog\": \"What… what have I stumbled upon?\"
			},
			{
			  \"scene\": \"The turtles start talking to the main character in their high-pitched voices.\",
			  \"dialog\": \"'Greetings! We are the mutant turtle squad! Are you ready for an adventure?'\"
			}
		  ]
		}
	}";
	$simResponse = json_decode($simJson);
	$output->json = $simResponse->json;
	sleep(1);
} else {
	$instructions = array(
		"Write a json object containing the description of a humorous, three panel, comic strip.",
		"In the comic strip our main character, a short green humaniod alien named Alpha Zeta, engages in the following premise: ",
		add_period($query),
		"Include a detailed scene description and words spoken by the main character.",
		//"The description is written as a json object, describing the content that makes up the comic strip.", 
		"The json object has the following properties: `title` and `panels`.",
		"The following is a description of each property value:",
		"`title`: The title of the comic strip. Limit to 50 letters.",
		"`panels` is an array of objects with the following properties: `scene` and `dialog`",
		"`scene`: A description of the panel scene including all characters.",
		"`dialog`: Words spoken by the main character. This can be an empty string if the character is not speaking.",
	);

	$prompt = generatePrompt($instructions);
	$response = gptComplete($OPENAI_KEY, $prompt);
	//$simdata = json_decode('{"error":"","data":{"id":"cmpl-7HLL7cEu2Wil6TesgiuB7DDwAAhCo","object":"text_completion","created":1684367961,"model":"text-davinci-003","choices":[{"text":" Limit to 250 letters.\n\n{\n  \"title\" : \"Distanced from the Sun\",\n  \"panels\": [\n    \"Panel one: A person stands in a field, looking up at the sky.\",\n    \"Panel two: The person holds out both arms and says 'The distance between Earth and the Sun is about 93 million miles!'\",\n    \"Panel three: Cut back to an aerial view of the person standing in the field, with the sun in the background.\"\n  ]\n}","index":0,"logprobs":null,"finish_reason":"stop"}],"usage":{"prompt_tokens":111,"completion_tokens":108,"total_tokens":219}},"script":null}');
	
	if(isset($response->data->error)) $output->error = $response->data->error;

	if(OUTPUT_DEBUG_DATA) {
		$output->data = $response->data;
		$output->debug = $response->debug;
	}
	$output->json = $response->json;
}
?>