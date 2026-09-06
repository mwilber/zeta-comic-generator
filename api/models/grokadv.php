<?php
require_once('grok.php');
/**
 * Provides functionality for interacting with Grok 4.6 at high reasoning effort.
 * 
 * Used for the concept-generation step in the xAI workflow.
 */
class ModelGrokAdvanced extends ModelGrok {
	function __construct() {
		parent::__construct();
		$this->reasoningEffort = "high";
	}
}
?>
