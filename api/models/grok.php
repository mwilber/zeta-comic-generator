<?php
/**
 * Provides functionality for interacting with the xAI Responses API using Grok 4.6.
 */
class ModelGrok extends BaseModel {
	protected $reasoningEffort = "low";

	function __construct() {
		$this->modelName = "grok-4.6";
		$this->apiUrl = "https://api.x.ai/v1/responses";
		$this->apiKey = GROK_KEY;
		$this->requestTimeout = 3600;
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
			'reasoning' => ['effort' => $this->reasoningEffort],
			'input' => $messagesArray,
			'text' => ['format' => ['type' => $this->responseFormat]]
		];

		return $body;
	}
}
?>
