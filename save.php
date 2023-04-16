<?php
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	// include database and object files
	include_once './db.php';

	function downloadImage($url, $savePath = './backgrounds/') {
		// Create the directory if it doesn't exist
		if (!file_exists($savePath)) {
			mkdir($savePath, 0777, true);
		}

		// Initialize cURL session
		$ch = curl_init($url);

		// Set cURL options
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		// Get the image content
		$imageData = curl_exec($ch);

		// Check for errors
		if (curl_errno($ch)) {
			throw new Exception(curl_error($ch));
		}

		// Close the cURL session
		curl_close($ch);

		// Generate a unique file name
		$filename = uniqid() . '.png';
		$filePath = $savePath . $filename;

		// Save the image to the local filesystem
		file_put_contents($filePath, $imageData);

		return $filename;
	}

	// required headers
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset=UTF-8");

	$output = json_decode('{"response": {}, "error": null}');

	$database = new Database();
	$db = $database->getConnection();

	if(!isset($_POST["script"])) {
		$output->error = "Value `script` not found in POST";
		$output->response->values = $_POST;
	} else {
		$jsonData = $_POST["script"];
	
		// prepare query statement
		$stmt = $db->prepare("INSERT INTO `comics` (`json`) VALUES (".$db->quote($jsonData).");");
		// execute query
		$stmt->execute();
		$output->response->comicId = $db->lastInsertId();
	
		//echo $comicId;

		if($output->response->comicId > 0) {
			for($idx = 0; $idx < 3; $idx++) {
				$bkgUrl = $_POST["bkg".($idx + 1)];
				$panel = ($idx + 1);

				try {
					$filename = downloadImage($bkgUrl);
					//echo "Image saved as: " . $filename;
				} catch (Exception $e) {
					$output->error = "Error getting background image: " . $e->getMessage();
				}
			
				if(isset($filename)){
					// prepare query statement
					$stmt = $db->prepare("INSERT INTO `backgrounds` (`comic_id`, `panel`, `filename`) VALUES ('".$output->response->comicId."', '".$panel."', '".$filename."');");
					// execute query
					$stmt->execute();
				}
			}
		}
	}


	echo json_encode($output);

?>
