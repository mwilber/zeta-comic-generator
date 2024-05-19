<?php
/**
 * Provides functionality for interacting with the Amazon Bedrock Titan API to generate text completions or images.
 */
use Aws\BedrockRuntime\BedrockRuntimeClient;

class ModelTitan {
	function __construct() {
		$this->modelName = "amazon.titan-text-express-v1";
		$this->apiKey = AWS_ACCESS_KEY;
		$this->apiSecret = AWS_SECRET_KEY;
	}

	function sendPrompt($prompt) {
		$result = new stdClass;
		$response = $this->textComplete($this->apiKey, $prompt);
		$json = json_decode($response['body']);
		$result->data = $json;

		$result->error = $json->error;
	
		if(isset($json->results[0]->outputText)) {
	
			$script = trim($json->results[0]->outputText);
			$script = str_replace("\\n", "", $script);
			$script = str_replace("\\r", "", $script);
			$script = str_replace("\\t", "", $script);
			$script = str_replace("```json", "", $script);
			$script = str_replace("tabular-data-json", "", $script);
			$script = str_replace("`", "", $script);
			$jscript = json_decode($script);

			// Titan is wrapping the script in an object with an array `rows`
			// { rows: [ {title:...} ] }
			if (isset($jscript->rows)) {
				$jscript = $jscript->rows[0];
			}
			if (isset($jscript->data)) {
				$jscript = $jscript->data[0];
			}
	
			$result->debug = $script;
			if($jscript) $result->json = $jscript;
		}
		return $result;
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
			'inputText' => $prompt,
			'textGenerationConfig' => [
				'temperature' => 1,
				'maxTokenCount' => 8000
			]
		]);

		$response = $bedrockRuntimeClient->invokeModel([
			'contentType' => 'application/json',
			'body' => $request,
			'modelId' => $this->modelName,
		]);

		return $response;
	}
}

class ModelTitanImage {
	function __construct() {
		$this->modelName = "amazon.titan-image-generator-v1";
		$this->apiKey = AWS_ACCESS_KEY;
		$this->apiSecret = AWS_SECRET_KEY;
		$this->imageSize = 512;
	}

	function sendPrompt($prompt) {
		
		$result = new stdClass;
		$response = $this->textToImage($prompt);
		$json = json_decode($response);
		$result->data = $json;

		$result->error = $json->error;

		$base64_image_data = $result->data->images[0];

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

	function textToImage($prompt) {

		$titanSeed = rand(0, 2147483647);
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
			'taskType' => 'TEXT_IMAGE',
			'textToImageParams' => [
				'text' => $prompt,
			],
			'imageGenerationConfig' => [
				'numberOfImages' => 1,
				'quality' => 'standard',
				'cfgScale' => 8.0,
				'height' => $this->imageSize,
				'width' => $this->imageSize,
				'seed' => $titanSeed
			]
		]);

		$result = $bedrockRuntimeClient->invokeModel([
			'contentType' => 'application/json',
			'body' => $request,
			'modelId' => $this->modelName,
		]);

		return $result['body'];
	}
}
?>