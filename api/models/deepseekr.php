<?php

class ModelDeepSeekR extends ModelDeepSeek {
	public function __construct() {
		parent::__construct();
		$this->modelName = "deepseek-reasoner";
		$this->responseFormat = "text";
	}
}

?>