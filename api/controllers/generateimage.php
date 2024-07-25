<?php
/**
 * Generates an image based on the provided prompt and model.
 * 
 * Uses the POST parameter `model` to determine which model to use for generating the image.
 * Uses the POST parameter `query` to determine the prompt to use for generating the image.
 *
 * @example
	// Response JSON
	{
		"error": "",
		"prompt": "A simulated background.",
		"model": "simulation",
		"data":
			Raw JSON data sent from the API (used for debugging invalid responses generated by the model),
		"json": {
			"url": "a_valid_absolute_url_to_an_image.png"
		}
	}
 */
$modelId = POSTval("model", "sdf");

$query = $_POST["query"];
if(!$query){
	$query = "A grassy knoll";
}

$prompts = new Prompts();
$params = array();
array_push($params, $query);
$output->prompt = $prompts->generatePrompt($controller, $params);
//$output->prompt = $query;

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
			$model = new ModelDallE();
			break;
		case "ttn":
			$model = new ModelTitanImage();
			break;
		case "sdf":
			$model = new ModelStableDiffusion();
			break;
	}
	if (!$model) {
		$output->error = "Invalid model id";
	} else {
		// Record the model that was used
		$output->model = $model->modelName;
	
		$response = $model->sendPrompt($output->prompt);
		$output->error = $response->error;
	
		$output->data = $response->data;
		$output->json = $response->json;
	}
}
?>