<?php
require_once('_base_model.php');
/**
 * Provides functionality for interacting with the Google REST API to generate text completions.
 */
class ModelGemini extends BaseModel {
	function __construct() {
		$this->modelName = "gemini-2.0-flash";
		$this->apiKey = GOOGLE_KEY;
		$this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/".$this->modelName.":generateContent?key=".$this->apiKey;
	}

	protected function buildRequestHeaders() {
		return [
			'Content-Type: application/json',
		];
	}

	protected function buildRequestBody($messages) {
		$messagesArray = [];
		$system = "";
		foreach ($messages as $message) {
			// Gemini uses "model" for assistant messages
			if (isset($message->role) && $message->role === "assistant") {
				$message->role = "model";
			}
			// Set the system prompt
			if (
				isset($message->role) &&
				(
					$message->role === "system" ||
					$message->role === "developer") &&
				$system === ""
			) {
				$system = $message->content;
			} else {
				$messagesArray[] = [
					"role" => $message->role,
					"parts" => [
						["text" => $message->content]
					]
				];
			}
		}
		$body = new stdClass;
		$body->system_instruction = [
			"parts" => [
				["text" => $system]
			]
		];
		$body->contents = $messagesArray;

		$body->generationConfig = [
			"response_mime_type" => "application/json"
		];

		return $body;
	}

	protected function processResponse($response) {
		$result = new stdClass;
		$json = $response;
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

		if(isset($json->usageMetadata)) {
			$result->tokens = [
				"prompt_token_count" => $json->usageMetadata->promptTokenCount,
				"candidates_token_count" => $json->usageMetadata->candidatesTokenCount,
			];
		}

		return $result;
	}
}
?>