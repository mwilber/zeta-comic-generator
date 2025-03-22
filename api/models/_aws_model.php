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

		return $response['body'];
	}

}
?>