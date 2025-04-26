<?php
/**
 * Provides functionality for interacting with the OpenAI REST API to generate images.
 */
class ModelGptImage extends ModelDallE {
	function __construct() {
		parent::__construct();
		$this->modelName = "gpt-image-1";
	}

	protected function buildRequestBody($prompt) {
		$body = [
			"model" => $this->modelName,
			"prompt" => $prompt,
			"n" => 1,
			"size" => $this->imageSize,
			"quality" => "medium"
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

		$base64_image_data = $result->data->data[0]->b64_json;
		$imagePath = $this->saveImageFromBase64($base64_image_data, $this->modelName);

		$responseObj = new stdClass;
		$responseObj->url = $imagePath;
		$result->json = $responseObj;
		// Clear out the base64 image so it's not stored in the database.
		$response->data = [];

		if(isset($json->usage)) {
			$result->tokens = [
				"input_tokens" => $json->usage->input_tokens,
				"output_tokens" => $json->usage->output_tokens,
			];
		}

		return $result;
	}
}
?>