<?php
	$rows = [];
	$database = new Database();
	$db = $database->getConnection();

	try {
		$stmt = $db->prepare("SELECT * FROM `comics` WHERE `gallery` = 1 AND (seriesId = 0 OR seriesId = 5) ORDER BY timestamp DESC LIMIT 4");
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} catch(PDOException $e) {
		echo "Database Error";
	}
?>
<div id="alertdialog" class="dialog-wrapper" role="alert" tabindex="-1">
	<div class="dialog">
		<button id="closedialog" class="close" aria-label="Close"></button>
		<p>
			Vote for Zeta Comic Generator in the Gemini App Contest!
		</p>
		<br style="float:clear;"/>
		<a href="https://ai.google.dev/competition/projects/zeta-comic-generator" class="cartoon-button" target="_blank" rel="noopener noreferrer">
			<img class="burst" src="/assets/images/speech_bubble.svg" alt="Cartoon speech bubble icon" />
			<span class="cartoon-font">Contest Page</span>
		</a>
	</div>
</div>

<div class="homepage">
	<div class="home-intro">
		<section class="home-hero" aria-label="Create comics with Alpha Zeta">
			<header>
				<h1>AI powered stories featuring a little green alien named Alpha.</h1>
				<a href="/generate">Create</a> comics from your ideas.
			</header>
			<img class="home-hero-image" src="/assets/images/homepage_hero.png" width="1024" height="1024" alt="Alpha Zeta holds a paintbrush above a city full of his fellow little green aliens." fetchpriority="high">
		</section>

		<div class="home-feed">
			<section class="home-updates" aria-labelledby="updates-title">
				<h2 id="updates-title">Updates</h2>
				<div class="home-panels">
					<div class="home-update">
						<a href="https://www.youtube.com/watch?v=eP0bWg1HjKk" target="_blank" rel="noopener noreferrer">
							<span class="home-update-image">
								<img src="https://i.ytimg.com/vi/eP0bWg1HjKk/hqdefault.jpg" alt="Poster frame for Selfie, Alpha Zeta's first animated short">
							</span>
							<span class="home-update-copy">
								<strong id="animated-short-title">Watch Alpha in his first animated short: <em>Selfie</em></strong>
							</span>
						</a>
					</div>
					<div class="home-update">
						<div class="home-update-image home-update-placeholder" role="img" aria-label="Update placeholder"></div>
					</div>
				</div>
			</section>

			<section class="home-latest" aria-labelledby="latest-comics-title">
				<h2 id="latest-comics-title"><a href="/gallery">Latest Comics</a></h2>
				<div id="gallery" class="home-panels" role="region" aria-label="Gallery of latest comics">
				<?php foreach ($rows as $row): ?>
					<div class="frame">
						<a class="strip" href="/detail/<?php echo htmlspecialchars($row["permalink"], ENT_QUOTES, 'UTF-8') ?>" aria-label="Comic Title: <?php echo htmlspecialchars($row["title"], ENT_QUOTES, 'UTF-8') ?>">
							<img src="<?php echo htmlspecialchars(BUCKET_URL . '/thumbnails/thumb_' . $row["permalink"] . '.png', ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
							<h3><?php echo htmlspecialchars($row["title"], ENT_QUOTES, 'UTF-8') ?></h3>
						</a>
					</div>
				<?php endforeach; ?>
				</div>
			</section>
		</div>
	</div>

	<section class="home-how-it-works" aria-labelledby="how-it-works-title">
		<h2 id="how-it-works-title">How It Works</h2>
		<p class="diagram">
			<img src="/assets/images/layer_diagram_900c.png" width="100%" alt="Diagram of comic strip layer composite. An AI generated background image, a hand drawn character image and a dialog baloon image from an AI generated script layered on top of each other.">
		</p>
		<p class="description">
			Zeta Comic Generator starts with a premise, a short description of what the comic should be about.
			Large Language Models use the premise to write a complete script for a three panel comic strip.
			The model then elaborates on a scene description for each panel.
			That description is sent to an image generator to render a background image.
			Finally, the model selects from a list of <a href="/about#character-art">character actions</a>. Each action represents an image of
			<a href="https://greenzeta.com/project/illustrations/" target="_blank" rel="noopener noreferrer">Alpha Zeta</a>,
			the alien mascot of <a href="https://greenzeta.com" target="_blank" rel="noopener noreferrer">GreenZeta.com</a>.
			All of the assets are combined here into a single comic strip!
		</p>
		<div class="action-buttons" style="margin-top: 0.5em;">
			<a href="/generate" class="cartoon-button">
				<img class="burst" src="/assets/images/speech_bubble.svg" alt="Cartoon speech bubble icon">
				<span class="cartoon-font">Create Your Own</span>
			</a>
		</div>

	</section>
</div>

<script defer type="module" src="/scripts/home.js?v=<?php echo $version ?>"></script>
