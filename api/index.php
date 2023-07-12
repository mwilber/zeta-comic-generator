<?php
	// ini_set('display_errors', 1);
	// ini_set('display_startup_errors', 1);
	// error_reporting(E_ERROR);

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
	require __DIR__ . '/includes/gpt.php';

	switch ($controller) {

		case 'gpt_script':
		case 'gpt_background':
		case 'gpt_dialog':
		case 'gpt_action':
        case 'gpt_background_3':
        case 'gpt_dialog_3':
        case 'gpt_action_3':
        case 'comic':
        case 'detail':
		case 'gallery':
        case 'image':
        case 'save':
			require __DIR__ . '/controllers/'.$controller.'.php';
			break;
	
		default:
			$output->error = "Action not avaialble.";
			break;
	}

    echo json_encode($output);
?>