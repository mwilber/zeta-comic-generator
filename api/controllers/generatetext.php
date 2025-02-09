<?php
/**
 * Generates a text response based on the provided prompt and model.
 * 
 * Expects the variable $actionId to be set to the ID of the prompt to use for generating the text.
 * 
 * Uses the POST parameter `model` to determine which model to use for generating the text.
 * Uses the following POST parameters to insert into the text generation prompt:
 * - `query` : The premise of a comic strip.
 * - `panel1 : A description of the first panel in a comic strip.
 * - `panel2 : A description of the second panel in a comic strip.
 * - `panel3 : A description of the third panel in a comic strip.
 * 
 * @example
	// Response JSON
	{
		"error": "",
		"prompt": null,
		"model": "simulation",
		"data": 
			Raw JSON data sent from the API (used for debugging invalid responses generated by the model),
		"json": {
			JSON data sent from the API formatted for the front-end.
		}
	}
 */
define("OUTPUT_DEBUG_DATA", true);

$modelId = POSTval("model", "oai");
$actionId = $controller;

$characterActions = [
	"analysis",
	"angry",
	"approval",
	"creeping",
	"disguised",
	"enamored",
	"explaining",
	"joyous",
	"laughing",
	"reporting",
	"running",
	"santa_claus_costume",
	"scifi_costume",
	"selfie",
	"sitting",
	"standing",
	"startled",
	"teaching",
	"terrified",
	"trick_or_treat",
	"typing",
	"writing"
];
$paramNames = [
	"query",
	"panel1",
	"panel2",
	"panel3"
];

$params = array();
foreach ($paramNames as $paramName) {
	$paramVal = POSTval($paramName);
	if ($paramVal) {
		if ($paramName == "query") $paramVal = addPeriod($paramVal);
		array_push($params, $paramVal);
	}
}
if ($actionId == "action") array_push($params, implode(", ", $characterActions));

// Get the prompt
$prompts = new Prompts();
if(OUTPUT_DEBUG_DATA) {
	$output->actionId = $actionId;
	$output->params = $params;
}
$output->prompt = $prompts->generatePrompt($actionId, $params);

// Determine if the daily generation limit has been reached
$database = new Database();
$db = $database->getConnection();
// Fetch the number of records in the table for the current date
$stmt = $db->prepare("SELECT COUNT(*) FROM `metrics` WHERE DATE(timestamp) = CURDATE()");
$stmt->execute();
$hitCount = $stmt->fetchColumn();

if ($hitCount >= RATE_LIMIT) {
    $output->error = "ratelimit";
} elseif ($modelId) {
	$model = null;
	switch ($modelId) {
		case "oai":
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
	}

	if (!$model) {
		$output->error = "Invalid model id";
	} else {
		// Record the model that was used
		$output->model = $model->modelName;

		$response = $model->sendPrompt($output->prompt);
		$output->error = $response->error;

		countHit($actionId, $params[0]);

		if(OUTPUT_DEBUG_DATA) {
			$output->debug = $response->debug;
			$output->data = $response->data;
		}

		// Ensure a valid character action
		if ($actionId == "action" && is_array($response->json->panels)) {
			foreach($response->json->panels as &$value) {
				$oldVal = $value;
				$value = new stdClass;
				$value->action = $oldVal;
				if(!in_array($value->action, $characterActions)) {
					$value->altAction = $oldVal;
					$value->action = "standing";
				}
			}
		}

		$output->json = $response->json;
	}
}

function addPeriod($str) {
	$last_char = substr($str, -1);
	if ($last_char !== '.' && $last_char !== '!' && $last_char !== '?') {
	$str .= '.';
	}
	return $str;
}

function countHit($action, $premise) {
	if ($action != "script") return;
	// Store a record in metrics table
	$database = new Database();
	$db = $database->getConnection();

	$stmt = $db->prepare("INSERT INTO `metrics` (`premise`) VALUES (".$db->quote($_POST["query"]).");");
	// execute query
	$stmt->execute();
}
?>