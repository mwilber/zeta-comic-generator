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

async function fetchSceneComponent(scene, endpoint, property, premise, part) {
	let result = "";
	let retry = 2;
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

async function fetchComic(prompt) {
	
	const partNames = ['first', 'second', 'third'];
    const formData = new FormData();
	formData.append('query', prompt);

	let comic = {};

    const script = await queryApi('/api/gpt_script', formData);
	if(script.json && script.json.panels && script.json.panels.length) comic.script = script.json;
	UpdateProgress(13);

	for(let [idx, panel] of comic.script.panels.entries()) {
		panel.background = await fetchSceneComponent(prompt + ' - ' + panel.scene, 'gpt_background', 'background');
		UpdateProgress(3);
		panel.background_url = await renderBackground(idx, panel.background, prompt);
		UpdateProgress(20);
		panel.action = await fetchSceneComponent(panel.scene, 'gpt_action', 'action');
		UpdateProgress(3);
		panel.altdialog = await fetchSceneComponent(panel.scene, 'gpt_dialog', 'dialog', prompt, partNames[idx]);
		UpdateProgress(3);
	}

	console.log(comic);

    return comic.script;
}

async function renderBackground(idx, description, premise) {
	// Bypass images. For testing prompts
	// return;

	document.getElementById('panel' + (idx + 1)).innerHTML = `Rendering...`;

	let image = await fetchBackground(premise + " - " + description);
	if(!image){
		SetStatus('error');
		return;
	}

	console.log("image data", image);
	console.log("attempting panel", idx)

	document.getElementById('panel' + (idx + 1)).innerHTML = `
		<img class="background" src="${image.data[0].url}"/>
		`;

	return image.data[0].url;
}

async function fetchBackground(prompt) {
    const formData = new FormData();
	formData.append('query', prompt);

	return await queryApi('/api/image', formData);
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
		if(!script){
			SetStatus('error');
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