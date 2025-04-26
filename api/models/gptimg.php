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
			"quality" => "low"
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