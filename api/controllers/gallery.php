<?php
/**
 * Fetches a page of comics from the database where the `gallery` flag is set to 1.
 *
 * This function first retrieves the total count of comics with the `gallery` flag set to 1.
 * It then fetches a page of comics based on the provided `$page` and `$limit` parameters,
 * and populates the `$output->gallery` array with the retrieved comic data.
 * 
 * Uses the GET parameter `page` to determine the page of comics to fetch.
 *
 * @example
	// Response JSON
	{
		"error": "",
		"gallery": [
			{
				"id": "e6b4b2a746ed40e1af829d1fa82daa10",
				"title": "May the Fourth Be With Zeta"
			},
			{
				"id": "db85e2590b6109813dafa101ceb2faeb",
				"title": "The Floral Deity Dilemma"
			},
			...
		],
		"count": 100
	}
*/
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