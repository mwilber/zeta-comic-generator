<?php
	require __DIR__ . '/../api/includes/key.php';

	$seriesRs = null;

	if(defined("DEV_SITE") && DEV_SITE === true) {
		$database = new Database();
		$db = $database->getConnection();

		try {
			$stmt = $db->prepare("SELECT * FROM `series` ORDER BY timestamp DESC");
			$stmt->execute();
			$seriesRs = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch(PDOException $e) {
			echo "ERROR: Could not execute the query. " . $e->getMessage();
		}
	}
?>
<script>
	const characterActions = <?php echo json_encode($GLOBALS['characterActions']); ?>;
</script>
<div id="statusdialog" class="dialog-wrapper" role="alert" aria-labeledby="progress" tabindex="-1">
	<div class="dialog">
		<div id="status"></div>
		<div class="lds-grid"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
		<progress id="progress" value="0" max="100"></progress>
	</div>
</div>
<div id="errordialog" class="dialog-wrapper" role="alert" tabindex="-1">
	<div class="dialog">
		<button id="closedialog" class="close" aria-label="Close"></button>
		<p>
			The daily limit for generating comics has been reached. 
			<br/>
			Please try again tomorrow.
			<br/>
			In the meantime, check out the gallery to see Alpha in action.
		</p>
		<br style="float:clear;"/>
		<a href="/gallery" class="cartoon-button">
			<img class="burst" src="/assets/images/speech_bubble.svg" alt="Cartoon speech bubble icon" />
			<span class="cartoon-font">View Gallery</span>
		</a>
	</div>
</div>
<div id="interface">
	<div class="query-wrapper">
		<h2>Setup</h2>
		<div id="input">
			<div class="row selections" style="flex-wrap: wrap;">
				<?php if(defined("DEV_SITE") && DEV_SITE === true): ?>
					<?php if($seriesRs): ?> 
					<label>
						Series
						<div class="select">
						<select name="series-id" id="series-id">
							<option value="" selected>None</option>
							<?php foreach($seriesRs as $series): ?>
							<option value="<?= $series['id'] ?>"><?php if($series['active'] == 0): echo "!"; endif; ?>
								<?= htmlspecialchars($series['title']) ?>
							</option>
							<?php endforeach; ?>
						</select>
						</div>
					</label>
					<?php endif; ?>
				<?php else: ?>
					<input id="series-id" type="hidden" value="" />
				<?php endif; ?>
				<div id="group-selection">
					<label>
						Model Select
						<div class="select">
							<select name="group-select" id="group-select">
								<option value="">(Select a provider)</option>
								<option value="openai">OpenAI</option>
								<option value="google">Google</option>
							</select>
						</div>
					</label>
				</div>
				<div id="model-selection" style="display: none;">
					<label> 
						Story Model
						<div class="select">
							<select name="story-model" id="story-model">
								<option value="">(Select a model)</option>
								<option value="o">o3</option>
								<option value="gpt5">GPT 5</option>
								<option value="gemthink">Gemini 2.5 Pro</option>
								<option value="grok">Grok 2</option>
								<option value="deepseekr">DeepSeek R1</option>
							</select>
						</div>
					</label>
					<label> 
						Script Model
						<div class="select">
							<select name="script-model" id="script-model">
								<option value="">(Select a model)</option>
								<option value="gpt">GPT 5 Mini</option>
								<option value="gem">Gemini 2.0 Flash</option>
								<option value="grok">Grok 2</option>
								<option value="deepseek">DeepSeek V3</option>
							</select>
						</div>
					</label>
					<label>
						Image Model
						<div class="select">
							<select name="image-model" id="image-model">
								<option value="">(Select a model)</option>
								<option value="oai">Dall-E 3</option>
								<option value="imagen">Imagen 3</option>
							</select>
						</div>
					</label>
					<label id="image-style-label" style="display:none;">
						Image Style
						<div class="select">
							<select name="image-style" id="image-style">
								<option value="" selected>(default)</option>
								<option value="anime">Anime</option>
								<option value="cinematic">Cinematic</option>
								<option value="comic-book">Comic Book</option>
								<option value="fantasy-art">Fantasy</option>
								<option value="low-poly">Low Poly</option>
								<option value="neon-punk">Neon Punk</option>
								<option value="origami">Origami</option>
								<option value="photographic">Photographic</option>
							</select>
						</div>
					</label>
				</div>
				<label>
					<br/>
					<button type="button" id="advanced-toggle" class="toggle-button">Advanced</button>
				</label>
			</div>
			<div class="row">
				<label for="query">
					Enter a story premise. Keep it short and simple. Then press the "Start" button.
				</label>
			</div>
			<div class="row">
				<input id="query" type="text" placeholder="ex. An explanation of the distance between the earth and the sun."/>
				<button id="generate" class="cartoon-button" disabled>
					<img class="burst" src="/assets/images/speech_bubble.svg" alt="Cartoon speech bubble icon" />
					<span class="cartoon-font">Start</span>
				</button>
			</div>
			<div class="row">
				<span id="character-count">210 characters left</span>
			</div>
		</div>
	</div>
	<div class="strip-wrapper">
		<h2>Composite</h2>
		<div id="strip" role="region" aria-label="Comic Strip Composite" tabindex="-1">
			<div class="strip-container">
				<div class="panel-container">
					<div id="panel1" class="panel"></div>
					<div id="panel2" class="panel"></div>
					<div id="panel3" class="panel"></div>
				</div>
			</div>
			<div class="strip-controls">
				<button id="save" class="cartoon-button">
					<img class="burst" src="/assets/images/speech_bubble.svg" />
					<span class="cartoon-font">Save</span>
				</button>
				<div id="permalink" class="cartoon-button"></div>
			</div>	
		</div>
	</div>
	<div class="script-wrapper">
		<h2 class="script">Script</h2>
		<ul id="script"></ul>
	</div>
</div>

<script defer type="module" src="/scripts/generate.js?v=<?php echo $version ?>"></script>