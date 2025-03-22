<?php
/**
 * Provides functionality for interacting with the Grok REST API to generate text completions.
 * 
 * curl https://api.x.ai/v1/chat/completions -H "Content-Type: application/json" -H "Authorization: Bearer GROK_KEY" -d '{
  "messages": [
    {
      "role": "system",
      "content": "You are a test assistant."
    },
    {
      "role": "user",
      "content": "Testing. Just say hi and hello world and nothing else."
    }
  ],
  "model": "grok-2-latest",
  "stream": false,
  "temperature": 0
}'
 */
class ModelGrok {
	function __construct() {
		$this->modelName = "grok-2-latest";

		$this->apiUrl = "https://api.x.ai/v1/chat/completions";
		$this->apiKey = GROK_KEY;
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
			$messagesArray[] = [
				"role" => $message->role,
				"content" => $message->content
			];
		}
		$body = '{
			"model": "'.$this->modelName.'",
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