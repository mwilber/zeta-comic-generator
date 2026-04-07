<?php
require_once('gem.php');

class ModelGemThink extends ModelGemini {
	function __construct() {
		parent::__construct();
		$this->modelName = "gemini-3.1-pro-preview";
		$this->apiKey = GOOGLE_KEY;
		$this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/".$this->modelName.":generateContent?key=".$this->apiKey;
	}
}
?>
