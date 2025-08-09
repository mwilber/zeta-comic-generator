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
<h2 id="ai-models">AI Models</h2>
<ul class="models">
	<li>
		<h3>Story</h3>
		<ul>
			<li><a href="https://openai.com/product" target="_blank" rel="noopener noreferrer">o3</a></li>
			<li><a href="https://openai.com/product" target="_blank" rel="noopener noreferrer">GPT 5</a></li>
			<li><a href="https://deepmind.google/models/gemini/" target="_blank" rel="noopener noreferrer">Gemini 2.5 Pro</a></li>
			<li><a href="https://x.ai/grok" target="_blank" rel="noopener noreferrer">Grok 2</a></li>
			<li><a href="https://www.anthropic.com/claude/sonnet" target="_blank" rel="noopener noreferrer">Claude 3.5 Sonnet</a></li>
			<li><a href="https://github.com/deepseek-ai/DeepSeek-R1" target="_blank" rel="noopener noreferrer">DeepSeek R1</a></li>
		</ul>
	</li>
	<li>
		<h3>Script</h3>
		<ul>
			<li><a href="https://openai.com/product" target="_blank" rel="noopener noreferrer">GPT 5 Mini</a></li>
			<li><a href="https://deepmind.google/models/gemini/" target="_blank" rel="noopener noreferrer">Gemini 2.0 Flash</a></li>
			<li><a href="https://x.ai/grok" target="_blank" rel="noopener noreferrer">Grok 2</a></li>
			<li><a href="https://www.anthropic.com/claude/sonnet" target="_blank" rel="noopener noreferrer">Claude 3.5 Sonnet</a></li>
			<li><a href="https://github.com/deepseek-ai/DeepSeek-V3" target="_blank" rel="noopener noreferrer">DeepSeek V3</a></li>
			<li><a href="https://www.llama.com/" target="_blank" rel="noopener noreferrer">Llama 3.2</a></li>
		</ul>
	</li>
	<li>
		<h3>Background</h3>
		<ul>
			<li><a href="https://openai.com/dall-e-3" target="_blank" rel="noopener noreferrer">Dall-E 3</a></li>
			<li><a href="https://deepmind.google/models/imagen/" target="_blank" rel="noopener noreferrer">Imagen 3</a></li>
			<li><a href="https://stability.ai/stable-image" target="_blank" rel="noopener noreferrer">Stable Diffusion XL</a></li>
			<li><a href="https://aws.amazon.com/bedrock/titan/" target="_blank" rel="noopener noreferrer">Titan Image</a></li>
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