<?php
require_once('_aws_model.php');
/**
 * Provides functionality for interacting with the Amazon Bedrock StableDiffusion API to generate images.
 */
use Aws\BedrockRuntime\BedrockRuntimeClient;

class ModelStableDiffusion extends BaseAwsModel {
	function __construct() {
		$this->modelName = "stability.stable-diffusion-xl-v1";
		$this->apiKey = AWS_ACCESS_KEY;
		$this->apiSecret = AWS_SECRET_KEY;
		$this->imageSize = 512;
	}

	protected function processResponse($response) {
		$result = new stdClass;
		$json = json_decode($response);
		$result->data = $json;

		$result->error = $json->error;

		$base64_image_data = $result->data->artifacts[0]->base64;

		$saveDir = 'backgrounds';
		$output_dir = '../assets/' . $saveDir . '-full';
		$absolute_path = '/assets/' . $saveDir . '-full';

		if (!file_exists($output_dir)) {
			mkdir($output_dir);
		}

		$i = 1;
		while (file_exists("$output_dir/$modelId" . '_' . "$i.png")) {
			$i++;
		}

		$image_data = base64_decode($base64_image_data);

		// TODO: Send image as url encoded base64 and modify the save script to handle.
		$image_path = "$output_dir/$modelId" . '_' . "$i.png";

		$file = fopen($image_path, 'wb');
		fwrite($file, $image_data);
		fclose($file);

		$responseObj = new stdClass;

		$responseObj->url = "$absolute_path/$modelId" . '_' . "$i.png";
		// // Pass the image as a url encoded base64 string.
		// $responseObj->url = "data:image/png;base64,".$base64_image_data;

		$result->json = $responseObj;

		return $result;
	}

	protected function buildRequestBody($prompt) {

		$titanSeed = rand(0, 2147483647);

		$request = [
			'text_prompts' => [
				['text' => $prompt]
			],
			'cfg_scale' => 10,
			'steps' => 30,
			'height' => $this->imageSize,
			'width' => $this->imageSize,
			'seed' => $titanSeed
		];

		if ($params) {
			$request['style_preset'] = $params;
		}

		return $request;
	}

	protected function sendRequest($headers, $body) {

		$bedrockRuntimeClient = new BedrockRuntimeClient($headers);

		$response = $bedrockRuntimeClient->invokeModel([
			'contentType' => 'application/json',
			'body' => json_encode($body),
			'modelId' => $this->modelName,
		]);

		return $response['body'];
	}
}
?>