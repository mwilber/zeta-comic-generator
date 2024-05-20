<?php
/**
 * Provides functionality for interacting with the OpenAI REST API to generate images.
 */
class ModelDallE {
	function __construct() {
		$this->modelName = "dall-e-3";
		$this->apiUrl = "https://api.openai.com/v1/images/generations";
		$this->apiKey = OPENAI_KEY;
		$this->imageSize = "1024x1024";
	}

	function sendPrompt($prompt) {
		
		$result = new stdClass;
		$response = $this->textToImage($prompt);
		$json = json_decode($response);
		$result->data = $json;

		$result->error = $json->error;

		if (isset($json->data[0])) {
			$result->json = $json->data[0];
		}

		return $result;
	}

	function textToImage($prompt) {

		$ch = curl_init();
		$headers = array(
			'Authorization: Bearer ' . $this->apiKey,
			'Content-Type: application/json',
		);
		curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$body = '{
			"model": "'.$this->modelName.'",
			"prompt": "'.$prompt.'",
			"n": 1,
			"size": "'.$this->imageSize.'"
		}';

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
		curl_setopt($ch, CURLOPT_POSTFIELDS,$body);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Timeout in seconds
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	
		$response = curl_exec($ch);

		return $response;
	}
}
?>