<?php
    use Aws\BedrockRuntime\BedrockRuntimeClient;

    if(isset($_POST["query"])) {
        $query = $_POST["query"];
    } else {
        // FOR TESTING
        $query = "An explanation of the distance between Earth and the Sun";
    }

    $instructions = array(
        "You are a cartoonist and humorist. Write the script for a three panel comic strip.",
		"In the comic strip our main character, a short green humaniod alien named Alpha Zeta, engages in the following premise: ",
		add_period($query),
		"Include a detailed scene description and words spoken by the main character.",
		//"The description is written as a json object, describing the content that makes up the comic strip.", 
		"Write your script in the form of a json object. The json object has the following properties: `title` and `panels`.",
		"The following is a description of each property value:",
		"`title`: The title of the comic strip. Limit to 50 letters.",
		"`panels` is an array of objects with the following properties: `scene` and `dialog`",
		"`scene`: A description of the panel scene including all characters.",
		"`dialog`: Words spoken by Alpha Zeta. He is the only character that speaks so there is no need to label with a name. This can be an empty string if the character is not speaking.",
	);

	$prompt = generatePrompt($instructions);

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

?>