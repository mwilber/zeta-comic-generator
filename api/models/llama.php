<?php
/**
 * Provides functionality for interacting with the Amazon Bedrock Claude API to generate text completions.
 */
use Aws\BedrockRuntime\BedrockRuntimeClient;

class ModelLlama {
	function __construct() {
		$this->modelName = "meta.llama3-70b-instruct-v1:0";
		$this->apiKey = AWS_ACCESS_KEY;
		$this->apiSecret = AWS_SECRET_KEY;
	}

	function sendPrompt($prompt) {
		$result = new stdClass;
		$response = $this->textComplete($this->apiKey, $prompt);
		$json = json_decode($response['body']);
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
		return $result;
	}

	function extractJsonFromString($input) {
		// Regular expression to find JSON objects in the string
		$pattern = '/\{(?:[^{}]|(?R))*\}/';
	
		// Perform the regular expression match
		if (preg_match($pattern, $input, $matches)) {
			$jsonString = $matches[0];
			return $jsonString;
		} else {
			// Return the original string if no JSON object is found
			return $input;
		}
	}

	function textComplete($key, $prompt) {

		$bedrockRuntimeClient = new BedrockRuntimeClient([
			'region' => 'us-east-1',
			'version' => 'latest',
			//'profile' => $profile,
			'credentials' => [
				'key'    => $this->apiKey,
				'secret' => $this->apiSecret,
			],
		]);

		$request = json_encode([
			'prompt' => '<|begin_of_text|><|start_header_id|>user<|end_header_id|>'.$prompt.'<|eot_id|><|start_header_id|>assistant<|end_header_id|>',
			'max_gen_len' => 1024,
			'temperature' => 0.5,
			'top_p' => 0.9
		]);

		$response = $bedrockRuntimeClient->invokeModel([
			'contentType' => 'application/json',
			'accept' => 'application/json',
			'body' => $request,
			'modelId' => $this->modelName,
		]);

		return $response;
	}
}

?>