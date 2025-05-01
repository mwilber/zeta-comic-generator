<?php
require_once('_base_model.php');
/**
 * Provides functionality for interacting with the OpenAI REST API to generate images.
 */
class ModelImagen extends BaseModel {
	function __construct() {
		$this->modelName = "imagen-3.0-generate-002";
		$this->apiKey = GOOGLE_KEY;
		$this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/".$this->modelName.":predict?key=".$this->apiKey;
		$this->imageSize = "1024x1024";
	}

	protected function buildRequestHeaders() {
		return [
			'Content-Type: application/json',
		];
	}

	protected function buildRequestBody($prompt) {
		
		$body = new stdClass;
		$body->parameters = [
			"sampleCount" => 1
		];
		$body->instances = [
			"prompt" => $prompt
		];

		return $body;
	}

	protected function processResponse($response) {
		$result = new stdClass;
		$json = $response;
		$result->data = $json;

		$result->error = $json->error;

		$base64_image_data = $result->data->predictions[0]->bytesBase64Encoded;
		$imagePath = $this->saveImageFromBase64($base64_image_data, $this->modelName);

		$responseObj = new stdClass;
		$responseObj->url = $imagePath;
		$result->json = $responseObj;
		// Clear out the base64 image so it's not stored in the database.
		$response->predictions = [];
		$result->tokens = [
			"image" => 1,
		];

		return $result;
	}

}
?>