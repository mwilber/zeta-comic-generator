<?php
/**
 * Retrieves a comic record from the database and its associated background images.
 *
 * This function fetches a single comic record from the `comics` table based on the 
 * provided permalink. It then retrieves all the background images associated with 
 * that comic from the `backgrounds` table and stores them in the 
 * `$output->backgrounds` array.
 * 
 * Expects variable $hash to be set to the permalink of the comic to retrieve.
 * 
 * @example
	// Response JSON
	{
		"error": "",
		"script": {
			"title": "The Adventures of Alpha Zeta",
			"panels": [
				{
					"scene": "Panel 1 scene description.",
					"dialog": [
						{
							"character": "alpha",
							"text": "I'm saying something."
						}
					],
					"panelEl": {},
					"background": "A description of the background.",
					"images": [],
					"action": "standing"
				},
				{
					"scene": "Panel 2 scene description.",
					"dialog": [
						{
							"character": "alpha",
							"text": "I'm saying something else."
						}
					],
					"panelEl": {},
					"background": "A description of the background.",
					"images": [],
					"action": "typing",
					"altAction": "hopeful"
				},
				{
					"scene": "Panel 3 scene description.",
					"dialog": [
						{
							"character": "alpha",
							"text": "I'm saying a punch line."
						}
					],
					"panelEl": {},
					"background": "A description of the background.",
					"images": [],
					"action": "joyous"
				}
			]
		},
		"backgrounds": [
			"6648d7d1475af.png",
			"6648d7d213f01.png",
			"6648d7d361767.png"
		],
		"id": 000,
		"prompt": ""
	}
*/

$output->script = null;
$output->backgrounds = null;

$database = new Database();
$db = $database->getConnection();

try {
	$stmt = $db->prepare("SELECT * FROM `comics` WHERE permalink = :id");
	$stmt->bindParam(':id', $hash, PDO::PARAM_STR);
	$stmt->execute();

	// Fetch the single record as an object
	$result = $stmt->fetch(PDO::FETCH_OBJ);

	if ($result && isset($result->json)) {
		$output->id = $result->id;
		$output->prompt = stripslashes($result->prompt);
		$output->script = json_decode($result->json);
		$output->seriesId = $result->seriesId;
	} else {
		$output->error = "No record found with ID: $id";
	}
} catch(PDOException $e) {
	$output->error = "ERROR: Could not execute the query. " . $e->getMessage();
}

if($output->seriesId) {
	$stmt = $db->prepare("SELECT * FROM `series` WHERE `id` = :id");
	$stmt->bindParam(':id', $output->seriesId, PDO::PARAM_INT);
	$stmt->execute();
	$result = $stmt->fetch(PDO::FETCH_OBJ);
	$output->series = new stdClass();
	if($result) {
		$output->series->title = $result->title;
		$output->series->permalink = $result->permalink;
		$output->series->currentIdx = -1;
		$output->series->comics = [];
		$stmt = $db->prepare("SELECT `permalink`, `title` FROM `comics` WHERE `seriesId` = :id AND `gallery` = 1 ORDER BY `timestamp` ASC");
		$stmt->bindParam(':id', $output->seriesId, PDO::PARAM_INT);
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach ($result as $record) {
			array_push($output->series->comics, $record);
			if($record['permalink'] == $hash) {
				$output->series->currentIdx = count($output->series->comics) - 1;
			}
		}
	}
}

if($output->script) $output->backgrounds = [];

try {
	$stmt = $db->prepare("SELECT * FROM `backgrounds` WHERE comic_id = :id ORDER BY panel ASC");
	$stmt->bindParam(':id', $output->id, PDO::PARAM_INT);
	$stmt->execute();

	// Fetch the single record as an object
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

	if ($result) {
		foreach ($result as $record) {
			array_push($output->backgrounds, BUCKET_URL."/backgrounds/".$record['filename']);
		}
	} else {
		$output->error = "No background image record found with Comic ID: $output->id";
	}
} catch(PDOException $e) {
	$output->error = "ERROR: Could not execute the query. " . $e->getMessage();
}

if($output->script) $output->continuity = [];

try {
	$stmt = $db->prepare("SELECT `categories`.`prefix`, `categories`.`alias`, `categories`.`heading`, `continuity`.`categoryId`, `continuity`.`description`, `continuity`.`permalink` FROM `continuity`
	JOIN `categories` ON `continuity`.`categoryId` = `categories`.`id`
	JOIN `comic_continuity` ON comic_continuity.continuityId = continuity.id  
	WHERE comic_continuity.comicId = :comicId AND continuity.active = true
	ORDER BY `continuity`.`categoryId`"); // );  
	$stmt->bindParam(':comicId', $output->id, PDO::PARAM_INT);
	$stmt->execute();

	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	if ($result) {
		foreach ($result as $record) {
			array_push($output->continuity, $record);
		}
	}
} catch(PDOException $e) {
	$output->error = "ERROR: Could not execute the query. " . $e->getMessage();
}

?>