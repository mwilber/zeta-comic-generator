<?php
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);


	function extractJsonFromString($input) {
		// Regular expression to find JSON objects in the string
		$pattern = '/\{(?:[^{}]|(?R))*\}/';
	
		// Perform the regular expression match
		if (preg_match($pattern, $input, $matches)) {
			$jsonString = $matches[0];
			return $jsonString;
		} else {
			// Return the original string if no JSON object is found
			return $input;
		}
	}


	$jsondata = "\n\n{\n    \"panels\": [\n        \"sitting\",\n        \"standing\",\n        \"frustrated\"\n    ]\n}";
	

	$script = trim($jsondata);
	$script = str_replace("\\n", "", $script);
	$script = str_replace("\\r", "", $script);
	$script = str_replace("\\t", "", $script);
	$script = str_replace("```json", "", $script);
	$script = str_replace("tabular-data-json", "", $script);
	$script = str_replace("`", "", $script);
	$script = extractJsonFromString($script);
	$jscript = json_decode($script);

	echo $script;
	echo "\n\n";
	print_r($jscript);
?>
