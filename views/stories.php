<?php
	$database = new Database();
	$db = $database->getConnection();

	$hash = $path[2];
	$story = null;
	// If hash is set. Query the database for the story
	if ($hash):
		$stmt = $db->prepare("SELECT * FROM `stories` WHERE active = 1 AND permalink = :hash ORDER BY timestamp DESC");
		$stmt->bindParam(':hash', $hash);
		$stmt->execute();
		$story = $stmt->fetch(PDO::FETCH_ASSOC);
	endif;
?>
<script>

</script>

<?php
if ($story):
	try {
		$stmt = $db->prepare("SELECT * FROM `comics` WHERE `storyId` = :storyId ORDER BY comics.timestamp DESC");
		$stmt->bindParam(':storyId', $story['id']);
		$stmt->execute();

		// Should only be one record
		$comics = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} catch(PDOException $e) {
		echo "ERROR: Could not execute the query. " . $e->getMessage();
	}
?>
	<h2>
		<?= htmlspecialchars($story['title']) ?>
	</h2>
	<div id="stories" role="region" aria-label="">
		<?php if (count($comics) > 0): ?>
			<ul class="comic-list">
				<?php $totalComics = count($comics); ?>
				<?php foreach($comics as $index => $comic): ?>
					<li class="comic-item">
						<a href="/detail/<?= htmlspecialchars($comic['permalink']) ?>">
							<img src="<?php echo BUCKET_URL; ?>/thumbnails/thumb_<?= htmlspecialchars($comic['permalink']) ?>.png" alt="<?= htmlspecialchars($comic['title']) ?>" width="100" />
							Part <?= $totalComics - $index ?>
							<br/>
							<?= htmlspecialchars($comic['title']) ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
<?php
else:
	try {
		$stmt = $db->prepare("SELECT 
			stories.*, 
			comics.permalink AS comicPermalink
		FROM 
			stories
		LEFT JOIN (
			SELECT c1.*
			FROM comics c1
			INNER JOIN (
				SELECT storyId, MAX(timestamp) AS max_timestamp
				FROM comics
				GROUP BY storyId
			) c2 ON c1.storyId = c2.storyId AND c1.timestamp = c2.max_timestamp
		) comics ON comics.storyId = stories.id
		WHERE 
			stories.active = 1
		ORDER BY 
			stories.timestamp DESC");
		$stmt->execute();

		// Should only be one record
		$stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} catch(PDOException $e) {
		echo "ERROR: Could not execute the query. " . $e->getMessage();
	}
?>
	<h2>Stories</h2>
	<div id="stories" role="region" aria-label="">
		<ul class="story-list">
		<?php foreach($stories as $story): ?>
			<li class="story-item">
				<a href="/stories/<?= htmlspecialchars($story['permalink']) ?>">
					<img src="<?php echo BUCKET_URL; ?>/thumbnails/thumb_<?= htmlspecialchars($story['comicPermalink']) ?>.png" alt="<?= htmlspecialchars($story['title']) ?>" width="100" />
					<?= htmlspecialchars($story['title']) ?>
				</a>
			</li>
		<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>

<!-- <script type="text/javascript" src="/scripts/stories.js?v=<?php echo $version ?>"></script> -->