<?php
	$database = new Database();
	$db = $database->getConnection();

	// Fetch the number of records in the table for the current date
	$stmt = $db->prepare("SELECT COUNT(*) FROM `metrics` WHERE DATE(timestamp) = CURDATE()");
	$stmt->execute();

	$output->scriptgeneration = $stmt->fetchColumn();
	$output->limitreached = $output->scriptgeneration < RATE_LIMIT;
?>