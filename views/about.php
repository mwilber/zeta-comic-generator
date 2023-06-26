<?php 
    $actions = [
		"angry",
		"approval",
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
    Massive Language Models at <a href="https://openai.com/product" target="_blank" rel="noopener noreferrer">OpenAI</a> 
    use the premise to write a complete script for a three panel comic strip. 
    The model then elaborates on a scene description for each panel. 
    That description is sent to the <a href="https://openai.com/dall-e-2" target="_blank" rel="noopener noreferrer">Dall-E</a> 
    image generator to render a background image. 
    Finally the model selects from a list of <a href="/about#character-art">character actions</a>, each one representing an image of 
    <a href="https://greenzeta.com/project/illustrations/" target="_blank" rel="noopener noreferrer">Alpha Zeta</a>, 
    the alien mascot of <a href="https://greenzeta.com" target="_blank" rel="noopener noreferrer">GreenZeta.com</a>. 
    All of the assets are combined into a single comic strip!
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