<?php

/**
 * API endpoint to retrieve request logs from the database
 * Returns JSON array of all log entries
 */

$database = new Database();
$db = $database->getConnection();

// Prepare query to get all records from requestlog
$query = "SELECT * FROM requestlog ORDER BY timestamp DESC LIMIT 100";
$stmt = $db->prepare($query);
$stmt->execute();

// Fetch all records as an associative array
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert any JSON strings back to objects
foreach ($logs as &$log) {
	if (isset($log['payload'])) {
		$log['payload'] = json_decode($log['payload']);
	}
	if (isset($log['body'])) {
		$log['body'] = json_decode($log['body']);
	}
	if (isset($log['response'])) {
		$log['response'] = json_decode($log['response']);
	}
	if (isset($log['result'])) {
		$log['result'] = json_decode($log['result']);
	}
}

$output->data = $logs;

?> 