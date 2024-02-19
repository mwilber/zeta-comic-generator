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
		Easily <a href="/generate">create</a> unique comic strips with the help of 
		<a href="https://openai.com/product" target="_blank" rel="noopener noreferrer">OpenAI</a> models and 
		hand drawn character art.
	</header>

	<h2>Latest Comics</h2>
	<div id="gallery">
	<?php foreach ($rows as $row): ?>
		<div class="frame">
			<a class="strip" href="/detail/<?php echo $row["permalink"] ?>">
				<img src="https://zeta-comic-generator.s3.us-east-2.amazonaws.com/thumbnails/thumb_<?php echo $row["permalink"] ?>.png">
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
	<p class="diagram">
		<img src="/assets/images/layer_diagram_900b.png" alt="Diagram of comic strip layer composite.">
	</p>
	<p class="description">
		Zeta Comic Generator starts with a premise, a short description of what the comic should be about. 
		Large Language Models at <a href="https://openai.com/product" target="_blank" rel="noopener noreferrer">OpenAI</a> 
        use the premise to write a complete script for a three panel comic strip. 
		The model then elaborates on a scene description for each panel. 
		That description is sent to the <a href="https://openai.com/dall-e-2" target="_blank" rel="noopener noreferrer">Dall-E</a> 
        image generator to render a background image. 
		Finally the model selects from a list of <a href="/about#character-art">character actions</a>, each one representing an image of 
		<a href="https://greenzeta.com/project/illustrations/" target="_blank" rel="noopener noreferrer">Alpha Zeta</a>, 
		the alien mascot of <a href="https://greenzeta.com" target="_blank" rel="noopener noreferrer">GreenZeta.com</a>. 
		All of the assets are combined here into a single comic strip!
	</p>
	<div class="action-buttons" style="margin-top: 0.5em;">
		<a href="/generate" class="cartoon-button">
			<img class="burst" src="/assets/images/speech_bubble.svg">
			<span class="cartoon-font">Create Your Own</span>
		</a>
	</div>