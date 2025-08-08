<?php
require_once('_base_model.php');
/**
 * Provides functionality for interacting with the Grok REST API to generate images.
 */
class ModelGrokImage extends BaseModel {
	function __construct() {
		$this->modelName = "grok-2-image";
		$this->apiUrl = "https://api.x.ai/v1/images/generations";
		$this->apiKey = GROK_KEY;
		$this->imageSize = "1024x1024";
	}

	protected function buildRequestBody($prompt) {
		$body = [
			"model" => $this->modelName,
			"prompt" => $prompt,
			"n" => 1,
			"response_format" => "url",
			//"size" => $this->imageSize,
		];

		return $body;
	}

	protected function processResponse($response) {
		$result = new stdClass;
		$json = $response;
		$result->data = $json;

		$result->error = $json->error;

		if (isset($json->data[0])) {
			$result->json = $json->data[0];
		}

		$result->tokens = [
			"image" => 1,
		];

		return $result;
	}

}
?>