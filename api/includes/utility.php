<?php
	function add_period($str) {
		$last_char = substr($str, -1);
		if ($last_char !== '.' && $last_char !== '!' && $last_char !== '?') {
		$str .= '.';
		}
		return $str;
	}
?>