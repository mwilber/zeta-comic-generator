<?php
require_once __DIR__ . '/../api/includes/characteractions.php';
$version = "1.0.0";
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Comic Promoter</title>
	<link rel="stylesheet" href="/styles/main.css?v=<?php echo $version; ?>">
	<link rel="stylesheet" href="/styles/detail.css?v=<?php echo $version; ?>">
	<link rel="stylesheet" href="/styles/script.css?v=<?php echo $version; ?>">
	<link rel="stylesheet" href="/styles/strip.css?v=<?php echo $version; ?>">
	<link rel="stylesheet" href="/comicpromoter/styles/main.css?v=<?php echo $version; ?>">
</head>
<body class="comicpromoter" data-status="ready">
	<main class="content">
		<h1>Comic Promoter</h1>
		<p id="status" class="status">Loading...</p>

		<section class="card">
			<h2>Comic</h2>
			<div id="strip" class="promoter-strip">
				<div class="strip-container"></div>
			</div>
		</section>

		<section class="card">
			<h2>Generated Images</h2>
			<div id="preview-strip" class="preview-group"></div>
			<div id="preview-panels" class="preview-group"></div>
		</section>

		<section class="card">
			<h2>Buffer Post</h2>
			<form id="post-form">
				<label for="post-text">Post Text</label>
				<textarea id="post-text" rows="7" required></textarea>

				<label for="additional-text">Additional Post Text</label>
				<textarea id="additional-text" rows="3">Zeta Comic Generator turns a one-line prompt into a finished three-panel strip in seconds. A JavaScript front end drives PHP endpoints that pipe story ideas to an LLM, dialogue to a lighter model, and backgrounds to an image generator before compositing hand-drawn art of Alpha Zeta.</textarea>

				<label for="hashtags">Hashtags</label>
				<input id="hashtags" type="text" value="#AI #AIart #characterart #GenerativeArt #sciencefiction #scifi #humor #Aliens #AIComics #UFOTwitter">

				<label for="post-date">Post Date</label>
				<input id="post-date" type="date" required>

				<button id="submit-btn" type="submit">Send to Buffer</button>
			</form>
			<p id="submit-result" class="status"></p>
		</section>
	</main>

	<script async src="/scripts/html2canvas.min.js"></script>
	<script>
		const characterActions = <?php echo json_encode($GLOBALS['characterActions']); ?>;
		window.COMICPROMOTER_CONFIG = {
			baseUrl: "https://comicgenerator.greenzeta.com"
		};
	</script>
	<script type="module" src="/comicpromoter/scripts/main.js?v=<?php echo $version; ?>"></script>
</body>
</html>
