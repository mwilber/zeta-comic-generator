var API_MODE = 'simulation';
API_MODE = 'production';

function ClearElements() {
	[
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
	document.body.dataset.status = status;

	['generate', 'query'].forEach((id) => {
		document.getElementById(id)[status === 'generating' ? 'setAttribute' : 'removeAttribute']('disabled', '');
	});
	document.getElementById('statusdialog').classList[status === 'generating' ? 'add' : 'remove']('active');

	const el = document.getElementById("status");
	el.innerHTML = status;
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
	sceneData.append('mode', API_MODE);
	sceneData.append('query', scene);
	if(premise) sceneData.append('premise', premise);
	if(part) sceneData.append('part', part);

	// Sometimes GPT returns a null, retry up to 2 times to get a usable result.
	while(retry > 0) {
		retry--;
		let response = await queryApi('/api/' + endpoint + '/?c='+(Math.floor(Math.random()*10000000000000000)), sceneData);
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
	formData.append('mode', API_MODE);
	formData.append('query', prompt);

	let comic = {};

    const script = await queryApi('/api/gpt_script/?c='+(Math.floor(Math.random()*10000000000000000)), formData);
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

	let panelEl = document.getElementById('panel' + (idx + 1));
	panelEl.classList.add('rendering');
	panelEl.innerHTML = ``;

	let image = await fetchBackground(premise + " - " + description);
	if(!image){
		SetStatus('error');
		return;
	}

	console.log("image data", image);
	console.log("attempting panel", idx);

	panelEl.classList.remove('rendering');
	panelEl.classList.add('rendered');

	panelEl.innerHTML += `
	<img class="background" src="${image.data[0].url}"/>
	`;
	setTimeout(() => {
		panelEl.classList.remove('rendered');
	}, 1000);

	return image.data[0].url;
}

async function fetchBackground(prompt) {
    const formData = new FormData();
	formData.append('mode', API_MODE);
	formData.append('query', prompt);

	return await queryApi('/api/image/?c='+(Math.floor(Math.random()*10000000000000000)), formData);
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

	fetch('/api/save/?c='+(Math.floor(Math.random()*10000000000000000)), {
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
                <a href="/detail/${data.response.permalink}">
					<img class="burst" src="/assets/images/speech_bubble.svg" />
					<span class="cartoon-font">Permalink</span>
				</a>
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

		document.getElementById("script").innerHTML = `<li><h2>${script.title}</h2></li>`;
		if(script.panels && script.panels.length){
			for(const [idx,panel] of script.panels.entries()){
				//panel.background = panel.setting;
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

				saveObj.backgrounds[idx] = panel.background_url;
				saveObj.foregrounds[idx] = panel.action.toLowerCase() + '.png';
				document.getElementById('panel' + (idx + 1)).innerHTML += `
					<img class="character" src="../assets/character_art/${panel.action.toLowerCase()}.png"/>
					`;
				if(panel.dialog)
					document.getElementById('panel' + (idx + 1)).innerHTML += `
						<div class="bubble-container">
						<div class="bubble speech" title="Speech Balloon">${panel.dialog}</div>
						</div>
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
			SetStatus('complete');
		}
	});
}

function SetCharCount() {
	let el = document.getElementById('character-count');
    let characterCount = document.getElementById('query').value.length;
    let characterleft = 140 - characterCount;

    // console.log(characterleft);

	if(characterleft < 0)
		el.style.color = '#c00';
	else if(characterleft < 15)
		el.style.color = '#600';
	else
		el.style.color = '';

	if(characterleft < 0)
		el.innerText = Math.abs(characterleft) + " over limit.";
	else
		el.innerText = characterleft + " characters left.";

	return true;

}

SetStatus('ready');

document.getElementById('generate').addEventListener("click", () => {
	const query = document.getElementById('query');
	if(!query || !query.value || query.value.length > 140) return;

	let safeQuery = query.value.replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');

	GenerateStrip(safeQuery);
});

['keyup', 'change', 'paste'].forEach(
	(evt) => document.getElementById('query').addEventListener('keyup', SetCharCount)
);

document.getElementById('save').addEventListener("click", () => {
	const query = document.getElementById('query');
	if(!query) return;

	SaveStrip();
});