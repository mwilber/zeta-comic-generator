<?php

	define("OUTPUT_DEBUG_DATA", true);
	//define("OAI_MODEL", "gpt-3.5-turbo-16k");
	//define("OAI_MODEL", "gpt-4");
	define("OAI_MODEL", "gpt-4-1106-preview");

	function gptComplete($key, $prompt) {

		$response = new stdClass;
		$response->data = null;
		$response->script = null;

		$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=AIzaSyCfEfSQqpt73aREIOf0tvyWXcaQmn7TUxg";
	
		$ch = curl_init();
		$headers = array(
			'Content-Type: application/json',
		);
		curl_setopt($ch, CURLOPT_URL, $url);
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

		//echo $body; die;
	
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
			$script = str_replace("`", "", $script);
			$jscript = json_decode($script);

			$response->debug = $script;
	
			if($jscript) $response->json = $jscript;
	
		}

		return $response;
	}

	// function gptComplete($key, $prompt) {

	// 	$response = new stdClass;
	// 	$response->data = null;
	// 	$response->script = null;

	// 	$url = "https://api.openai.com/v1/chat/completions";
	
	// 	$ch = curl_init();
	// 	$headers = array(
	// 		'Authorization: Bearer ' . $key,
	// 		'Content-Type: application/json',
	// 	);
	// 	curl_setopt($ch, CURLOPT_URL, $url);
	// 	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	// 	curl_setopt($ch, CURLOPT_HEADER, 0);
	// 	$body = '{
	// 		"model": "'.OAI_MODEL.'",
    //         "response_format": { "type": "json_object" },
	// 		"messages": [
	// 			{
	// 				"role": "user",
	// 				"content": "'.$prompt.'"
	// 			}
	// 		]
	// 	}';

	// 	//echo $body; die;
	
	// 	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 
	// 	curl_setopt($ch, CURLOPT_POSTFIELDS,$body);
	// 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	// 	// Timeout in seconds
	// 	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	
	// 	$json = curl_exec($ch);
	// 	$data = json_decode($json);
	// 	$response->data = $data;
	
	// 	if(isset($data->choices[0]->message->content)) {
	
	// 		$script = trim($data->choices[0]->message->content);
	// 		$script = str_replace("\\n", "", $script);
	// 		$script = str_replace("\\r", "", $script);
	// 		$script = str_replace("\\t", "", $script);
	// 		$script = str_replace("```json", "", $script);
	// 		$script = str_replace("`", "", $script);
	// 		$jscript = json_decode($script);

	// 		$response->debug = $script;
	
	// 		if($jscript) $response->json = $jscript;
	
	// 	}

	// 	return $response;
	// }
?>