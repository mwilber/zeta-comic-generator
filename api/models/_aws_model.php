<?php
require_once('_base_model.php');
/**
 * Provides functionality for interacting with the Amazon Bedrock API to generate text completions.
 */
use Aws\BedrockRuntime\BedrockRuntimeClient;

class BaseAwsModel extends BaseModel {
	function __construct() {
		$this->apiKey = AWS_ACCESS_KEY;
		$this->apiSecret = AWS_SECRET_KEY;
	}

	protected function buildRequestHeaders() {
		return [
			'region' => 'us-east-1',
			'version' => 'latest',
			//'profile' => $profile,
			'credentials' => [
				'key'    => $this->apiKey,
				'secret' => $this->apiSecret,
			],
		];
	}

	protected function sendRequest($headers, $body) {

		$bedrockRuntimeClient = new BedrockRuntimeClient($headers);

		$response = $bedrockRuntimeClient->invokeModel([
			'contentType' => 'application/json',
			'body' => $body,
			'modelId' => $this->modelName,
		]);

		return $response;
	}

	protected function processResponse($response) {
		$result = new stdClass;
		$json = json_decode($response['body']);
		$result->data = $json;

		$result->error = $json->error;
	
		if(isset($json->content[0]->text)) {
	
			$script = trim($json->content[0]->text);
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

}
?>