import { ScriptRenderer } from "./modules/ScriptRenderer.js";
import { ComicRenderer } from "./modules/ComicRenderer.js";
import { CharacterAction } from "./modules/CharacterAction.js";
import { ComicGenerator } from "./modules/ComicGenerator.js";

let api, comicRenderer, scriptRenderer;

document.addEventListener("DOMContentLoaded", () => {
	comicRenderer = new ComicRenderer({ el: document.querySelector(".strip-container") });
	scriptRenderer = new ScriptRenderer({ el: document.querySelector("#script") });
	api = new ComicGenerator({
		onUpdate: (script, progress) => {
			comicRenderer.LoadScript(script);
			scriptRenderer.LoadScript(script);
			UpdateProgress(progress || 0);
		}
	});

	AttachUiEvents();

	SetStatus('ready');

});

async function GenerateStrip(premise) {
	ClearElements();
	UpdateProgress(0);
	SetStatus('generating');

	const textModel = document.getElementById('script-model').value;
	const imageModel = document.getElementById('image-model').value;

	let script = await api.WriteScript(premise, {model: textModel});
	if(!script || script.error) {
		SetStatus('error');
		return;
	}
	// TODO: handle errors for each function response
	await api.WriteBackground({model: textModel});
	await api.DrawBackground({model: imageModel});
	await api.WriteAction({model: textModel});

	//TODO: Check the renderer progress. Handle error if <100 at this point.

	document.getElementById('query').value = '';
	document.getElementById('save').style.display = 'initial';
	SetStatus('complete');
}

function AttachUiEvents() {

	const UIevents = [
		{
			selector: "#generate",
			event: "click",
			handler: () => {
				const query = document.getElementById('query');
				//if(!query || !query.value || query.value.length > 140) return;
			
				let safeQuery = query.value.replace(/[\\"]/g, '\\$&').replace(/\u0000/g, '\\0');
			
				GenerateStrip(safeQuery);
			}
		},
		{
			selector: "#save",
			event: "click",
			handler: (e) => {
				const query = document.getElementById('query');
				if(!query) return;

				document.getElementById('save').setAttribute('disabled', 'true');
				api.SaveStrip();
			}
		}
	];

	for (const event of UIevents) {
		document.querySelectorAll(event.selector).forEach(el => {
			el.addEventListener(event.event, event.handler);
		});
	}

}

function ClearElements() {
	[
		'script',
		// 'panel1',
		// 'panel2',
		// 'panel3',
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
	amount = amount || 0;
	console.log("Update:", amount);
	const el = document.getElementById("progress");
	el.setAttribute("value", amount);
	el.innerHTML = amount + "%";
}