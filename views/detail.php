<script>
	const comicId = '<?php echo $path[2] ?>';
</script>

<h2>Detail</h2>
<div id="query"></div>
<div id="output">
	<h2>Script</h2>
	<ul id="script"></ul>
	<h2>Composite</h2>
	<div id="strip">
		<div id="panel1" class="panel"></div>
		<div id="panel2" class="panel"></div>
		<div id="panel3" class="panel"></div>
	</div>
</div>

<script>

function ClearElements() {
	[
		'script',
		'panel1',
		'panel2',
		'panel3'
	].forEach((id) => document.getElementById(id).innerHTML = '');
}

function SetStatus(status) {
	document.body.className = status;

	['query'].forEach((id) => {
		document.getElementById(id)[status === 'generating' ? 'setAttribute' : 'removeAttribute']('disabled', '');
	});

	if(status === 'generating'){
		
	}
}

ClearElements();
SetStatus('generating');
if(comicId) {
fetch('/server/detail.php?id='+comicId)
	.then((response) => response.json())
	.then((data) => {
		if(!data || !data.script){
			SetStatus('error');
			return;
		}
		const script = data.script;
		console.log("response", script);

		document.getElementById("query").innerHTML = `${data.prompt}`;

		if(script.panels && script.panels.length){
			script.panels.forEach((panel, idx) => {
				document.getElementById("script").innerHTML += `
				<li>
					Panel ${idx + 1}
					<ul>
					<li>Character: ${panel.character}</li>
					<li>Dialog: ${panel.dialog}</li>
					<li>Background: ${panel.background}</li>
					</ul>
				</li>
				`;

				document.getElementById('panel' + (idx + 1)).innerHTML = `Rendering...`;

				document.getElementById('panel' + (idx + 1)).innerHTML = `
					<img class="background" src="/assets/backgrounds/${data.backgrounds[idx]}"/>
					<img class="character" src="/assets/character_art/${panel.character.toLowerCase()}.png"/>
					`;
				if(panel.dialog)
					document.getElementById('panel' + (idx + 1)).innerHTML += `
						<div class="dialog">${panel.dialog}</div>
						`;

			});
			SetStatus('');
		}
	});
} else {
	SetStatus('error');
}
</script>