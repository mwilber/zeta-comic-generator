<?php
	$request = $_SERVER['REQUEST_URI'];
	echo $request;

	$path = explode('/', $request);

	// Validate the path
	// if(isset($path[1])) {
	// 	if($path[1] == 'detail' && (!isset($path[2]) || !$path[2])) {
	// 		// If no detail hash, display the home page
	// 		$path[1] = 'home';
	// 	}
	// }

	print_r($path);
?>