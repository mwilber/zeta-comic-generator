<?php
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	// include database and object files
	include_once './db.php';

	// required headers
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset=UTF-8");

	if(!isset($_GET["id"])) die;

	$output = json_decode('{"script": null, "backgrounds": [], "error": ""}');

	$database = new Database();
	$db = $database->getConnection();

	try {
		$stmt = $db->prepare("SELECT * FROM `comics` WHERE permalink = :id");
		$stmt->bindParam(':id', $_GET["id"], PDO::PARAM_INT);
		$stmt->execute();

		// Fetch the single record as an object
		$result = $stmt->fetch(PDO::FETCH_OBJ);

		if ($result && isset($result->json)) {
			$output->id = $result->id;
			$output->prompt = $result->prompt;
			$output->script = json_decode($result->json);
		} else {
			$output->error = "No record found with ID: $id";
		}
	} catch(PDOException $e) {
		$output->error = "ERROR: Could not execute the query. " . $e->getMessage();
	}

	try {
		$stmt = $db->prepare("SELECT * FROM `backgrounds` WHERE comic_id = :id ORDER BY panel ASC");
		$stmt->bindParam(':id', $output->id, PDO::PARAM_INT);
		$stmt->execute();

		// Fetch the single record as an object
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if ($result) {
			foreach ($result as $record) {
				array_push($output->backgrounds, $record['filename']);
				//echo "Value from column_to_display: (".$record['panel'].")" . $record['filename'] . "<br>";
			}
		} else {
			$output->error = "No background image record found with Comic ID: $output->id";
		}
	} catch(PDOException $e) {
		$output->error = "ERROR: Could not execute the query. " . $e->getMessage();
	}
	//print_r($output);
	echo json_encode($output);
?>