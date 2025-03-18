<?php
require_once('base_model.php');

/**
 * Provides functionality for interacting with the OpenAI REST API to generate text completions.
 */
class ModelGpt extends BaseModel {
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
}
?>