<?php
	use Aws\BedrockRuntime\BedrockRuntimeClient;

	$bedrockRuntimeClient = new BedrockRuntimeClient([
		'region' => 'us-east-1',
		'version' => 'latest',
		//'profile' => $profile,
		'credentials' => [
			'key'    => AWS_ACCESS_KEY,
			'secret' => AWS_SECRET_KEY,
		],
	]);

	$image_prompt = 'Create an image of an intergalactic villain\'s lair filled with advanced technology and multiple screens displaying different landscapes of Earth, including forests, cities, and oceans. Include a large red button on a central console.';
	$titanSeed = rand(0, 2147483647);
	//$base64 = invokeTitanImage($image_prompt, $titanSeed);
	$base64_image_data = "";

	try {
		$modelId = 'amazon.titan-image-generator-v1';

		$request = json_encode([
			'taskType' => 'TEXT_IMAGE',
			'textToImageParams' => [
				'text' => $image_prompt
			],
			'imageGenerationConfig' => [
				'numberOfImages' => 1,
				'quality' => 'standard',
				'cfgScale' => 8.0,
				'height' => 512,
				'width' => 512,
				'seed' => $titanSeed
			]
		]);

		$result = $bedrockRuntimeClient->invokeModel([
			'contentType' => 'application/json',
			'body' => $request,
			'modelId' => $modelId,
		]);

		$response_body = json_decode($result['body']);

		print_r($response_body);

		$base64_image_data = $response_body->images[0];
	} catch (Exception $e) {
		echo "Error: ({$e->getCode()}) - {$e->getMessage()}\n";
	}

	$output->data = $base64_image_data;
?>