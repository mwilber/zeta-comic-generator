<?php
	$database = new Database();
	$db = $database->getConnection();
	
	try {
		$stmt = $db->prepare("SELECT stories.title AS storyTitle, comics.* FROM `comics` INNER JOIN `stories` ON comics.storyId = stories.id WHERE stories.active = 1 ORDER BY stories.timestamp DESC, comics.timestamp DESC");
		$stmt->execute();

		// Should only be one record
		$stories = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$storyList = array();
		foreach($stories as $story) {
			if (!isset($storyList[$story['storyId']])) {
				$storyList[$story['storyId']] = array(
					'title' => $story['storyTitle'],
					'id' => $story['storyId'],
					'comics' => array()
				);
			}
			$storyList[$story['storyId']]['comics'][] = array(
				'title' => $story['title'],
				'permalink' => $story['permalink']
			);
		}

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
	<?php if (!empty($storyList)): ?>
		<ul class="story-list">
		<?php foreach($storyList as $story): ?>
			<li class="story-item">
				<h3><?= htmlspecialchars($story['title']) ?></h3>
				<?php if (count($story['comics']) > 0): ?>
					<ul class="comic-list">
						<?php $totalComics = count($story['comics']); ?>
						<?php foreach($story['comics'] as $index => $comic): ?>
							<li class="comic-item">
								<a href="/detail/<?= htmlspecialchars($comic['permalink']) ?>">
									<img src="<?php echo BUCKET_URL; ?>/thumbnails/thumb_<?= htmlspecialchars($comic['permalink']) ?>.png" alt="<?= htmlspecialchars($comic['title']) ?>" width="100" />
									Part <?= $totalComics - $index ?>: <?= htmlspecialchars($comic['title']) ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>

<script type="text/javascript" src="/scripts/stories.js?v=<?php echo $version ?>"></script>