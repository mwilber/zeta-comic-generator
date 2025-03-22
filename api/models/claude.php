<?php
require_once('_aws_model.php');
/**
 * Provides functionality for interacting with the Amazon Bedrock Claude API to generate text completions.
 */

class ModelClaude extends BaseAwsModel {
	function __construct() {
		parent::__construct();
		$this->modelName = "anthropic.claude-3-5-sonnet-20240620-v1:0";
	}

	protected function buildRequestBody($messages) {
		$messagesArray = [];
		$system = "";
		foreach ($messages as $message) {
			// Replace "system" role with "developer"
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
					'role' => $message->role,
					'content' => [
					[
						'type' => 'text',
						'text' => $message->content
					]
					]
				];
			}
		}
		$body = json_encode([
			'anthropic_version' => 'bedrock-2023-05-31',
			'max_tokens' => 1000,
			'system' => $system,
			'messages' => $messagesArray,
		]);

		// print_r($request); die;

		return $body;
	}
}

?>