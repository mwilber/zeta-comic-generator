<?php
    use Aws\BedrockRuntime\BedrockRuntimeClient;

    if(isset($_POST["query"])) {
        $query = $_POST["query"];
    } else {
        // FOR TESTING
        $query = "An explanation of the distance between Earth and the Sun";
    }

	$prompt = generatePrompt($prompts->script, array(add_period($query)));
    $output->prompt = $prompt;

	$bedrockRuntimeClient = new BedrockRuntimeClient([
		'region' => 'us-east-1',
		'version' => 'latest',
		//'profile' => $profile,
		'credentials' => [
			'key'    => AWS_ACCESS_KEY,
			'secret' => AWS_SECRET_KEY,
		],
	]);

    try {
		$modelId = 'amazon.titan-text-express-v1';

		$request = json_encode([
			'inputText' => $prompt,
			'textGenerationConfig' => [
				'temperature' => 1,
                'maxTokenCount' => 8000
			]
		]);

		$result = $bedrockRuntimeClient->invokeModel([
			'contentType' => 'application/json',
			'body' => $request,
			'modelId' => $modelId,
		]);

		$response_body = json_decode($result['body']);
        $output->data = $response_body;

		if(isset($response_body->results[0]->outputText)) {
	
			$script = trim($response_body->results[0]->outputText);
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

			$output->debug = $script;
	
			if($jscript) $output->json = $jscript;
	
		}

		//$base64_image_data = $response_body->images[0];
	} catch (Exception $e) {
		echo "Error: ({$e->getCode()}) - {$e->getMessage()}\n";
	}

	// Record the model that was used
	$output->model = $modelId;
?>