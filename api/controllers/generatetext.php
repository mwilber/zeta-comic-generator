<?php
define("OUTPUT_DEBUG_DATA", true);

$modelId = POSTval("model", "oai");
$actionId = $controller;

$characterActions = [
	"angry",
	"approval",
	"creeping",
	"disguised",
	"enamored",
	"explaining",
	"joyous",
	"running",
	"santa_claus_costume",
	"scifi_costume",
	"selfie",
	"sitting",
	"standing",
	"startled",
	"teaching",
	"terrified",
	"typing"
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

if ($modelId) {
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
	}

	if (!$model) {
		$output->error = "Invalid model id";
	} else {
		// Record the model that was used
		$output->model = $model->modelName;

		$response = $model->sendPrompt($output->prompt);
		$output->error = $response->error;

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
?>