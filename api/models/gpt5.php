<?php
require_once('gpt.php');

/**
 * Provides functionality for interacting with the OpenAI GPT-5 model.
 * Inherits from ModelGpt and uses the gpt-5 model.
 */
class ModelGpt5 extends ModelGpt {
	function __construct() {
		parent::__construct();
		$this->modelName = "gpt-5";
		$this->apiUrl = "https://api.openai.com/v1/responses";
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
			//'response_format' => ['type' => $this->responseFormat],
			'stream' => false,
			'reasoning' => [
				'effort' => 'medium'
			],
			'text' => [
				'verbosity' => 'medium',
			],
			'input' => $messagesArray
		];

		return $body;
	}

	protected function processResponse($response) {
		$result = new stdClass;
		$json = $response;
		$result->data = $json;

		$result->error = $json->error;

		$output = null;
		if (isset($json->output) && is_array($json->output)) {
			foreach ($json->output as $item) {
				if (is_object($item) && isset($item->content)) {
					$output = $item->content;
					break;
				}
			}
		}

		// If $output is not null, process it
		if ($output !== null) {
			$script = trim($output[0]->text);
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
}
?>