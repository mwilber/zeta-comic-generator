<?php
require_once('_base_model.php');
/**
 * Provides functionality for interacting with the OpenAI REST API to generate images.
 */
class ModelGptImage extends BaseModel {
	public $imageSize;

	function __construct() {
		$this->modelName = "gpt-image-2";
		$this->apiUrl = "https://api.openai.com/v1/images/generations";
		$this->apiKey = OPENAI_KEY;
		$this->imageSize = "1024x1024";
		$this->requestTimeout = 120;
	}

	protected function buildRequestBody($prompt) {
		$body = [
			"model" => $this->modelName,
			"prompt" => $prompt,
			"n" => 1,
			"size" => $this->imageSize,
			"quality" => "low",
			"background" => "opaque",
			"output_format" => "png"
		];

		return $body;
	}

	protected function processResponse($response) {
		$result = new stdClass;
		$json = $response;
		$result->data = $json;

		$result->error = $json->error ?? null;

		if($result->error) {
			return $result;
		}

		$responseObj = new stdClass;

		$imageUrl = $this->extractImageUrlFromResponse($json);
		if($imageUrl) {
			$responseObj->url = $imageUrl;
			$result->json = $responseObj;
			$result->tokens = [
				"image" => 1,
			];
			return $result;
		}

		$base64_image_data = $this->extractBase64ImageFromResponse($json);
		if(!$base64_image_data) {
			$result->error = "No image returned by model.";
			return $result;
		}

		$imagePath = $this->saveImageFromBase64($base64_image_data, $this->modelName);

		$responseObj->url = $imagePath;
		$result->json = $responseObj;
		// Clear out the base64 image so it's not stored in the database.
		$this->clearBase64ImageData($response);
		$result->tokens = [
			"image" => 1,
		];

		return $result;
	}

	protected function extractBase64ImageFromResponse($json) {
		if(!is_object($json)) return null;

		if(isset($json->data) && is_array($json->data)) {
			foreach($json->data as $item) {
				if(is_object($item) && isset($item->b64_json) && is_string($item->b64_json) && trim($item->b64_json) !== "") {
					return trim($item->b64_json);
				}
			}
		}

		if(isset($json->output) && is_array($json->output)) {
			foreach($json->output as $item) {
				if(is_object($item) && isset($item->type) && $item->type === "image_generation_call" && isset($item->result) && is_string($item->result) && trim($item->result) !== "") {
					return trim($item->result);
				}
			}
		}

		return null;
	}

	protected function extractImageUrlFromResponse($json) {
		if(!is_object($json) || !isset($json->data) || !is_array($json->data)) return null;

		foreach($json->data as $item) {
			if(is_object($item) && isset($item->url) && is_string($item->url) && $item->url !== "") {
				return $item->url;
			}
		}

		return null;
	}

	protected function clearBase64ImageData($response) {
		if(!is_object($response)) return;

		if(isset($response->data) && is_array($response->data)) {
			foreach($response->data as $item) {
				if(is_object($item) && isset($item->b64_json)) {
					$item->b64_json = null;
				}
			}
		}

		if(isset($response->output) && is_array($response->output)) {
			foreach($response->output as $item) {
				if(is_object($item) && isset($item->type) && $item->type === "image_generation_call" && isset($item->result)) {
					$item->result = null;
				}
			}
		}
	}

}
?>
