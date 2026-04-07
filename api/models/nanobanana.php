<?php
require_once('_base_model.php');

class ModelNanoBanana extends BaseModel {
	function __construct() {
		$this->modelName = "gemini-3.1-flash-image-preview";
		$this->apiKey = GOOGLE_KEY;
		$this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/".$this->modelName.":generateContent";
	}

	protected function buildRequestHeaders() {
		return [
			'Content-Type: application/json',
			'x-goog-api-key: '.$this->apiKey,
		];
	}

	protected function buildRequestBody($prompt) {
		$body = new stdClass;
		$body->contents = [[
			"parts" => [[
				"text" => $prompt
			]]
		]];
		$body->generationConfig = [
			"responseModalities" => ["TEXT", "IMAGE"],
			"imageConfig" => [
				"aspectRatio" => "1:1",
				"imageSize" => "1K",
				"outputMimeType" => "image/png"
			],
		];

		return $body;
	}

	protected function processResponse($response) {
		$result = new stdClass;
		$json = $response;
		$result->data = $json;
		$result->error = isset($json->error) ? $json->error : "";

		$base64_image_data = null;
		if (isset($json->candidates) && is_array($json->candidates)) {
			foreach ($json->candidates as $candidate) {
				if (!isset($candidate->content->parts) || !is_array($candidate->content->parts)) {
					continue;
				}
				foreach ($candidate->content->parts as $part) {
					if (isset($part->inlineData->data) && $part->inlineData->data) {
						$base64_image_data = $part->inlineData->data;
						break 2;
					}
				}
			}
		}

		if ($base64_image_data) {
			$imagePath = $this->saveImageFromBase64($base64_image_data, $this->modelName);
			$responseObj = new stdClass;
			$responseObj->url = $imagePath;
			$result->json = $responseObj;
			// Clear out base64 image data so it is not stored in the database payload.
			if (is_object($response) && isset($response->candidates) && is_array($response->candidates)) {
				foreach ($response->candidates as $candidate) {
					if (!is_object($candidate) || !isset($candidate->content->parts) || !is_array($candidate->content->parts)) {
						continue;
					}
					foreach ($candidate->content->parts as $part) {
						if (is_object($part) && isset($part->inlineData) && is_object($part->inlineData) && isset($part->inlineData->data)) {
							$part->inlineData->data = "";
						}
						if (is_object($part)) {
							foreach (array_keys(get_object_vars($part)) as $partKey) {
								if (strtolower($partKey) === "thoughtsignature") {
									$part->{$partKey} = "";
								}
							}
						}
					}
				}
			}
		} else {
			$result->error = $result->error ?: "No image data returned.";
			$result->json = new stdClass;
		}

		if (isset($json->usageMetadata)) {
			$result->tokens = [
				"prompt_token_count" => $json->usageMetadata->promptTokenCount ?? 0,
				"candidates_token_count" => $json->usageMetadata->candidatesTokenCount ?? 0,
				"total_token_count" => $json->usageMetadata->totalTokenCount ?? 0,
			];
		} else {
			$result->tokens = [
				"image" => 1,
			];
		}

		return $result;
	}
}
?>
