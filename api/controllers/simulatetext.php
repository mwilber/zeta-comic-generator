<?php
// pause execution for 1 second to simulate the remote API response
sleep(1);

$actionId = $controller;
$query = $_POST["query"];
$output->prompt = $query;
// Record the model that was used
$output->model = "simulation";
$output->error = "";

$output->data = new stdClass;

switch ($actionId) {
    case "script":
        $output->data = json_decode("{\"title\": \"A Simulated Comic\",\"panels\": [{\"scene\": \"Panel 1 Scene.\",\"dialog\": \"I'm saying something.\"},{\"scene\": \"Panel 2 Scene.\",\"dialog\": \"I'm saying something else.\"},{\"scene\": \"Panel 3 Scene.\",\"dialog\": \"I'm saying a punch line.\"}]}");
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