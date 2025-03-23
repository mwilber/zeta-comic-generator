<?php
require_once('_base_model.php');
/**
 * Provides functionality for interacting with the OpenAI REST API to generate text completions.
 */
class ModelDeepSeek extends BaseModel {
	function __construct() {
		$this->modelName = "deepseek-chat";
		$this->apiUrl = "https://api.deepseek.com/chat/completions";
		$this->apiKey = DEEPSEEK_KEY;
	}

	protected function buildRequestBody($messages) {
		$messagesArray = [];
		foreach ($messages as $message) {
			$messagesArray[] = [
				"role" => $message->role,
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