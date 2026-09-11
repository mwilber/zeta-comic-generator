<?php
require_once('gpt.php');

/**
 * Provides functionality for interacting with the OpenAI GPT-5.6 Terra model.
 * Inherits the Responses API request format and low reasoning effort from ModelGpt.
 */
class ModelGpt5 extends ModelGpt {
	function __construct() {
		parent::__construct();
		$this->modelName = "gpt-5.6-terra";
	}
}
