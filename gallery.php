
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
		$stmt = $db->prepare("SELECT * FROM `comics` WHERE title <> \"\"");
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach ($rows as $row) {
			$strip = new stdClass;
			$strip->id = $row["id"];
			$strip->title = $row["title"];

			array_push($output->gallery, $strip);
		}

	} catch(PDOException $e) {
		$output->error = "ERROR: Could not execute the query. " . $e->getMessage();
	}

	echo json_encode($output);

	die;
	// <html>
	// 	<h2>Gallery</h2>
	// 
	// echo json_encode($output);
	// 	if (isset($rows)) {
	// 		echo "<ul>";
	// 		foreach ($rows as $row) {
	// 			echo "<li>";
	// 			echo $row["title"];
	// 			echo '<img src="./thumbnails/thumb_'.$row["id"].'.png"/>';
	// 			echo "</li>";
	// 		}
	// 		echo "</ul>";
	// 	} else {
	// 		echo "No records found.";
	// 	}
	//
	// </html>
?>