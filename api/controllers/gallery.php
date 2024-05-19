
<?php
	$database = new Database();
	$db = $database->getConnection();

	if(isset($_GET["page"]) ) $page = $_GET["page"];
	else $page = 1;

	$limit = 24;
	$offset = ($page - 1) * $limit;

	$output->gallery = array();

	try {

		$stmt = $db->prepare("SELECT COUNT(*) FROM `comics` WHERE `gallery` = 1");
		$stmt->execute();

		// Fetch the number of records in the table
		$output->count = $stmt->fetchColumn();

		// Fetch a page of comics
		
		$stmt = $db->prepare("SELECT * FROM `comics` WHERE `gallery` = 1 ORDER BY timestamp DESC LIMIT :limit OFFSET :offset");
		$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
		$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
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