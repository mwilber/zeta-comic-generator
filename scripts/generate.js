function ClearElements() {
	[
		'title',
		'script',
		'panel1',
		'panel2',
		'panel3',
		'permalink'
	].forEach((id) => document.getElementById(id).innerHTML = '');

	[
		'save',
		'permalink'
	].forEach((id) => document.getElementById(id).style.display = null);
}

function SetStatus(status) {
	document.body.className = status;

	['generate', 'query'].forEach((id) => {
		document.getElementById(id)[status === 'generating' ? 'setAttribute' : 'removeAttribute']('disabled', '');
	});

	const el = document.getElementById("status");
	el.innerHTML = status;

	if(status === 'generating'){
		
	}
}

function UpdateProgress(amount) {
	if(isNaN(window.progress) || amount === 0) window.progress = amount;
	else window.progress += amount;
	console.log("Update:", window.progress);
	const el = document.getElementById("progress");
	el.setAttribute("value", window.progress);
	el.innerHTML = window.progress + "%";
}

async function fetchComic(prompt) {
	
	const partNames = ['first', 'second', 'third'];
    const formData = new FormData();
	formData.append('query', prompt);

	let comic = {};

    const script = await queryApi('/api/gpt_script', formData);
	let errorMsg = '';
	if(!script || !script.json || !script.json.panels || !script.json.panels.length) errorMsg = "Script object not returned.";
	if(script.error) errorMsg = script.error.message;

	if(errorMsg) return {error: errorMsg};
	else comic.script = script.json;

	UpdateProgress(13);
	
	const altDialog = await fetchAltSceneComponent(comic.script.panels, 'gpt_dialog');
	if(!altDialog.length) return {error: "Dialog not received."}
	altDialog.forEach((dialog, idx) => {
		comic.script.panels[idx].altDialog = comic.script.panels[idx].dialog;
		comic.script.panels[idx].dialog = dialog;
	});
	UpdateProgress(9);

	const altBackground = await fetchAltSceneComponent(comic.script.panels, 'gpt_background');
	if(!altBackground.length) return {error: "Background descriptions not received."}
	altBackground.forEach((bkg, idx) => {
		comic.script.panels[idx].background = bkg;
	});
	UpdateProgress(9);

	for(let [idx, panel] of comic.script.panels.entries()) {
		// panel.background = await fetchSceneComponent(prompt + ' - ' + panel.scene, 'gpt_background', 'background');
		// UpdateProgress(3);
		panel.background_url = await renderBackground(idx, panel.background, prompt);
		if(panel.background_url.error) return {error: panel.background_url.error}
		UpdateProgress(20);
		// panel.action = await fetchSceneComponent(panel.scene, 'gpt_action', 'action');
		// UpdateProgress(3);
		// panel.altdialog = await fetchSceneComponent(panel.scene, 'gpt_dialog', 'dialog', prompt, partNames[idx]);
		// UpdateProgress(3);
	}

	const altAction = await fetchAltSceneComponent(comic.script.panels, 'gpt_action');
	if(!altAction.length) return {error: "Character action not received."}
	altAction.forEach((action, idx) => {
		comic.script.panels[idx].action = action.action;
		comic.script.panels[idx].altAction = action.altAction || "";
	});
	UpdateProgress(9);

	console.log(comic);

    return comic.script;
}

async function fetchSceneComponent(scene, endpoint, property, premise, part) {
	let result = "";
	let retry = 3;
	let sceneData = new FormData();
	sceneData.append('query', scene);
	if(premise) sceneData.append('premise', premise);
	if(part) sceneData.append('part', part);

	// Sometimes GPT returns a null, retry up to 2 times to get a usable result.
	while(retry > 0) {
		retry--;
		let response = await queryApi('/api/' + endpoint, sceneData);
		if(response.json) {
			result = response.json[property];
			break;
		}
	}

	return result;
}

async function fetchAltSceneComponent(panels, endpoint) {
	let result = [];
	if(!panels || !panels.length) return result;

	let retry = 3;
	let sceneData = new FormData();
	for(let idx = 0; idx < 3; idx++) {
		sceneData.append(
			'panel'+(idx+1), 
			(panels[idx] && panels[idx].scene) ? panels[idx].scene : ''
		);
	}

	// Sometimes GPT returns a null, retry up to 2 times to get a usable result.
	while(retry > 0) {
		retry--;
		let response = await queryApi('/api/' + endpoint + "_3", sceneData);
		if(response.json && response.json.panels && response.json.panels.length) {
			result = [...response.json.panels];
			break;
		}
	}

	return result;
}

async function renderBackground(idx, description, premise) {
	// Bypass images. For testing prompts
	// return;

	document.getElementById('panel' + (idx + 1)).innerHTML = `Rendering...`;

	let image = await fetchBackground(premise + " - " + description);
	if(!image || !image.data || !image.data.length || image.error){
		let errorMsg = 'Image did not return.';
		if(image.error && image.error.message) errorMsg = image.error.message;
		return {error: errorMsg};
	}

	console.log("image data", image);
	console.log("attempting panel", idx)

	document.getElementById('panel' + (idx + 1)).innerHTML = `
		<img class="background" src="${image.data[0].url}"/>
		`;

	return image.data[0].url;
}

async function fetchBackground(prompt) {
	let result = {};
	let retry = 3;
    const formData = new FormData();
	formData.append('query', prompt);

	// Sometimes GPT returns a null, retry up to 2 times to get a usable result.
	while(retry > 0) {
		retry--;
		let response = await queryApi('/api/image', formData);
		if(response.data && response.data.length) {
			result = response;
			break;
		}
	}

	return result;
}

async function queryApi(apiUrl, formData) {
	try {
		const response = await fetch(apiUrl, {
            method: 'POST',
            body: formData
        });
		const data = await response.json();
		console.log("data", data);
		return data;
	} catch (error) {
		console.error('Error fetching GPT response:', error);
		return 'Error: Unable to connect to GPT API';
	}
}

function SaveStrip(){
	if(!saveObj) return;

	document.getElementById('save').style.display = null;
	document.getElementById('permalink').style.display = null;

	const formData = new FormData();
	formData.append('prompt', saveObj.prompt);
	formData.append('title', saveObj.title);
	formData.append('script', JSON.stringify(saveObj.script));
	formData.append('bkg1', saveObj.backgrounds[0]);
	formData.append('bkg2', saveObj.backgrounds[1]);
	formData.append('bkg3', saveObj.backgrounds[2]);
	formData.append('fg1', saveObj.foregrounds[0]);
	formData.append('fg2', saveObj.foregrounds[1]);
	formData.append('fg3', saveObj.foregrounds[2]);
	//formData.append('thumbnail', saveObj.thumbnail);

	fetch('/api/save', {
		method: 'POST',
		body: formData
	})
		.then(response => response.json())
		.then(data => {
			if(!data || !data.response || !data.response.comicId) {
				document.getElementById('save').style.display = 'initial';
				document.getElementById('permalink').style.display = 'initial';
				document.getElementById('permalink').innerHTML = `
					There was a problem saving.
				`;
			}
			document.getElementById('permalink').style.display = 'initial';
			document.getElementById('permalink').innerHTML = `
				<a href="/detail/${data.response.permalink}">Permalink</a>
			`;
			console.log('Success:', data);
		})
		.catch(error => console.error('Error:', error));
}

async function GenerateStrip(query) {
	ClearElements();
	UpdateProgress(0);
	SetStatus('generating');
	fetchComic(query).then(async (script) => {
		if(!script || script.error){
			SetStatus('error');
			alert("There was a problem generating the script. There may be a problem with your premise or GPT may just be busy at the moment. Check your premise and remove any special characters and try again. [" + script.error +"]");
			return;
		}
		console.log("response", script);

		window["saveObj"] = {prompt: query, script, backgrounds: [], foregrounds: []};

		if(script.panels && script.panels.length){
			for(const [idx,panel] of script.panels.entries()){
				//panel.background = panel.setting;
				document.getElementById("script").innerHTML += `
				<li>
					Panel ${idx + 1}
					<ul>
						<li>Description: ${panel.scene}</li>
						<li>Action: ${panel.action}</li>
						<li>Dialog: ${panel.dialog}</li>
						<li>Background: ${panel.background}</li>
						<li>Suggested Action: ${panel.altAction}</li>
						<li>Inline Dialog: ${panel.altDialog}</li>
					</ul>
				</li>
				`;

				saveObj.backgrounds[idx] = panel.background_url;
				saveObj.foregrounds[idx] = panel.action.toLowerCase() + '.png';
				document.getElementById('panel' + (idx + 1)).innerHTML += `
					<img class="character" src="../assets/character_art/${panel.action.toLowerCase()}.png"/>
					`;
				if(panel.dialog)
					document.getElementById('panel' + (idx + 1)).innerHTML += `
						<div class="dialog bubble speech">${panel.dialog}</div>
						`;

				document.getElementById('save').style.display = 'initial';
			}

			// html2canvas(
			// 	document.querySelector("#panel1"),
			// 	{
			// 		allowTaint: true,
			// 		useCORS: false
			// 	}
			// ).then(canvas => {
			// 	saveObj.thumbnail = canvas.toDataURL();
			// 	document.body.appendChild(canvas)
			// });

			saveObj.title = script.title;
			document.getElementById('title').innerText = saveObj.title;
			SetStatus('');
		}
	});
}

document.getElementById('generate').addEventListener("click", () => {
	const query = document.getElementById('query');
	if(!query) return;

	GenerateStrip(query.value);
});

document.getElementById('save').addEventListener("click", () => {
	const query = document.getElementById('query');
	if(!query) return;

	SaveStrip();
});