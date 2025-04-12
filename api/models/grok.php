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
class ModelGrok extends BaseModel {
	function __construct() {
		$this->modelName = "grok-2-latest";
		$this->apiUrl = "https://api.x.ai/v1/chat/completions";
		$this->apiKey = GROK_KEY;
	}

	protected function buildRequestBody($messages) {
		$messagesArray = [];
		foreach ($messages as $message) {
			$messagesArray[] = [
				"role" => $message->role === "developer" ? "system" : $message->role,
				"content" => $message->content
			];
		}
		$body = [
			'model' => $this->modelName,
			'response_format' => ['type' => $this->responseFormat],
			'stream' => false,
			'messages' => $messagesArray
		];

		return $body;
	}
}
?>