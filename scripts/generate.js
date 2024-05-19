import { ComicGeneratorApi } from "./modules/ComicGeneratorApi.js";
import { ComicRenderer } from "./modules/ComicRenderer/ComicRenderer.js";
import { ScriptRenderer } from "./modules/ScriptRenderer.js";

let api, comicRenderer, scriptRenderer;

document.addEventListener("DOMContentLoaded", () => {
	comicRenderer = new ComicRenderer({
		el: document.querySelector(".strip-container"),
	});
	scriptRenderer = new ScriptRenderer({
		el: document.querySelector("#script"),
	});
	api = new ComicGeneratorApi({
		onUpdate: (script, progress) => {
			comicRenderer.LoadScript(script);
			scriptRenderer.LoadScript(script);
			UpdateProgress(progress || 0);
		},
	});

	AttachUiEvents();

	SetStatus("ready");
});

function AttachUiEvents() {
	const UIevents = [
		{
			selector: "#generate",
			event: "click",
			handler: GenerateStrip,
		},
		{
			selector: "#save",
			event: "click",
			handler: SaveStrip,
		},
		{
			selector: "#query",
			event: "keyup",
			handler: SetCharCount,
		},
		{
			selector: "#query",
			event: "change",
			handler: SetCharCount,
		},
		{
			selector: "#query",
			event: "paste",
			handler: SetCharCount,
		},
		{
			selector: "#image-model",
			event: "change",
			handler: (e) => {
				console.log("target val", e.target.value);
				const styleSelectGroup =
					document.getElementById("image-style-label");
				if (e.target.value === "sdf")
					styleSelectGroup.style.display = "block";
				else styleSelectGroup.style.display = "none";
			},
		},
	];

	for (const event of UIevents) {
		document.querySelectorAll(event.selector).forEach((el) => {
			el.addEventListener(event.event, event.handler);
		});
	}
}

async function GenerateStrip() {
	const query = document.getElementById("query");
	if(!query || !query.value || query.value.length > 140) return;
	
	comicRenderer.clear();
	api.ClearComicData();
	ClearElements();
	UpdateProgress(0);
	SetStatus("generating");

	let safeQuery = query.value
		.replace(/[\\"]/g, "\\$&")
		.replace(/\u0000/g, "\\0");
	const textModel = document.getElementById("script-model").value;
	const imageModel = document.getElementById("image-model").value;

	let script = await api.WriteScript(safeQuery, { model: textModel });
	if (!script || script.error) {
		SetStatus("error");
		return;
	}
	let background = await api.WriteBackground({ model: textModel });
	if (!background || background.error) {
		SetStatus("error");
		return;
	}
	let image = await api.DrawBackgrounds({ model: imageModel });
	if (!image || image.error) {
		SetStatus("error");
		return;
	}
	let action = await api.WriteAction({ model: textModel });
	if (!action || action.error) {
		SetStatus("error");
		return;
	}

	//TODO: Check the renderer progress. Handle error if <100 at this point.

	document.getElementById("query").value = "";
	document.getElementById("save").style.display = "initial";
	SetStatus("complete");
}

async function SaveStrip() {
	document.getElementById("save").setAttribute("disabled", "true");
	let data = await api.SaveStrip();

	if (!data || !data.response || !data.response.comicId) {
		document.getElementById("save").style.display = "initial";
		document.getElementById("save").removeAttribute("disabled");
		alert("There was a problem saving.");
	}
	console.log("Success:", data);
	window.location.replace("/detail/" + data.response.permalink);
}

function ClearElements() {
	[
		"script",
		// 'panel1',
		// 'panel2',
		// 'panel3',
		"permalink",
	].forEach((id) => (document.getElementById(id).innerHTML = ""));

    document.querySelector(".strip-container").innerHTML = `
    <div id="panel1" class="panel"></div>
	<div id="panel2" class="panel"></div>
	<div id="panel3" class="panel"></div>
    `;

	["save", "permalink"].forEach(
		(id) => (document.getElementById(id).style.display = null)
	);

	document.getElementById("save").removeAttribute("disabled");
}

function SetStatus(status) {
	document.body.dataset.status = status;

	if (status === "error") {
		alert(
			"There was a problem generating the strip. Please try again. If the problem persists, try again in a little while."
		);
	}

	["generate", "query"].forEach((id) => {
		document
			.getElementById(id)
			[status === "generating" ? "setAttribute" : "removeAttribute"](
				"disabled",
				""
			);
	});
	document
		.getElementById("statusdialog")
		.classList[status === "generating" ? "add" : "remove"]("active");

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

function SetCharCount() {
	let el = document.getElementById("character-count");
	let characterCount = document.getElementById("query").value.length;
	let characterleft = 140 - characterCount;

	// console.log(characterleft);

	if (characterleft < 0) el.style.color = "#c00";
	else if (characterleft < 15) el.style.color = "#600";
	else el.style.color = "";

	if (characterleft < 0)
		el.innerText = Math.abs(characterleft) + " over limit.";
	else el.innerText = characterleft + " characters left.";

	return true;
}
