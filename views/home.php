<?php
	$database = new Database();
	$db = $database->getConnection();

	try {
		$stmt = $db->prepare("SELECT * FROM `comics` WHERE `gallery` = 1 ORDER BY timestamp DESC LIMIT 4");
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} catch(PDOException $e) {
		echo "Database Error";
	}
?>
	<header>
		Easily create you own comic strips with the help of <a href="#">OpenAI</a> models and Alpha Zeta, 
		the alien mascot of <a href="https://greenzeta.com" target="_blank" rel="noopener noreferrer">GreenZeta.com</a>
	</header>

	<h2>Latest Comics</h2>
	<div id="gallery">
	<?php foreach ($rows as $row): ?>
		<div class="frame">
			<a class="strip" href="/detail/<?php echo $row["permalink"] ?>">
				<img src="/assets/thumbnails/thumb_<?php echo $row["permalink"] ?>.png">
				<h3><?php echo $row["title"] ?></h3>
			</a>
		</div>
	<?php endforeach; ?>
	</div>
	<div class="action-buttons">
		<a href="/gallery" class="cartoon-button">
			<img class="burst" src="/assets/images/speech_bubble.svg">
			<span class="cartoon-font">View Gallery</span>
		</a>
	</div>
	<h2>How It Works</h2>
	<p style="text-align: center;">
		<img src="/assets/images/layer_diagram_900.png" alt="Diagram of comic strip layer composite." style="width: 75%;">
	</p>
	<p>
		Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec vel semper augue. Sed faucibus neque augue, eu faucibus turpis sodales dignissim. Phasellus ac volutpat quam, quis condimentum justo. Fusce sagittis cursus fringilla. Sed quis gravida arcu, ut facilisis urna. Suspendisse potenti. Integer sodales nibh auctor, tristique quam a, faucibus nisl. Donec at nunc lectus. Fusce vitae nunc aliquet, consectetur ipsum id, mollis diam.
	</p>
	<div class="action-buttons" style="margin-top: 0.5em;">
		<a href="/gallery" class="cartoon-button">
			<img class="burst" src="/assets/images/speech_bubble.svg">
			<span class="cartoon-font">Create Your Own</span>
		</a>
	</div>