<?php
	$database = new Database();
	$db = $database->getConnection();

	// Fetch the number of records in the table for the current date
	$stmt = $db->prepare("SELECT COUNT(*) FROM `metrics` WHERE DATE(timestamp) = CURDATE()");
	$stmt->execute();

	$output->json = new stdClass();
	
	$output->json->count = $stmt->fetchColumn();
	$output->json->limitreached = $output->json->count >= RATE_LIMIT;
?>