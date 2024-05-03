<?php
	if(isset($_POST["query"])) {
		$query = $_POST["query"];
	} else {
		// FOR TESTING
		$query = "An explanation of the distance between Earth and the Sun";
	}

	$modelUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=".$GOOGLE_KEY;
	$modelId = "gemini-pro";

	$prompt = generatePrompt($prompts->script, array(add_period($query)));
	//$response = gptComplete($OPENAI_KEY, $prompt);

	$response = new stdClass;
	$response->data = null;
	$response->script = null;

	$ch = curl_init();
	$headers = array(
		'Content-Type: application/json',
	);
	curl_setopt($ch, CURLOPT_URL, $modelUrl);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	$body = '{
		"contents": [{
			"parts": [
				{
					"text": "'.$prompt.'"
				}
			]
		}]
	}';

	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
	curl_setopt($ch, CURLOPT_POSTFIELDS,$body);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	// Timeout in seconds
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);

	$json = curl_exec($ch);
	$data = json_decode($json);
	$response->data = $data;

	if(isset($data->candidates[0]->content->parts[0]->text)) {
		$script = trim($data->candidates[0]->content->parts[0]->text);
		$script = str_replace("\\n", "", $script);
		$script = str_replace("\\r", "", $script);
		$script = str_replace("\\t", "", $script);
		$script = str_replace("```json", "", $script);
		$script = str_replace("json", "", $script);
		$script = str_replace("JSON", "", $script);
		$script = str_replace("`", "", $script);
		$jscript = json_decode($script);

		$response->debug = $script;

		if($jscript) $response->json = $jscript;
	}
	
	if(isset($response->data->error)) $output->error = $response->data->error;

	if(OUTPUT_DEBUG_DATA) {
		$output->data = $response->data;
		$output->debug = $response->debug;
	}

	$output->json = $response->json;

	// Record the model that was used
	$output->model = $modelId;
?>