<?php
require_once('_base_model.php');
/**
 * Provides functionality for interacting with the OpenAI REST API to generate images.
 */
class ModelDallE extends BaseModel {
	function __construct() {
		$this->modelName = "dall-e-3";
		$this->apiUrl = "https://api.openai.com/v1/images/generations";
		$this->apiKey = OPENAI_KEY;
		$this->imageSize = "1024x1024";
	}

	protected function buildRequestBody($prompt) {
		$body = [
			"model" => $this->modelName,
			"prompt" => $prompt,
			"n" => 1,
			"size" => $this->imageSize
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