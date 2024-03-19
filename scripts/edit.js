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
	fetch('/api/detail/'+comicId+'/?c='+(Math.floor(Math.random()*1000000)))
		.then((response) => response.json())
		.then((data) => {
			if(!data || !data.script){
				SetStatus('error');
				return;
			}
			window.stripData = data;
			const script = data.script;
            console.log("data", data);
			console.log("response", script);

			if (!script.credits) {
				script.credits = {
					script: '',
					image: '',
					background: '',
					action: ''
				};
			}

			window['comicTitle'] = script.title;

			document.getElementById("query").innerHTML = `${data.prompt}`;
			document.getElementById("script").innerHTML = `<li><input name="script-title" value="${script.title}"/></li>`;
            document.getElementById("strip-title").innerText = script.title;

			// Add the credits
			document.getElementById("script").innerHTML += `<li>
				<ul class="credits">
					<li><span>Script: </span><span><input name="script-credit-script" value="${script.credits.script}"/></span></li>
					<li><span>Images: </span><span><input name="script-credit-image" value="${script.credits.image}"/></span></li>
					<li><span>Backgrounds: </span><span><input name="script-credit-background" value="${script.credits.background}"/></span></li>
					<li><span>Actions: </span><span><input name="script-credit-action" value="${script.credits.action}"/></span></li>
				</ul>
			</li>`;

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
                                    <tr><td>Description</td> <td><textarea name="script-panel-${idx}-scene">${panel.scene}</textarea></td></tr>
                                    <tr><td>Action</td> <td><input name="script-panel-${idx}-action" value="${panel.action}"/></td></tr>
                                    <tr><td>Alt Action</td> <td><input name="script-panel-${idx}-altAction" value="${panel.altAction}"/></td></tr>
                                    <tr><td>Dialog</td> <td><input name="script-panel-${idx}-dialog" value="${panel.dialog}"/></td></tr>
                                    <tr><td>Background</td> <td><textarea name="script-panel-${idx}-background">${panel.background}</textarea></td></tr>
                                    <tr><td>Background Url</td> <td><input name="script-panel-${idx}-background_url" value="${panel.background_url}"/></td></tr>
                                    <tr><td>Stored File</td> <td><a href="/assets/backgrounds/${data.backgrounds[idx]}" target="_blank">${data.backgrounds[idx]}</a></td></tr>
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
							<div class="bubble-container">
							<div class="bubble speech" title="Speech Balloon">${panel.dialog}</div>
							</div>
							`;

				});
				SetStatus('');
			}
		});
} else {
	SetStatus('error');
}

function SaveStrip(saveObj){
	if(!saveObj) return;

	// document.getElementById('save').setAttribute('disabled', 'true');
	// document.getElementById('permalink').style.display = null;

	const formData = new FormData();
    formData.append('id', saveObj.id);
	formData.append('prompt', saveObj.prompt);
	formData.append('title', saveObj.title);
	console.log("adding to form", saveObj.script);
	formData.append('script', JSON.stringify(saveObj.script));
	// formData.append('bkg1', saveObj.backgrounds[0]);
	// formData.append('bkg2', saveObj.backgrounds[1]);
	// formData.append('bkg3', saveObj.backgrounds[2]);
	// formData.append('fg1', saveObj.foregrounds[0]);
	// formData.append('fg2', saveObj.foregrounds[1]);
	// formData.append('fg3', saveObj.foregrounds[2]);

	fetch('/api/update/?c='+(Math.floor(Math.random()*1000000)), {
		method: 'POST',
		body: formData
	})
		.then(response => response.json())
		.then(data => {
			// if(!data || !data.response || !data.response.comicId) {
			// 	document.getElementById('save').style.display = 'initial';
			// 	document.getElementById('save').removeAttribute('disabled');
			// 	document.getElementById('permalink').style.display = 'initial';
			// 	document.getElementById('permalink').innerHTML = `
			// 		There was a problem saving.
			// 	`;
			// }
			// document.getElementById('save').style.display = null;
			// document.getElementById('permalink').style.display = 'initial';
			// document.getElementById('permalink').innerHTML = `
            //     <a href="/detail/${data.response.permalink}">
			// 		<img class="burst" src="/assets/images/speech_bubble.svg" />
			// 		<span class="cartoon-font">Permalink</span>
			// 	</a>
			// `;
			console.log('Success:', data);
            if(!data.error) alert("Success! ("+data.response.values+")");
            else alert("Error: "+data.error);
		})
		.catch(error => console.error('Error:', error));
}

document.getElementById('download').addEventListener("click", () => {
    const panelFieldNames = [
        "scene",
        "action",
        "altAction",
        "dialog",
        "background",
        "background_url"
    ];
	let data = window.stripData;

    data.script.title = document.querySelector("[name=script-title]").value;
    data.title = data.script.title;

	if (!data.script.credits) data.script.credits = {};
	data.script.credits.script = document.querySelector("[name=script-credit-script]").value;
	data.script.credits.image = document.querySelector("[name=script-credit-image]").value;
	data.script.credits.background = document.querySelector("[name=script-credit-background]").value;
	data.script.credits.action = document.querySelector("[name=script-credit-action]").value;

    data.script.panels.forEach((panel, idx) => {
        for(let field of panelFieldNames) {
            data.script.panels[idx][field] = document.querySelector("[name=script-panel-" + idx + "-" + field + "]").value;
        }
    });


    console.log("to save:", data);

    SaveStrip(data);
});

document.getElementById('thumbnail').addEventListener("click", () => {

    let data = window.stripData;
    console.log("to save:", data);

    const formData = new FormData();
    formData.append('id', comicId);
	formData.append('background', data.backgrounds[1]);
	formData.append('foreground', data.script.panels[1].action.toLowerCase());

    fetch('/api/thumbnail/?c='+(Math.floor(Math.random()*1000000)), {
		method: 'POST',
		body: formData
	})
		.then(response => response.json())
		.then(data => {
			console.log('Success:', data);
            if(!data.error) alert("Success! ("+data.response.values+")");
            else alert("Error: "+data.error);
		})
		.catch(error => console.error('Error:', error));

});