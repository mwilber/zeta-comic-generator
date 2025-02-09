<?php
	$database = new Database();
	$db = $database->getConnection();
	
	try {
		$stmt = $db->prepare("SELECT `categories`.`prefix`, `continuity`.`description` 
							  FROM `continuity` 
							  JOIN `categories` ON `continuity`.`categoryId` = `categories`.`id` 
							  WHERE permalink = :id");
		$stmt->bindParam(':id', $path[2], PDO::PARAM_STR);
		$stmt->execute();

		// Should only be one record
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		// Fetch the single record as an object
		$record = $result[0];

		// if ($record && isset($record['category'])) {
		// 	switch ($record['category']) {
		// 		case 1:
		// 			$continuityPrefix = " - Personality Trait";
		// 			break;
		// 		case 2:
		// 			$continuityPrefix = " - Likes";
		// 			break;
		// 		case 3:
		// 			$continuityPrefix = " - Visited";
		// 			break;
		// 		case 4:
		// 			$continuityPrefix = " - Encountered";
		// 			break;
		// 	}
		// }
	} catch(PDOException $e) {
		$output->error = "ERROR: Could not execute the query. " . $e->getMessage();
	}

?>
<script>
	const continuityId = '<?php echo $path[2] ?>';
</script>
<h2>
	Gallery
	<?php 
		if (isset($record['prefix']) && isset($record['description'])) {
			echo " - " . $record['prefix'] . ": ";
			echo $record['description'];
		}
	?>
</h2>
<div id="gallery" role="region" aria-label="Gallery of comics in reverse chronlogical order."></div>

<script type="text/javascript" src="/scripts/gallery.js?v=<?php echo $version ?>"></script>