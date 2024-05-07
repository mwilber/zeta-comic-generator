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

	document.getElementById('save').removeAttribute('disabled');
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

async function fetchComic(prompt, script) {
	
	const partNames = ['first', 'second', 'third'];
	const modelSelect = document.getElementById('script-model');
    const formData = new FormData();
	formData.append('query', prompt);
	formData.append('model', modelSelect.value || 'oai');

	let comic = {};

	if (!script) script = await queryApi('/api/script/?c='+(Math.floor(Math.random()*1000)), formData);
	let errorMsg = '';
	if(!script || !script.json || !script.json.panels || !script.json.panels.length) errorMsg = "Script object not returned.";
	if(script.error) errorMsg = script.error.message;

	if(errorMsg) return {error: errorMsg};
	else comic.script = script.json;

	// Begin recording script credits
	comic.script.credits = {
		script: script.model || "",
		image: "",
		background: "",
		action: "",
	};

	// //TODO: Remove this when done testing
	// return {error: JSON.stringify(comic)};

	UpdateProgress(13);
	
	// const altDialog = await fetchSceneComponent(comic.script.panels, 'gpt_dialog');
	// if(!altDialog.length) return {error: "Dialog not received."}
	// altDialog.forEach((dialog, idx) => {
	// 	comic.script.panels[idx].altDialog = comic.script.panels[idx].dialog;
	// 	comic.script.panels[idx].dialog = dialog;
	// });
	UpdateProgress(9);

	const altBackground = await fetchSceneComponent(comic.script.panels, 'background');
	if(!altBackground.result.length) return {error: "Background descriptions not received."}
	altBackground.result.forEach((bkg, idx) => {
		comic.script.panels[idx].background = bkg;
	});
	comic.script.credits.background = altBackground.model;
	UpdateProgress(9);

	for(let [idx, panel] of comic.script.panels.entries()) {
		let backgroundImg = await renderBackground(idx, panel.background, prompt);
		panel.background_url = backgroundImg.url;
		if(panel.error) return {error: panel.background_url.error}
		comic.script.credits.image = backgroundImg.model;
		UpdateProgress(20);
	}

	const altAction = await fetchSceneComponent(comic.script.panels, 'action');
	if(!altAction.result.length) return {error: "Character action not received."}
	altAction.result.forEach((action, idx) => {
		comic.script.panels[idx].action = action.action;
		comic.script.panels[idx].altAction = action.altAction || "";
	});
	comic.script.credits.action = altAction.model;
	UpdateProgress(9);

	console.log(comic);

    return comic.script;
}

async function fetchSceneComponent(panels, endpoint) {
	let result = [];
	let model = "";
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
		let response = await queryApi('/api/' + endpoint + '/?c='+(Math.floor(Math.random()*1000)), sceneData);
		if(response.json && response.json.panels && response.json.panels.length) {
			result = [...response.json.panels];
			model = response.model;
			break;
		} else if(response.json && response.json.descriptions && response.json.descriptions.length) {
			result = [...response.json.descriptions];
			model = response.model;
			break;
		}
	}

	return {result, model};
}

async function renderBackground(idx, description, premise) {
	// Bypass images. For testing prompts
	// return;

	let panelEl = document.getElementById('panel' + (idx + 1));
	panelEl.classList.add('rendering');
	panelEl.innerHTML = ``;

	let image = await fetchBackground(description);
	if(!image || !image.json || !image.json.url || image.error){
		let errorMsg = 'Image did not return.';
		if(image.error && image.error.message) errorMsg = image.error.message;
		return {error: errorMsg};
	}

	//console.log("image data", image);
	console.log("attempting panel", idx);

	panelEl.classList.remove('rendering');
	panelEl.classList.add('rendered');

	panelEl.innerHTML += `
	<img class="background" src="${image.json.url}"/>
	`;
	setTimeout(() => {
		panelEl.classList.remove('rendered');
	}, 1000);

	return {url: image.json.url, model: image.model};
}

async function fetchBackground(prompt) {
	let result = {};
	let retry = 3;
	const modelSelect = document.getElementById('image-model');
    const formData = new FormData();
	formData.append('mode', API_MODE);
	formData.append('query', prompt);
	formData.append('model', modelSelect.value || 'oai');



	// Sometimes GPT returns a null, retry up to 2 times to get a usable result.
	while(retry > 0) {
		retry--;
		let response = await queryApi('/api/image/?c='+(Math.floor(Math.random()*1000)), formData);
		if(response.data && response.json) {
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
		console.log("API Response", data);
		return data;
	} catch (error) {
		console.error('Error fetching GPT response:', error);
		return 'Error: Unable to connect to GPT API';
	}
}

function SaveStrip(){
	if(!saveObj) return;

	document.getElementById('save').setAttribute('disabled', 'true');
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

	fetch('/api/save/?c='+(Math.floor(Math.random()*1000000)), {
		method: 'POST',
		body: formData
	})
		.then(response => response.json())
		.then(data => {
			if(!data || !data.response || !data.response.comicId) {
				document.getElementById('save').style.display = 'initial';
				document.getElementById('save').removeAttribute('disabled');
				document.getElementById('permalink').style.display = 'initial';
				document.getElementById('permalink').innerHTML = `
					There was a problem saving.
				`;
			}
			document.getElementById('save').style.display = null;
			document.getElementById('permalink').style.display = 'initial';
			document.getElementById('permalink').innerHTML = `
                <a href="/detail/${data.response.permalink}">
					<img class="burst" src="/assets/images/speech_bubble.svg" />
					<span class="cartoon-font">Permalink</span>
				</a>
			`;
			console.log('Success:', data);
			window.location.replace("/detail/"+data.response.permalink);
		})
		.catch(error => console.error('Error:', error));
}

async function GenerateStrip(query, override) {
	ClearElements();
	UpdateProgress(0);
	SetStatus('generating');
	fetchComic(query, override).then(async (script) => {
		if(!script || script.error){
			SetStatus('error');
			alert("There was a problem generating the script. There may be a problem with your premise or GPT may just be busy at the moment. Check your premise and remove any special characters and try again. [" + script.error +"]");
			return;
		}
		console.log("response", script);

		window["saveObj"] = {prompt: query, script, backgrounds: [], foregrounds: []};

		document.getElementById("script").innerHTML = `<li><h2>${script.title}</h2></li>`;
        document.getElementById("strip-title").innerText = script.title;

		// Add the credits
		document.getElementById("script").innerHTML += `<li>
			<ul class="credits">
				<li><span>Script: </span><span>${script.credits.script}</span></li>
				<li><span>Images: </span><span>${script.credits.image}</span></li>
				<li><span>Backgrounds: </span><span>${script.credits.background}</span></li>
				<li><span>Actions: </span><span>${script.credits.action}</span></li>
			</ul>
		</li>`;

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
				if(panel.dialog){
					renderDialog(panel.dialog, panel.action.toLowerCase())
						.then((canvas) => {
							document.getElementById('panel' + (idx + 1))
								.appendChild(canvas);
						});
				}

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
			document.getElementById('query').value = '';
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

	let safeQuery = query.value.replace(/[\\"]/g, '\\$&').replace(/\u0000/g, '\\0');

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