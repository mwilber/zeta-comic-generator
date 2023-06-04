<?php

	define("OUTPUT_DEBUG_DATA", true);

	function add_period($str) {
		$last_char = substr($str, -1);
		if ($last_char !== '.' && $last_char !== '!' && $last_char !== '?') {
		$str .= '.';
		}
		return $str;
	}

	function writePromptLine($prompt, $line) {
		if($line == "") return $prompt;
		$newLine = "";
		if($prompt != "") $newLine = "\\n";

		$newLine .= $line;

		return $prompt . $newLine;
	}

	function generatePrompt($instructions) {

		$prompt = "";

		foreach( $instructions as $instruction ) {
			$prompt = writePromptLine($prompt, $instruction);
		}

		return $prompt;
	}

	function gptComplete($key, $prompt) {

		$response = new stdClass;
		$response->data = null;
		$response->script = null;

		$url = "https://api.openai.com/v1/completions";
	
		$ch = curl_init();
		$headers = array(
			'Authorization: Bearer ' . $key,
			'Content-Type: application/json',
		);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$body = '{
				"model": "text-davinci-003",
				"prompt": "'.$prompt.'",
				"temperature": 0.9,
				"max_tokens": 500,
				"top_p": 1,
				"frequency_penalty": 0.0,
				"presence_penalty": 1.5
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
	
		if(isset($data->choices[0]->text)) {
	
			$script = trim($data->choices[0]->text);
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
?>