<?php
/**
 * Provides functionality for interacting with the Google REST API to generate text completions.
 */
class ModelGemini {
	function __construct() {
		$this->modelName = "gemini-1.5-pro";
		$this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/".$this->modelName.":generateContent?key=";
		$this->apiKey = GOOGLE_KEY;
	}

	function sendPrompt($prompt) {
		$result = new stdClass;
		$response = $this->textComplete($prompt);
		$json = json_decode($response);
		$result->data = $json;

		$result->error = $json->error;
	
		if(isset($json->candidates[0]->content->parts[0]->text)) {
			$script = trim($json->candidates[0]->content->parts[0]->text);
			$script = str_replace("\\n", "", $script);
			$script = str_replace("\\r", "", $script);
			$script = str_replace("\\t", "", $script);
			$script = str_replace("```json", "", $script);
			$script = str_replace("json", "", $script);
			$script = str_replace("JSON", "", $script);
			$script = str_replace("`", "", $script);
			$jscript = json_decode($script);
	
			$result->debug = $script;
			if($jscript) $result->json = $jscript;
		}
		return $result;
	}

	function textComplete($prompt) {

		$modelUrl = $this->apiUrl.$this->apiKey;

		$ch = curl_init();
		$headers = array(
			'Content-Type: application/json',
		);
		curl_setopt($ch, CURLOPT_URL, $modelUrl);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$body = '{
			"contents": [{
				"parts": [
					{
						"text": "'.$prompt.'"
					}
				]
			}]
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