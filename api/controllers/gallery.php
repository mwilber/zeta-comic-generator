
<?php
	$database = new Database();
	$db = $database->getConnection();

	$output->gallery = array();

	try {
		$stmt = $db->prepare("SELECT * FROM `comics` WHERE `gallery` = 1 ORDER BY timestamp DESC");
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
?>