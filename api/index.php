<?php
	/**
     * IMPORTANT NOTE: This API requires the file /includes/key.php, which contains sensitive login
     * credentials. See the file /includes/key_example.php if attempting to stand this up on
     * your own server.
     * 
	 * This is the main entry point for the API. It handles routing requests to the appropriate 
	 * controller based on the request URI.
	 * 
	 * The script sets up the necessary environment, including error reporting, global constants, 
	 * and required files. It then validates the request path and dispatches the request to the 
	 * appropriate controller.
	 * 
	 * The script also handles simulating api access if the `SIMULATION_MODE` constant is set to 
	 * `true` and simulating errors if the `SIMULATE_ERRORS` constant is set to `true`.
	 */

	ini_set('display_errors', 1);
	//ini_set('display_startup_errors', 1);
	error_reporting(E_ERROR);

	define("SIMULATION_MODE", "image"); // all, text, image, none
	define("SIMULATE_DELAY", 0);
	define("SIMULATE_ERRORS", false);

	$request = $_SERVER['REQUEST_URI'];
	$path = explode('/', $request);
	$controller = "";
	$hash = "";

	$output = new stdClass;
	$output->error = "";

	// Validate the path
	if(isset($path[2])) {
		$controller = $path[2];
		if($controller == 'detail' || $controller == 'gallery' || $controller == 'stories') {
			if(isset($path[3]) && $path[3]) {
				$hash = $path[3];
			} else {
				$controller = "";
			}
		} 
	}

	// Required headers
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset=UTF-8");

	// Global requires
	require __DIR__ . '/includes/key.php';
	require __DIR__ . '/includes/characteractions.php';
	require __DIR__ . '/includes/db.php';
	require __DIR__ . '/includes/s3.php';
	require __DIR__ . '/../vendor/autoload.php';
	require __DIR__ . '/includes/api_logger.php';

	// AI Prompts
	require __DIR__ . '/includes/prompts.php';

	// AI Models
	require __DIR__ . '/models/gpt.php';
	//require __DIR__ . '/models/gpt45.php';
	require __DIR__ . '/models/o.php';
	require __DIR__ . '/models/gem.php';
	require __DIR__ . '/models/gemthink.php';
	require __DIR__ . '/models/ttn.php';
	require __DIR__ . '/models/dall.php';
	require __DIR__ . '/models/sdf.php';
	require __DIR__ . '/models/claude.php';
	require __DIR__ . '/models/deepseek.php';
	require __DIR__ . '/models/deepseekr.php';
	require __DIR__ . '/models/llama.php';
	require __DIR__ . '/models/imagen.php';
	require __DIR__ . '/models/grok.php';

	switch ($controller) {
		// App API endpoints
		case 'comic':
		case 'detail':
		case 'gallery':
		case 'stories':
		case 'save':
		case 'imgproxy':
		case 'bedrock':
		case 'metrics':
			require __DIR__ . '/controllers/'.$controller.'.php';
			break;
		// Comic Generation API endpoints
		case 'test':
		case 'concept':
		case 'script':
		case 'background':
		case 'continuity':
		case 'action':
			if (SIMULATION_MODE == "all" || SIMULATION_MODE == "text") {
				require __DIR__ . '/controllers/simulatetext.php';
			} else {
				require __DIR__ . '/controllers/generatetext.php';
			}
			break;
		case 'testimage':
		case 'image':				
			if (SIMULATION_MODE == "all" || SIMULATION_MODE == "image") {
				require __DIR__ . '/controllers/simulateimage.php';
			} else {
				require __DIR__ . '/controllers/generateimage.php';
			}
			break;
		case 'log':
			require __DIR__ . '/controllers/log.php';
			break;
		default:
			$output->error = "Action not avaialble.";
			break;
	}

	// Randomly insert an error
	if (SIMULATE_ERRORS && $controller && rand(0, 100) < 50) {
		$output->error = "Simulated error.";
	}

	echo json_encode($output);

	function POSTval($name, $default = "") {
		if (isset($_POST[$name])) return $_POST[$name];
		return $default;
	}
?>