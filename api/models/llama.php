<?php
require_once('_aws_model.php');
/**
 * Provides functionality for interacting with the Amazon Bedrock Llama API to generate text completions.
 */

class ModelLlama extends BaseAwsModel {
	function __construct() {
		parent::__construct();
		$this->modelName = "meta.llama3-70b-instruct-v1:0";
	}

	protected function buildRequestBody($messages) {
		$messagesStr = "<|begin_of_text|>";
		foreach ($messages as $message) {
			$messagesStr .= "<|start_header_id|>".$message->role."<|end_header_id|>".$message->content."<|eot_id|>";
		}
		$messagesStr .= "<|start_header_id|>assistant<|end_header_id|>";

		$request = [
			'prompt' => $messagesStr,
			'max_gen_len' => 1024,
			'temperature' => 0.5,
			'top_p' => 0.9
		];

		return $request;
	}

	function processResponse($response) {
		$result = new stdClass;
		$json = $response;
		$result->data = $json;

		$result->error = $json->error;
	
		if(isset($json->generation)) {
	
			$script = trim($json->generation);
			$script = str_replace("\\n", "", $script);
			$script = str_replace("\\r", "", $script);
			$script = str_replace("\\t", "", $script);
			$script = str_replace("```json", "", $script);
			$script = str_replace("tabular-data-json", "", $script);
			$script = str_replace("`", "", $script);
			$script = $this->extractJsonFromString($script);
			$jscript = json_decode($script);

			if (isset($jscript->data)) {
				$jscript = $jscript->data[0];
			}
	
			$result->debug = $script;
			if($jscript) $result->json = $jscript;
		}

		$result->tokens = [
			"prompt_token_count" => $json->prompt_token_count,
			"generation_token_count" => $json->generation_token_count,
		];

		return $result;
	}

}

?>