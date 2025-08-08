<?php
require_once('grok.php');
/**
 * Provides functionality for interacting with the Grok 4 Advanced REST API to generate text completions.
 * 
 * Inherits from ModelGrok and uses the same API endpoint but with the grok-4 model.
 */
class ModelGrokAdvanced extends ModelGrok {
	function __construct() {
		parent::__construct();
		$this->modelName = "grok-4";
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
			'stream' => false,
			'messages' => $messagesArray
		];

		return $body;
	}
}
?>