<?php
	$database = new Database();
	$db = $database->getConnection();
	
	try {
		$stmt = $db->prepare("SELECT * FROM `stories` WHERE `active` = 1 ORDER BY `timestamp` DESC");
		$stmt->execute();

		// Should only be one record
		$stories = $stmt->fetchAll(PDO::FETCH_ASSOC);

	} catch(PDOException $e) {
		echo "ERROR: Could not execute the query. " . $e->getMessage();
	}

?>
<script>
	
</script>
<h2>
	Stories
</h2>
<div id="stories" role="region" aria-label="">
	<?php
	if ($stories && count($stories) > 0) {
		foreach ($stories as $story) {
			echo '<div class="story-wrapper">';
			echo '<div class="story-title">' . $story['title'] . '</div>';

			$stmt = $db->prepare("SELECT * FROM `comics` WHERE `storyId` = :id ORDER BY `timestamp` DESC");
			$stmt->bindParam(':id', $story['id'], PDO::PARAM_STR);
			$stmt->execute();
			$comics = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if ($comics && count($comics) > 0) {
				echo '<div class="story-comics">';
				foreach ($comics as $comic) {
					echo '<div class="comic-wrapper">';
					echo '<a href="/detail/' . $comic['permalink'] . '">';
					echo '<img src="' .BUCKET_URL.'/thumbnails/thumb_'.$comic["permalink"].'.png" alt="' . $comic['title'] . '" width="100" />';
					echo $comic['title'];
					echo '</a>';
					echo '</div>';
				}
				echo '</div>';
			}
		}
	}
	?>
</div>

<script type="text/javascript" src="/scripts/stories.js?v=<?php echo $version ?>"></script>