<?php
require_once('_base_model.php');
/**
 * Provides functionality for interacting with the OpenAI REST API to generate images.
 */
class ModelGptImage extends BaseModel {
	function __construct() {
		$this->modelName = "gpt-image-1.5";
		$this->apiUrl = "https://api.openai.com/v1/images/generations";
		$this->apiKey = OPENAI_KEY;
		$this->imageSize = "1024x1024";
	}

	protected function buildRequestBody($prompt) {
		$body = [
			"model" => $this->modelName,
			"prompt" => $prompt,
			"n" => 1,
			"size" => $this->imageSize,
			"quality" => "low"
		];

		return $body;
	}

	// protected function processResponse($response) {
	// 	$result = new stdClass;
	// 	$json = $response;
	// 	$result->data = $json;

	// 	$result->error = $json->error;

	// 	if (isset($json->data[0])) {
	// 		$result->json = $json->data[0];
	// 	}

	// 	$result->tokens = [
	// 		"image" => 1,
	// 	];

	// 	return $result;
	// }

	protected function processResponse($response) {
		$result = new stdClass;
		$json = $response;
		$result->data = $json;

		$result->error = $json->error;

		// print_r($response);
		// die;

		$base64_image_data = $result->data->data[0]->b64_json;
		$imagePath = $this->saveImageFromBase64($base64_image_data, $this->modelName);

		$responseObj = new stdClass;
		$responseObj->url = $imagePath;
		$result->json = $responseObj;
		// Clear out the base64 image so it's not stored in the database.
		// If $response is an object, clear out the base64 data
		if (is_object($response) && isset($response->data) && is_array($response->data)) {
			foreach ($response->data as $item) {
				if (is_object($item) && isset($item->b64_json)) {
					$item->b64_json = null;
				}
			}
		}
		$result->tokens = [
			"image" => 1,
		];

		return $result;
	}

}
?>
