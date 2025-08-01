<?php
	require __DIR__ . '/../api/includes/key.php';

	$database = new Database();
	$db = $database->getConnection();

	$hash = $path[2];
	$series = null;
	$seriesQueryDev = "series.active = 1 AND ";
	if(defined("DEV_SITE") && DEV_SITE === true) {
		$seriesQueryDev = "";
	}
	// If hash is set. Query the database for the story
	if ($hash) {
		$stmt = $db->prepare("SELECT * FROM `series` WHERE ".$seriesQueryDev."permalink = :hash ORDER BY timestamp DESC");
		$stmt->bindParam(':hash', $hash);
		$stmt->execute();
		$series = $stmt->fetch(PDO::FETCH_ASSOC);
	}
?>
<script>

</script>

<?php
if ($series):
	try {
		$stmt = $db->prepare("SELECT * FROM `comics` WHERE `seriesId` = :seriesId AND `gallery` = 1 ORDER BY comics.timestamp DESC");
		$stmt->bindParam(':seriesId', $series['id']);
		$stmt->execute();

		// Should only be one record
		$comics = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} catch(PDOException $e) {
		echo "ERROR: Could not execute the query. " . $e->getMessage();
	}
?>
	<h2>
		<?= htmlspecialchars($series['title']) ?>
	</h2>
	<div id="series" role="region" aria-label="">
		<?php if (count($comics) > 0): ?>
			<ul class="comic-list">
				<?php $totalComics = count($comics); ?>
				<?php foreach($comics as $index => $comic): ?>
					<li class="comic-item">
						<a href="/detail/<?= htmlspecialchars($comic['permalink']) ?>">
							<img src="<?php echo BUCKET_URL; ?>/thumbnails/thumb_<?= htmlspecialchars($comic['permalink']) ?>.png" alt="<?= htmlspecialchars($comic['title']) ?>" width="100" />
							<span class="comic-title">
								Part <?= $totalComics - $index ?>
								<br/>
								<?= htmlspecialchars($comic['title']) ?>
								<br/>
								<span class="comic-summary"><?= htmlspecialchars($comic['summary']) ?></span>
							</span>
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
			series.*, 
			comics.permalink AS comicPermalink,
			comics.gallery
		FROM 
			series
		LEFT JOIN (
			SELECT c1.*
			FROM comics c1
			INNER JOIN (
				SELECT seriesId, MAX(timestamp) AS max_timestamp
				FROM comics
				WHERE gallery = 1
				GROUP BY seriesId
			) c2 ON c1.seriesId = c2.seriesId AND c1.timestamp = c2.max_timestamp
		) comics ON comics.seriesId = series.id
		WHERE 
			".$seriesQueryDev."
			comics.gallery = 1
		ORDER BY 
			series.timestamp DESC");
		$stmt->execute();

		// Should only be one record
		$seriesRs = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} catch(PDOException $e) {
		echo "ERROR: Could not execute the query. " . $e->getMessage();
	}
?>
	<h2>series</h2>
	<div id="series" role="region" aria-label="">
		<ul class="story-list">
		<?php 
			foreach($seriesRs as $series): 
				if (!isset($series['comicPermalink'])) continue;
		?>
			<li class="story-item">
				<a href="/series/<?= htmlspecialchars($series['permalink']) ?>">
					<img src="<?php echo BUCKET_URL; ?>/thumbnails/thumb_<?= htmlspecialchars($series['comicPermalink']) ?>.png" alt="<?= htmlspecialchars($series['title']) ?>" width="100" />
					<span class="comic-title">
						<?= htmlspecialchars($series['title']) ?>
						<br/>
						<span class="comic-summary"><?= htmlspecialchars($series['premise']) ?></span>
					</span>
				</a>
			</li>
		<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>

<!-- <script type="text/javascript" src="/scripts/series.js?v=<?php echo $version ?>"></script> -->