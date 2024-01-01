<?php 
    $actions = [
		"angry",
		"approval",
		"creeping",
		"explaining",
		"joyous",
		"running",
		"sitting",
		"standing",
		"teaching",
		"terrified",
		"typing"
	];
?>

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
<h2 id="character-art">Character Art...</h2>
<ul class="character-art">
    <?php foreach($actions as $action): ?>
    <li>
        <h3><?php echo $action; ?></h3>
        <img src="/assets/character_art/<?php echo $action; ?>.png" />
    </li>
    <?php endforeach; ?>
</ul>
<h2 id="ai-prompts">AI Prompts</h2>
<ul class="prompts">
	<li>
		<h3>Script</h3>
		<div class="codeblock">
			<code>
				Write a json object containing the description of a humorous, three panel, comic strip.
				In the comic strip our main character, a short green humaniod alien named Alpha Zeta, engages in the following premise: {Premise}
				<br/>
				Include a detailed scene description and words spoken by the main character. 
				<br/>
				The json object has the following properties: `title` and `panels`. 
				<br/>
				The following is a description of each property value:
				<br/>
				`title`: The title of the comic strip. Limit to 50 letters.
				<br/>
				`panels` is an array of objects with the following properties: `scene` and `dialog`
				<br/>
				`scene`: A description of the panel scene including all characters.
				<br/>
				`dialog`: Words spoken by the main character. This can be an empty string if the character is not speaking.
			</code>
		</div>
	</li>
	<li>
		<h3>Backgrounds</h3>
		<div class="codeblock">
			<code>
				The following statements are passages in a story.
				<br/>
				- {Scene from panel 1 of the script}
				<br/>
				- {Scene from panel 2 of the script}
				<br/>
				- {Scene from panel 3 of the script}
				<br/>
				Rewrite each of the three passages as a detailed description of what the scene would look like without the main character present.
				<br/>
				Write your response as a json object with a single property `descriptions`, which is an array of strings containing each of the descriptions.
				<br/>
				Do not reference a panel in the description.
			</code>
		</div>
	</li>
	<li>
		<h3>Character Actions</h3>
		<div class="codeblock">
			<code>
				The following statements describe a three part story.
				<br/>
				- {Scene from panel 1 of the script}
				<br/>
				- {Scene from panel 2 of the script}
				<br/>
				- {Scene from panel 3 of the script}
				<br/>
				For each of the three parts coose one word from the following which most closely describes the action of the main character:
				angry, approval, explaining, ...
				<br/>
				Write your response as a valid json object with a single property `panels`, which is an array of strings containing each of the chosen words.
			</code>
		</div>
	</li>
</ul>