<?php
/**
 * Used only when the global constant SIMULATION_MODE is set to `true`.
 * 
 * Simulates a remote API response by pausing execution for 1 second, and then
 * generates a simulated response based on the provided action ID.
 *
 * Provides response identical to `generatetext.php`.
 */

// pause execution for 1 second to simulate the remote API response
sleep(SIMULATE_DELAY);

$actionId = $controller;
$output->actionId = $actionId;
$query = $_POST["query"];

$messagesJson = POSTval("messages", "");
if ($messagesJson) {
	$messages = json_decode($messagesJson);
} else {
	$messages = array();
}

$modelId = POSTval("model", "oai");
$model = null;
if ($modelId) {
	switch ($modelId) {
		case "o":
			$model = new ModelO();
			break;
		case "gpt":
			$model = new ModelGpt();
			break;
		case "gem":
			$model = new ModelGemini();
			break;
		case "ttn":
			$model = new ModelTitan();
			break;
		case "claude":
			$model = new ModelClaude();
			break;
		case "llama":
			$model = new ModelLlama();
			break;
		case "deepseek":
			$model = new ModelDeepseek();
			break;
		case "deepseekr":
			$model = new ModelDeepSeekR();
			break;	
	}
	// Record the model that was used
	$output->model = "SIMULATION (".$model->modelName.")";
}

$database = new Database();
$db = $database->getConnection();


$continuity = "";
$categories = "";

if ($actionId == "script") {
	// Get continuity records from the database
	$stmt = $db->prepare("SELECT `continuity`.`id`, `continuity`.`description`, `categories`.`heading` 
						FROM `continuity` 
						JOIN `categories` ON `continuity`.`categoryId` = `categories`.`id`
						WHERE `continuity`.`active` = true");
	// execute the query and loop through the results
	$stmt->execute();
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$continuity .= "".$row['id'].". ".$row['heading'].": ".$row['description'].". ";
	}

	// Get category records from the database
	$stmt = $db->prepare("SELECT * FROM `categories`");
	// execute the query and loop through the results
	$stmt->execute();
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$categories .= $row['id']." - ".$row['description'];
	}
}

$prompts = new Prompts();
$output->prompt = $prompts->generatePrompt($actionId, array($params));


$output->error = "";
$output->data = new stdClass;

switch ($actionId) {
	case "concept":
		array_push($messages, (object) [
			"role" => "developer",
			"content" => "You are a cartoonist and humorist. You are writing a comic strip starring a green zeta reticulan alien named Alpha Zeta.\\nYour comic strip will generally be positive and funny but can be serious when called for.\\nAlpha Zeta's personality includes the following traits:\\n- Cheeky\\n- Curious\\n- Perplexed by Human customs\\nIn each panel of your comic strip, Alpha Zeta will be able to perform only one of the following actions: analysis, angry, approval, creeping, disguised, enamored, explaining, joyous, laughing, reporting, running, santa_claus_costume, scifi_costume, selfie, sitting, standing, startled, teaching, terrified, trick_or_treat, typing, writing"
		]);
		$output->prompt = json_decode("Write a concept for a three panel comic. In this comic strip, Alpha Zeta will engage in the following premise: test.\\nYour concept should be written as a single paragraph. Your concept should include description of the overall story. \\nYour concept should include a breakdown of each panel.\\nOutput your response as a valid json object in the follwing format:\\n{\\n    \\\"concept\\\": \\\"\\\",\\n}");
		array_push($messages, (object) [
			"role" => "user",
			"content" => $output->prompt
		]);
		$response = "{\"concept\":\"In this comic strip, Alpha Zeta is perplexed by the human concept of taking tests and decides to try taking one to better understand it. In the first panel, Alpha Zeta is sitting at a desk with a human-style test paper in front of him, looking curious yet slightly confused as he analyzes the questions on the test. He\\u2019s trying to make sense of why humans would willingly subject themselves to this. In the second panel, we see Alpha Zeta in a classroom with other human students who are diligently taking their tests while Alpha looks increasingly perplexed, scratching his head and doing some analysis on a particularly challenging question. The third panel shows Alpha Zeta standing triumphantly at the front of the classroom, reporting his discovery to the teacher with a big grin: 'I still don't understand why humans do this, but I appreciate the funny answers I came up with! Can we make this a regular event?' The teacher and students laugh at his cheeky interpretation of a test.\", \"memory\": [{\"id\": 1, \"description\": \"This is a test memory\"}, {\"id\": 75, \"description\": \"Da moon\"}]}";
		array_push($messages, (object) [
			"role" => "assistant",
			"content" => $response
		]);
		$output->data = json_decode($response);
		break;
	case "script":
		//$output->data = json_decode("{\"title\": \"A Simulated Comic\",\"panels\": [{\"scene\": \"Panel 1 Scene.\",\"dialog\": \"I'm saying something.\"},{\"scene\": \"Panel 2 Scene.\",\"dialog\": \"I'm saying something else.\"},{\"scene\": \"Panel 3 Scene.\",\"dialog\": \"I'm saying a punch line.\"}]}");
    	//$output->data = json_decode("{\"title\":\"Alpha Zeta Visits the Capitol\",\"panels\":[{\"scene\":\"Panel 1: Alpha Zeta stands in front of the iconic Washington Monument, looking up in awe. His flying saucer is parked conspicuously on the grass nearby.\",\"dialog\":\"I think I found Earth's intergalactic antenna!\"},{\"scene\":\"Panel 2: Alpha Zeta is now at the steps of the U.S. Capitol building, grinning ear to ear with a camera in hand, taking a selfie.\",\"dialog\":\"Perfect place for my new profile pic—politically IN-correct!\"},{\"scene\":\"Panel 3: Alpha Zeta is standing in front of the Lincoln Memorial, mimicking the statue's pose. A couple of tourists nearby are laughing.\",\"dialog\":\"Honest Abe, meet your extraterrestrial twin!\"}],\"memory\":[{\"type\":3,\"description\":\"Washington Monument\"},{\"type\":3,\"description\":\"U.S. Capitol building\"},{\"type\":3,\"description\":\"Lincoln Memorial\"}]}");
		$output->prompt = "Write the script for a three panel comic strip.\\nInclude a detailed scene description and words spoken by the main character.\\nOutput your response as a valid json object in the follwing format:\\n{\\n    \\\"title\\\": \\\"\\\",\\n    \\\"panels\\\": [\\n        {\\n            \\\"scene\\\": \\\"\\\",\\n            \\\"action\\\": \\\"\\\",\\n            \\\"dialog\\\": \\\"\\\"\\n        },\\n        {\\n            \\\"scene\\\": \\\"\\\",\\n            \\\"action\\\": \\\"\\\",\\n            \\\"dialog\\\": \\\"\\\"\\n        },\\n        {\\n            \\\"scene\\\": \\\"\\\",\\n            \\\"action\\\": \\\"\\\",\\n            \\\"dialog\\\": \\\"\\\"\\n        }\\n    ],\\n    \\\"memory\\\" : [],\\n    \\\"newmemory\\\" : []\\n}\\nThe following is a description of each property value for the json object:\\n`title`: The title of the comic strip. Limit to 50 letters.\\n`panels` is a 3 element array of objects defining each of the 3 panels in the comic strip.\\n`scene`: A description of the panel scene, including all characters present.\\n`dialog`: Words spoken by Alpha Zeta. He is the only character that speaks. Do not label the dialog with a character name. This can be an empty string if the character is not speaking.\\n`action`: A word, chosen from the list above, describing the action Alpha Zeta is performing in the panel.\\n`memory`: An array of elements of significance, from the list above, used in the comic. The array will contain only the identifying number for the element of significance.\\n`newmemory`: An array of identified new elements of significance. Output each new element of significance as an object with properties `type`, which is the number of classification from the list above, and `description`, a short description of the element of significance no more than 5 words.";
		array_push($messages, (object) [
			"role" => "user",
			"content" => $output->prompt
		]);
		$response = "{\"title\":\"Alpha Zeta's Test Adventure\",\"panels\":[{\"scene\":\"Alpha Zeta is in a school classroom, sitting at a desk surrounded by human students. The desk is neatly arranged with a test paper and a pencil, while Alpha Zeta appears focused and curious.\",\"action\":\"sitting\",\"dialog\":\"Hmm, so this is a human test. Interesting way to pass the time!\"},{\"scene\":\"Alpha Zeta is now puzzled, analyzing a particularly complex math problem on the test paper. Other students are concentrated on their work, while Alpha Zeta scratches his head and looks perplexed.\",\"action\":\"analysis\",\"dialog\":\"I'm not sure if this problem needs numbers or a magic wand.\"},{\"scene\":\"Alpha Zeta is standing at the front of the classroom, animatedly reporting his experience to the amused teacher and classmates. The room is filled with laughter as they react to Alpha's unexpected enthusiasm.\",\"action\":\"reporting\",\"dialog\":\"I've decided\\u2014human tests should come with popcorn! Can we make this a regular event?\"}],\"summary\":\"Alpha Zeta's Test Adventure\"}";
		array_push($messages, (object) [
			"role" => "assistant",
			"content" => $response
		]);
		$output->data = json_decode($response);
		break;
	case "background":
		//$output->data->descriptions = ["A simulated background.","A simulated background.","A simulated background."];
		$output->prompt = "You are a talented artist who draws background art for animated cartoons.\\nGiven the provided script for the comic strip, for each panel, write a description of the background behind Alpha Zeta.\\nInclude enough detail necessary for an AI image generator to render an image of your description.\\nOutput your response as a valid json object in the follwing format:\\n{\\n    \\\"descriptions\\\": [\\n        \\\"background description 1\\\",\\n        \\\"background description 2\\\",\\n        \\\"background description 3\\\"\\n    ]\\n}\\nYour descriptions will be written within the following rules:\\n- Do not exceed 500 characters for each description.\\n- Describe each scene as it would look if the main character, Alpha Zeta, is not present.\\n- No characters will speak to each other.\\n- Do not include any items that contain readable text.\\n- Do not reference a comic strip panel.";
		array_push($messages, (object) [
			"role" => "user",
			"content" => $output->prompt
		]);
		$response = "{\"descriptions\":[\"A classroom equipped with standard school desks arranged in neat rows. There's a large chalkboard on the front wall, with mathematical formulas and diagrams sketched in white chalk. Sunlight streams through wide-open windows, casting a warm glow. Educational posters and a clock adorn the walls, while a few potted plants add a touch of greenery, creating an inviting learning environment.\",\"The same classroom is filled with students focused on their exam papers, heads down and pencils in hand. Outside the large windows, a few birds can be seen perched on the branches of leaf-laden trees as sunlight casts playful shadows inside. Colorful educational posters line the walls, and a bulletin board by the teacher's desk displays student artwork and announcements. The atmosphere is quiet and studious.\",\"The classroom is alive with laughter and a sense of relief as students celebrate completing their tests. Their desks are slightly messy with scattered pencils and erasers, indicating the flurry of test-taking. The large chalkboard at the front displays encouraging doodles. The windows reveal a bright, sunny day outside, adding joy to the classroom's atmosphere. A wall features a fun poster about teamwork and positivity.\"]}";
		array_push($messages, (object) [
			"role" => "assistant",
			"content" => $response
		]);
		$output->data = json_decode($response);
		break;
	case "action":
		$output->data->panels = json_decode("[{\"action\": \"standing\"},{\"action\": \"typing\",\"altAction\": \"hopeful\"},{\"action\": \"joyous\"}]");
		break;
	case "continuity":
		$output->prompt = "Continuity prompt here.";
		array_push($messages, (object) [
			"role" => "user",
			"content" => $output->prompt
		]);
		$response = "{\"alpha\": [\"Simulated Trait 1\", \"Simulated Trait 2\"], \"event\": [\"Simulated Event 1\",\"Simulated Event 2\",\"Simulated Event 3\"]}";
		array_push($messages, (object) [
			"role" => "assistant",
			"content" => $response
		]);
		$output->data = json_decode($response);
		break;
}

$output->messages = $messages;
$output->json = $output->data;
?>