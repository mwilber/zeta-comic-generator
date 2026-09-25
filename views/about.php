<h2>How It Works</h2>
<p class="diagram">
	<img src="/assets/images/layer_diagram_900c.png" alt="Diagram of comic strip layer composite. An AI generated background image, a hand drawn character image and a dialog baloon image from an AI generated script layered on top of each other.">
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
<h2 id="character-art">Character Art...</h2>
<ul class="character-art">
	<?php foreach(array_keys($GLOBALS['characterActions']) as $action): ?>
	<li>
		<h3><?php echo str_replace('_', ' ', $action); ?></h3>  

		<img src="/assets/character_art/<?php echo $action; ?>.png" alt="Alpha in <?php echo $action; ?> pose." />
	</li>
	<?php endforeach; ?>
</ul>
<h2 id="mcp-server">MCP Server</h2>
<div class="mcp-intro">
	<p>
		<img class="mcp-presenting" src="/assets/images/zeta_presenting.png" alt="Alpha Zeta presenting" />
		Create and browse Zeta comics from your AI assistant. Simply add a remote MCP (Model Context Protocol) server with the URL <a href="https://comicgenerator.greenzeta.com/mcp">https://comicgenerator.greenzeta.com/mcp</a>. Requires a client that supports &ldquo;MCP Apps&rdquo;.
	</p>
	<p>
		Once connected, ask things like “Show me the latest comic,” “List the series, then show me part 1,” or “Generate a comic about Alpha Zeta learning to cook.” After generating a comic, ask the assistant to save it to receive a permanent link!
	</p>
</div>
<h3>MCP Tools</h3>
<ul>
	<li><p><strong><code>generate_comic</code></strong>: Creates and displays a three-panel comic from a short <code>premise</code> (up to 210 characters). Supports an optional <code>workflow</code>: <code>openai</code> (default), <code>xai</code>, or <code>google</code>. The assistant checks availability first with <code>prepare_comic_generation</code>.</p></li>
	<li><p><strong><code>view_comic</code></strong>: Displays a saved comic using its <code>permalink</code>, the 32-character identifier from its detail-page URL.</p></li>
	<li><p><strong><code>get_latest_comic</code></strong>: Finds the newest public gallery comic and returns its title, summary when available, permalink, and link. Takes no arguments.</p></li>
	<li><p><strong><code>get_random_comic</code></strong>: Finds a random public gallery comic and returns the same details as <code>get_latest_comic</code>. Takes no arguments; repeated calls may return the same comic.</p></li>
	<li><p><strong><code>get_series</code></strong>: Lists available series with descriptions, published comic counts, permalinks, and links. Takes no arguments.</p></li>
	<li><p><strong><code>get_series_comic</code></strong>: Finds a comic within a series using its <code>series</code> permalink and a zero-based <code>index</code> (part 1 is <code>0</code>), ordered oldest first.</p></li>
	<li><p><strong><code>prepare_comic_generation</code></strong>: Checks the daily generation allowance for a <code>premise</code> and optional <code>workflow</code> before generation starts.</p></li>
	<li><p><strong><code>save_comic</code></strong>: Saves a completed generated comic when you explicitly ask to save it, returning a permanent link. The assistant supplies the draft identifier automatically.</p></li>
</ul>
<p>
	To display a comic found by the latest, random, or series lookup tools, the assistant passes the returned comic permalink to <code>view_comic</code>. See the <a href="https://github.com/mwilber/zeta-comic-generator/blob/master/mcp/README.md">MCP server documentation</a> for implementation and deployment details.
</p>
<h2 id="ai-models">AI Models</h2>
<ul class="models">
	<li>
		<h3>Story</h3>
		<ul>
			<li><a href="https://developers.openai.com/api/docs/models/gpt-6-astra" target="_blank" rel="noopener noreferrer">GPT 6 Astra</a></li>
			<li><a href="https://ai.google.dev/gemini-api/docs/models/gemini-3.1-pro-preview" target="_blank" rel="noopener noreferrer">Gemini 3.1 Pro</a></li>
			<li><a href="https://docs.x.ai/developers/models/grok-4.3" target="_blank" rel="noopener noreferrer">Grok 4.3</a></li>
			<li><a href="https://github.com/deepseek-ai/DeepSeek-R1" target="_blank" rel="noopener noreferrer">DeepSeek R1</a></li>
		</ul>
	</li>
	<li>
		<h3>Script</h3>
		<ul>
			<li><a href="https://developers.openai.com/api/docs/models/gpt-5.6-terra" target="_blank" rel="noopener noreferrer">GPT 5.6 Terra</a></li>
			<li><a href="https://ai.google.dev/gemini-api/docs/models/gemini-3.8-flash" target="_blank" rel="noopener noreferrer">Gemini 3.8 Flash</a></li>
			<li><a href="https://docs.x.ai/developers/models/grok-4.20-non-reasoning" target="_blank" rel="noopener noreferrer">Grok 4.2 (non-reasoning)</a></li>
			<li><a href="https://github.com/deepseek-ai/DeepSeek-V3" target="_blank" rel="noopener noreferrer">DeepSeek V3</a></li>
		</ul>
	</li>
	<li>
		<h3>Image</h3>
		<ul>
			<li><a href="https://platform.openai.com/docs/models/gpt-image-2" target="_blank" rel="noopener noreferrer">GPT Image 2</a></li>
			<li><a href="https://deepmind.google/models/imagen/" target="_blank" rel="noopener noreferrer">Imagen 4</a></li>
			<li><a href="https://ai.google.dev/gemini-api/docs/models/gemini-3.1-flash-image" target="_blank" rel="noopener noreferrer">Gemini 3.1 Flash Image (Nano Banana 2)</a></li>
			<li><a href="https://docs.x.ai/developers/models/grok-imagine-image" target="_blank" rel="noopener noreferrer">Grok Imagine</a></li>
			<li><a href="https://openai.com/dall-e-3" target="_blank" rel="noopener noreferrer">DALL-E 3</a></li>
		</ul>
	</li>
</ul>
<h2 id="ai-prompts">AI Prompts</h2>
<ul class="prompts">
<?php 
    $prompts = new Prompts();
    foreach($prompts->prompts as $action => $prompt):
		$params = [];
		switch ($action) {
			case "system":
				$params[] = implode(", ", array_keys($GLOBALS['characterActions']));
				$params[] = "Alpha Zeta's character profile includes the following: \n\n<strong>{ a bullet list of Alpha Zeta's character traits }</strong>";
				$params[] = "Events that have occurred in past comics: \n\n<strong>{ a bullet list of events from past comics }</strong>";
			case "concept":
				$params[] = "<strong>{ The story premise }</strong>";
				break;
			case "image":
				$params[] = "<strong>{ The scene description generated from the \"background\" prompt }</strong>";
				break;
			case "script":
				$params[] = implode(", ", array_keys($GLOBALS['characterActions']));
				break;
		}
        $promptDisplay = $prompts->generatePrompt($action, $params, true);
?>
	<li>
		<h3><?php echo ucfirst($action) ?></h3>
        <div class="codeblock">
            <pre><?php echo $promptDisplay ?></pre>
        </div>
	</li>
	<?php endforeach; ?>
</ul>
