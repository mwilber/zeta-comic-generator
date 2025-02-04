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
sleep(1);

$actionId = $controller;
$query = $_POST["query"];

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
$output->prompt = $prompts->generatePrompt($actionId, array($params), array($continuity, $categories));

// Record the model that was used
$output->model = "simulation";
$output->error = "";

$output->data = new stdClass;

switch ($actionId) {
	case "script":
		//$output->data = json_decode("{\"title\": \"A Simulated Comic\",\"panels\": [{\"scene\": \"Panel 1 Scene.\",\"dialog\": \"I'm saying something.\"},{\"scene\": \"Panel 2 Scene.\",\"dialog\": \"I'm saying something else.\"},{\"scene\": \"Panel 3 Scene.\",\"dialog\": \"I'm saying a punch line.\"}]}");
    	$output->data = json_decode("{\"title\":\"Alpha Zeta Visits the Capitol\",\"panels\":[{\"scene\":\"Panel 1: Alpha Zeta stands in front of the iconic Washington Monument, looking up in awe. His flying saucer is parked conspicuously on the grass nearby.\",\"dialog\":\"I think I found Earth's intergalactic antenna!\"},{\"scene\":\"Panel 2: Alpha Zeta is now at the steps of the U.S. Capitol building, grinning ear to ear with a camera in hand, taking a selfie.\",\"dialog\":\"Perfect place for my new profile pic—politically IN-correct!\"},{\"scene\":\"Panel 3: Alpha Zeta is standing in front of the Lincoln Memorial, mimicking the statue's pose. A couple of tourists nearby are laughing.\",\"dialog\":\"Honest Abe, meet your extraterrestrial twin!\"}],\"memory\":[{\"type\":3,\"description\":\"Washington Monument\"},{\"type\":3,\"description\":\"U.S. Capitol building\"},{\"type\":3,\"description\":\"Lincoln Memorial\"}]}");
		break;
	case "background":
		$output->data->descriptions = ["A simulated background.","A simulated background.","A simulated background."];
		break;
	case "action":
		$output->data->panels = json_decode("[{\"action\": \"standing\"},{\"action\": \"typing\",\"altAction\": \"hopeful\"},{\"action\": \"joyous\"}]");
		break;
}

$output->json = $output->data;
?>