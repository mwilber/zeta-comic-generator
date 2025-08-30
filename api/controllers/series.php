<?php

$database = new Database();
$db = $database->getConnection();

$output->hash = $hash;

$stmt = $db->prepare("SELECT * FROM `stories` ORDER BY `timestamp` DESC");
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($result as $row) {
    $output->stories[] = $row;
}

if (isset($hash) && $hash != '') {
	$stmt = $db->prepare("SELECT `permalink`, `title` FROM `comics` WHERE `storyId` = :hash ORDER BY `timestamp` DESC");
	$stmt->bindParam(':hash', $hash);
	$stmt->execute();
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

	foreach ($result as $row) {
		$output->comics[] = $row;
	}
}

?>