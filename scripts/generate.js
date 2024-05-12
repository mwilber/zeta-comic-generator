import { ScriptRenderer } from "./modules/ScriptRenderer.js";
import { ComicRenderer } from "./modules/ComicRenderer.js";
import { CharacterAction } from "./modules/CharacterAction.js";
import { ComicGenerator } from "./modules/ComicGenerator.js";

const api = new ComicGenerator();

document.addEventListener("DOMContentLoaded", () => {
	const comicRenderer = new ComicRenderer({ el: document.querySelector(".strip-container") });
	const scriptRenderer = new ScriptRenderer({ el: document.querySelector("#script") });

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