<?php
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
?>