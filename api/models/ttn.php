<?php
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
?>