
<?php
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ERROR);

	// required headers
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset=UTF-8");

	// include database and object files
	include_once './db.php';

	$database = new Database();
	$db = $database->getConnection();

	$output = new stdClass;
	$output->gallery = array();

	try {
		$stmt = $db->prepare("SELECT * FROM `comics` WHERE permalink <> \"\" AND title <> \"\"");
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach ($rows as $row) {
			$strip = new stdClass;
			$strip->id = $row["permalink"];
			$strip->title = $row["title"];

			array_push($output->gallery, $strip);
		}

	} catch(PDOException $e) {
		$output->error = "ERROR: Could not execute the query. " . $e->getMessage();
	}

	echo json_encode($output);

	die;
?>