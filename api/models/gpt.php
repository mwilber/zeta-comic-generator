<?php
require_once('_base_model.php');

/**
 * Provides functionality for interacting with the OpenAI REST API to generate text completions.
 */
class ModelGpt extends BaseModel {
	function __construct() {
		// $this->modelName = "gpt-3.5-turbo-16k";
		// $this->modelName = "gpt-4";
		// $this->modelName = "gpt-4-1106-preview";
		// $this->modelName = "gpt-4o-2024-05-13";
		// $this->modelName = "gpt-4o-2024-08-06";
		// $this->modelName = "gpt-4o-mini-2024-07-18";
		// $this->modelName = "gpt-4.1-2025-04-14";
		// Concept generation uses the latest Astra model with low reasoning effort.
		$this->modelName = "gpt-6-astra";

		$this->apiUrl = "https://api.openai.com/v1/responses";
		$this->apiKey = OPENAI_KEY;
	}

	protected function buildRequestBody($messages) {
		$messagesArray = [];
		foreach ($messages as $message) {
			$messagesArray[] = [
				// Responses API accepts developer instructions; preserve that role rather
				// than downgrading the application's system prompt.
				"role" => $message->role === "system" ? "developer" : $message->role,
				"content" => $message->content
			];
		}

		return [
			'model' => $this->modelName,
			'stream' => false,
			'reasoning' => [
				'effort' => 'low'
			],
			'text' => [
				'verbosity' => 'medium',
			],
			'input' => $messagesArray
		];
	}
}
