<?php
	if(isset($_POST["query"])) {
		$query = $_POST["query"];
	} else {
		// FOR TESTING
		$query = "An explanation of the distance between Earth and the Sun";
	}

	$modelUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=".$GOOGLE_KEY;
	$modelId = "gemini-pro";

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