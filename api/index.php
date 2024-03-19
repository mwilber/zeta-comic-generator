<?php
	ini_set('display_errors', 1);
	// ini_set('display_startup_errors', 1);
	error_reporting(E_ERROR);

	$request = $_SERVER['REQUEST_URI'];
	$path = explode('/', $request);
	$controller = "";
	$hash = "";

	$output = new stdClass;
	$output->error = "";

	// Validate the path
	if(isset($path[2])) {
		$controller = $path[2];
		if($controller == 'detail') {
			if(isset($path[3]) && $path[3]) {
				$hash = $path[3];
			} else {
				$controller = "";
			}
		} 
	}
	// print_r($controller);

	// Required headers
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset=UTF-8");

	// Global requires
	require __DIR__ . '/includes/db.php';
	require __DIR__ . '/includes/key.php';
	require __DIR__ . '/includes/utility.php';
	require __DIR__ . '/includes/gpt.php';
	require __DIR__ . '/includes/s3.php';
	require __DIR__ . '/../vendor/autoload.php';

	switch ($controller) {
		// App API endpoints
		case 'comic':
		case 'detail':
		case 'gallery':
		case 'save':
		case 'imgproxy':
		case 'bedrock':
			require __DIR__ . '/controllers/'.$controller.'.php';
			break;
		// Comic Generation API endpoints
		case 'image':					
		case 'script':
		case 'background':
		case 'dialog':
		case 'action':
			$service = "oai";
			if (isset($_POST['model'])) $service = $_POST['model'];
			require __DIR__ . '/controllers/'. $service . "_" . $controller . '.php';
			break;
		default:
			$output->error = "Action not avaialble.";
			break;
	}

	echo json_encode($output);
?>