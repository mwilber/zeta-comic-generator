<script>
	const comicId = '<?php echo $path[2] ?>';
</script>

<h2>Premise</h2>
<div id="query" class="premise"></div>
<h2>Composite</h2>
<div id="strip">
	<div id="panel1" class="panel"></div>
	<div id="panel2" class="panel"></div>
	<div id="panel3" class="panel"></div>
	<div class="strip-controls">
		<button id="download" class="cartoon-button">
			<img class="burst" src="/assets/images/burst.svg" />
			<span class="cartoon-font">Download</span>
		</button>
	</div>
</div>
<div id="output"></div>
<h2>Script</h2>
<ul id="script"></ul>

<script type="text/javascript" src="/scripts/html2canvas.min.js"></script>
<script type="text/javascript" src="/scripts/detail.js"></script>