<?php

class ModelO extends ModelGpt {
	public function __construct() {
		parent::__construct();
		$this->modelName = "o1-2024-12-17";
		// $this->modelName = "o3-mini-2025-01-31";
	}
}

?>