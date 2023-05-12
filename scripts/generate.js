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

	if(status === 'generating'){
		
	}
}

async function fetchComic(prompt) {
	return await queryApi('/server/comic.php?query=' + prompt);
}

async function fetchBackground(prompt) {
	return await queryApi('/server/image.php?query=' + prompt);
}

async function queryApi(apiUrl) {
	try {
		const response = await fetch(apiUrl);
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

	fetch('/server/save.php', {
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
				panel.background = panel.setting;
				document.getElementById("script").innerHTML += `
				<li>
					Panel ${idx + 1}
					<ul>
						<li>Character: ${panel.character}</li>
						<li>Dialog: ${panel.dialog}</li>
						<li>Background: <a href="/server/image.php?query=${panel.background}" target="blank">${panel.background}</a></li>
					</ul>
				</li>
				`;

				// Bypass images. For testing prompts
				// continue;

				document.getElementById('panel' + (idx + 1)).innerHTML = `Rendering...`;

				let image = await fetchBackground(panel.background);
				if(!image){
					SetStatus('error');
					return;
				}

				console.log("image data", image);
				console.log("attempting panel", idx)

				saveObj.backgrounds[idx] = image.data[0].url;
				saveObj.foregrounds[idx] = panel.character.toLowerCase() + '.png';

				document.getElementById('panel' + (idx + 1)).innerHTML = `
					<img class="background" src="${image.data[0].url}"/>
					<img class="character" src="../assets/character_art/${panel.character.toLowerCase()}.png"/>
					`;
				if(panel.dialog)
					document.getElementById('panel' + (idx + 1)).innerHTML += `
						<div class="dialog">${panel.dialog}</div>
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