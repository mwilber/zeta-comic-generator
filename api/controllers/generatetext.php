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

$prompts = new Prompts();
$modelId = POSTval("model", "oai");
$actionId = $controller;
$messages = GetMessages();

// Get the prompt params for the current action
$params = [];
switch ($actionId) {
	case "concept":
		$paramVal = POSTval("query");
		if ($paramVal) {
			$params[] = addPeriod($paramVal);
		}
		break;
}

// Set up the system prompt if it doesn't already exist
// If $messages is not an empty array, then it was set in the previous request
if (!count($messages)) {
	// Get the system prompt params
	$systemParams = GetSystemPromptParams();
	$messages[] = (object) [
		"role" => "system",
		"content" => $prompts->generatePrompt("system", $systemParams)
	];
}

// Generate the prompt for the current action and add it to the messages array
$output->prompt = $prompts->generatePrompt($actionId, $params);
array_push($messages, (object) [
	"role" => "user",
	"content" => $output->prompt
]);

// Determine if the daily generation limit has been reached
$hitCount = GetHitCount();
if ($hitCount >= RATE_LIMIT) {
    $output->error = "ratelimit";
} elseif ($modelId) {
	$model = GetModel($modelId);

	if (!$model) {
		$output->error = "Invalid model id";
	} else {
		// Record the model that was used
		$output->model = $model->modelName;

		$response = $model->sendPrompt($output->prompt, $messages);
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
				if(!in_array($value->action, array_keys($GLOBALS['characterActions']))) {
					$value->altAction = $oldVal;
					$value->action = "standing";
				}
			}
		}

		$output->json = $response->json;

		// Put the response in the message array
		array_push($messages, (object) [
			"role" => "assistant",
			"content" => json_encode($output->json)
		]);
	}	$output->messages = $messages;
}

if(OUTPUT_DEBUG_DATA) {
	$output->actionId = $actionId;
	$output->params = $params;
}


/**
 * Retrieves the character category from the database.
 *
 * @return array The row from the `categories` table.
 */
function GetCharacterCategory() {
	$database = new Database();
	$db = $database->getConnection();
	$stmt = $db->prepare("SELECT * FROM `categories` WHERE `alias` = 'alpha'");
	$stmt->execute();
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	return $row;
}

/**
 * Retrieves a set of continuity records from the database based on the provided category ID.
 *
 * @param int $categoryId The ID of the category to retrieve continuity records for.
 * @return array An array of associative arrays, where each inner array represents a row from the `continuity` table.
 */
function GetContinuityByCategoryId($categoryId) {
	$database = new Database();
	$db = $database->getConnection();
	$stmt = $db->prepare("SELECT * FROM `continuity` WHERE `categoryId` = :categoryId AND `active` = true");
	$stmt->bindParam(":categoryId", $categoryId, PDO::PARAM_INT);
	$stmt->execute();
	$recordSet = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $recordSet;
}

/**
 * Retrieves the number of records in the `metrics` table for the current date.
 *
 * @return int The number of records in the `metrics` table for the current date.
 */
function GetHitCount() {
	$database = new Database();
	$db = $database->getConnection();
	$stmt = $db->prepare("SELECT COUNT(*) FROM `metrics` WHERE DATE(timestamp) = CURDATE()");
	$stmt->execute();
	return $stmt->fetchColumn();
}

/**
 * Retrieves the messages from the POST request.
 *
 * If the "messages" parameter is present in the POST request, this function
 * decodes the JSON-encoded messages and returns them as an array. If the
 * parameter is not present, an empty array is returned.
 *
 * @return array The messages from the POST request, or an empty array if the
 *               "messages" parameter is not present.
 */
function GetMessages() {
	$messagesJson = POSTval("messages", "");
	if ($messagesJson) {
		return json_decode($messagesJson);
	} else {
		return array();
	}
}

/**
 * Retrieves the appropriate model instance based on the provided model alias.
 *
 * @param string $modelAlias The alias of the model to retrieve.
 * @return object|null The model instance, or null if the model alias is not recognized.
 */
function GetModel($modelAlias) {
	$model = null;
	switch ($modelAlias) {
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
	return $model;
}

/**
 * Retrieves the system prompt parameters based on the provided character actions.
 *
 * @return array The system prompt parameters.
 */
function GetSystemPromptParams() {
	$params = [];
	$characterCategory = GetCharacterCategory();
	$characterContinuity = [];
	if (isset($characterCategory['id'])) {
		$continuityData = GetContinuityByCategoryId($characterCategory['id']);
		foreach ($continuityData as $record) {
			$characterContinuity[] = $record['description'];
		}
	}

	// Set up the parameters for the prompt
	$params[] = "\n" . $characterCategory['prompt'] . "\n - " . implode("\n - ", $characterContinuity) . "\n";
	// Add the character actions, use the $GLOBALS array and convert each key name to a comma-separated string
	$params[] = implode(", ", array_keys($GLOBALS['characterActions']));

	return $params;
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