<?php 
	$actions = [
		"angry",
		"approval",
		"creeping",
		"disguised",
		"enamored",
		"explaining",
		"joyous",
		"running",
		"santa_claus_costume",
		"scifi_costume",
		"selfie",
		"sitting",
		"standing",
		"startled",
		"teaching",
		"terrified",
		"typing"
	];
?>

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
	<?php foreach($actions as $action): ?>
	<li>
		<h3><?php echo str_replace('_', ' ', $action); ?></h3>  

		<img src="/assets/character_art/<?php echo $action; ?>.png" />
	</li>
	<?php endforeach; ?>
</ul>
<h2 id="ai-models">AI Models</h2>
<ul class="models">
	<li>
		<h3>Script</h3>
		<ul>
			<li><a href="https://openai.com/product" target="_blank" rel="noopener noreferrer">GPT-4o</a></li>
			<li><a href="https://gemini.google.com/app" target="_blank" rel="noopener noreferrer">Gemini 1.5</a></li>
			<li>Coming soon, Llama 3</li>
			<li>Coming soon, Claude 3.5 Sonnet</li>
		</ul>
	</li>
	<li>
		<h3>Background</h3>
		<ul>
			<li><a href="https://openai.com/dall-e-2" target="_blank" rel="noopener noreferrer">Dall-E 3</a></li>
			<li><a href="https://stability.ai/stable-image" target="_blank" rel="noopener noreferrer">Stable Diffusion XL</a></li>
			<li><a href="https://aws.amazon.com/bedrock/titan/" target="_blank" rel="noopener noreferrer">Titan Image</a></li>
		</ul>
	</li>
</ul>
<h2 id="ai-prompts">AI Prompts</h2>
<ul class="prompts">
	<li>
		<h3>Script</h3>
		<div class="codeblock">
			<code>
				You are a cartoonist and humorist. Write the script for a three panel comic strip.<br/>
				In the comic strip our main character, a short green humaniod alien named Alpha Zeta, engages in the following premise: {p0}<br/>
				Include a detailed scene description and words spoken by the main character.<br/>
				Output your response as a valid json object in the follwing format:<br/>
				{<br/>
				&nbsp;&nbsp;"title": "",<br/>
				&nbsp;&nbsp;"panels": [<br/>
				&nbsp;&nbsp;&nbsp;&nbsp;{<br/>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"scene": "",<br/>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"dialog": ""<br/>
				&nbsp;&nbsp;&nbsp;&nbsp;},<br/>
				&nbsp;&nbsp;&nbsp;&nbsp;{<br/>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"scene": "",<br/>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"dialog": ""<br/>
				&nbsp;&nbsp;&nbsp;&nbsp;},<br/>
				&nbsp;&nbsp;&nbsp;&nbsp;{<br/>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"scene": "",<br/>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"dialog": ""<br/>
				&nbsp;&nbsp;&nbsp;&nbsp;},<br/>
				&nbsp;&nbsp;]<br/>
				}<br/>
				<br/>
				The following is a description of each property value for the json object:<br/>
				`title`: The title of the comic strip. Limit to 50 letters.<br/>
				`panels` is a 3 element array of objects defining each of the 3 panels in the comic strip.<br/>
				`scene`: A description of the panel scene, including all characters present.<br/>
				`dialog`: Words spoken by Alpha Zeta. He is the only character that speaks. Do not label the dialog with a character name. This can be an empty string if the character is not speaking.
			</code>
		</div>
	</li>
	<li>
		<h3>Backgrounds</h3>
		<div class="codeblock">
			<code>
			You are a talented artist who draws background art for animated cartoons.<br/>
			The following describes three scenes in a cartoon featuring the character Alpha Zeta:<br/>
			- {scene description 1}<br/>
			- {scene description 2}<br/>
			- {scene description 3}<br/>
			<br/>
			For each scene, write a description of the background behind Alpha Zeta.<br/>
			Include enough detail necessary for an AI image generator to render an image of your description.<br/>
			Output your response as a valid json object in the follwing format:<br/>
			{<br/>
			&nbsp;&nbsp;descriptions: [<br/>
			&nbsp;&nbsp;&nbsp;&nbsp;"background description 1",<br/>
			&nbsp;&nbsp;&nbsp;&nbsp;"background description 2",<br/>
			&nbsp;&nbsp;&nbsp;&nbsp;"background description 3"<br/>
			&nbsp;&nbsp;]<br/>
			}<br/>
			Your descriptions will be written within the following rules:<br/>
			- Do not exceed 500 characters for each description.<br/>
			- Describe each scene as it would look if the main character, Alpha Zeta, is not present.<br/>
			- No characters will speak to each other.<br/>
			- Do not include any items that contain readable text.<br/>
			- Do not reference a comic strip panel.<br/>
			</code>
		</div>
	</li>
	<li>
		<h3>Character Actions</h3>
		<div class="codeblock">
			<code>
			You are a talented artist who directs animated cartoons.<br/>
			The following describes three scenes in a cartoon featuring the character Alpha Zeta:<br/>
			- {scene description 1}<br/>
			- {scene description 2}<br/>
			- {scene description 3}<br/>
			<br/>
			For each of the three scenes choose one word, from the following list, which best describes the action or appearance of the main character:  <?php echo implode(", ", $actions); ?>.<br/>
			Output your response as a valid json object in the follwing format:<br/>
			{<br/>
				&nbsp;&nbsp;panels: [<br/>
					&nbsp;&nbsp;"word1",<br/>
					&nbsp;&nbsp;"word2",<br/>
					&nbsp;&nbsp;"word3"<br/>
				&nbsp;&nbsp;]<br/>
			}<br/>
			</code>
		</div>
	</li>
</ul>