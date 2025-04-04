<?php
/**
 * Provides functionality for interacting with the OpenAI REST API to generate text completions.
 */
class ModelGpt {
	function __construct() {
		// $this->modelName = "gpt-3.5-turbo-16k";
		// $this->modelName = "gpt-4";
		// $this->modelName = "gpt-4-1106-preview";
		// $this->modelName = "gpt-4o-2024-05-13";
		$this->modelName = "gpt-4o-2024-08-06";
		// $this->modelName = "gpt-4o-mini-2024-07-18";

		$this->apiUrl = "https://api.openai.com/v1/chat/completions";
		$this->apiKey = OPENAI_KEY;
	}

	function sendPrompt($prompt, $messages) {
		
		$result = new stdClass;
		$response = $this->textComplete($this->apiKey, $messages);
		$json = json_decode($response);
		$result->data = $json;

		$result->error = $json->error;

		if(isset($json->choices[0]->message->content)) {
			$script = trim($json->choices[0]->message->content);
			$script = str_replace("\\n", "", $script);
			$script = str_replace("\\r", "", $script);
			$script = str_replace("\\t", "", $script);
			$script = str_replace("```json", "", $script);
			$script = str_replace("`", "", $script);
			$jscript = json_decode($script);

			$result->debug = $script;
			if($jscript) $result->json = $jscript;
		}
		return $result;
	}

	function textComplete($key, $messages) {

		$response = new stdClass;
		$response->data = null;
		$response->script = null;
	
		$ch = curl_init();
		$headers = array(
			'Authorization: Bearer ' . $this->apiKey,
			'Content-Type: application/json',
		);
		curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$messagesArray = [];
		foreach ($messages as $message) {
			// Replace "system" role with "developer"
			if (isset($message->role) && $message->role === "system") {
				$message->role = "developer";
			}
			$messagesArray[] = [
				"role" => $message->role,
				"content" => $message->content
			];
		}
		$body = '{
			"model": "'.$this->modelName.'",
			"response_format": { "type": "json_object" },
			"messages": ' . json_encode($messagesArray) . '
		}';

		// echo $body; die;
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