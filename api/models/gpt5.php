<?php
require_once('gpt.php');

/**
 * Provides functionality for interacting with the OpenAI GPT-5 model.
 * Inherits from ModelGpt and uses the gpt-5 model.
 */
class ModelGpt5 extends ModelGpt {
	function __construct() {
		parent::__construct();
		$this->modelName = "gpt-5";
	}
}
?>