function ClearElements() {
	[
		'script',
		'panel1',
		'panel2',
		'panel3'
	].forEach((id) => document.getElementById(id).innerHTML = '');
}

function SetStatus(status) {
	document.body.dataset.status = status;

	['query'].forEach((id) => {
		document.getElementById(id)[status === 'generating' ? 'setAttribute' : 'removeAttribute']('disabled', '');
	});

	if(status === 'generating'){
		
	}
}

ClearElements();
SetStatus('ready');
if(comicId) {
	fetch('/api/detail/'+comicId)
		.then((response) => response.json())
		.then((data) => {
			if(!data || !data.script){
				SetStatus('error');
				return;
			}
			const script = data.script;
			console.log("response", script);

			document.getElementById("query").innerHTML = `${data.prompt}`;
			document.getElementById("script").innerHTML = `<li><h2>${script.title}</h2></li>`;

			if(script.panels && script.panels.length){
				script.panels.forEach((panel, idx) => {
					// Support older scripts that had character property
					if(!panel.action && panel.character) panel.action = panel.character;

					document.getElementById("script").innerHTML += `
					<li>
						<h3>Panel ${idx + 1}</h3>
						<ul>
							<li>
								<table>
									<tr><td>Description</td> <td>${panel.scene}</td></tr>
									<tr><td>Action</td> <td>${panel.action}</td></tr>
									<tr><td>Dialog</td> <td>${panel.dialog}</td></tr>
									<tr><td>Background</td> <td>${panel.background}</td></tr>
								</table>
							</li>
						</ul>
					</li>
					`;

					document.getElementById('panel' + (idx + 1)).innerHTML = `Rendering...`;

					document.getElementById('panel' + (idx + 1)).innerHTML = `
						<img class="background" src="/assets/backgrounds/${data.backgrounds[idx]}"/>
						<img class="character" src="/assets/character_art/${panel.action.toLowerCase()}.png"/>
						`;
					if(panel.dialog)
						document.getElementById('panel' + (idx + 1)).innerHTML += `
							<div class="dialog-container">
							<div class="dialog bubble speech" title="Speech Balloon">${panel.dialog}</div>
							</div>
							`;

				});
				SetStatus('');
			}
		});
} else {
	SetStatus('error');
}