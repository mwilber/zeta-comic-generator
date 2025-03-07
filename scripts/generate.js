import { ComicGeneratorApi } from "./modules/ComicGeneratorApi.js";
import { ComicRenderer } from "./modules/ComicRenderer/ComicRenderer.js";
import { ScriptRenderer } from "./modules/ScriptRenderer.js";

/**
 * The main entry point for the comic generation application. This script sets up 
 * the necessary components, attaches UI event handlers, and handles the logic for 
 * generating and saving comic strips.
 */
let api, comicRenderer, scriptRenderer;

/**
 * Initializes the comic generation application when the DOM content has finished loading.
 * This function sets up the ComicRenderer and ScriptRenderer instances, attaches UI event handlers,
 * and sets the application status to "ready".
 */
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

	// TODO: Remove this when the API is ready
	window.crender = comicRenderer;

	AttachUiEvents();
	SetDefaultSelections();

	api.GetMetrics().then((metrics)=>{
		if(metrics && metrics.limitreached === true){
			//alert("The daily limit for generating comics has been reached. Please try again tomorrow.");
			const dialog = document.getElementById("errordialog");
			dialog.classList[
				dialog.classList.contains("active") ? "remove" : "add"
			]("active");
			dialog.setAttribute("aria-hidden", "false");
			// Focus the first child of dialog element
			const closeBtn = dialog.querySelector(".close");
			if (closeBtn) {
				closeBtn.focus();
			}
		}
	});

	SetStatus("ready");
});


/**
 * Sets the default selections for the script and image models.
 * The selected values are retrieved from localStorage, if available, or a default value is used.
 * After setting the values, a 'change' event is dispatched on the corresponding elements to trigger any related functionality.
 */
function SetDefaultSelections() {
	const defaultConceptSelection = "o";
	const defaultSelection = "gpt";
	const defaultImageSelection = "oai";
	// TODO: Simplify this
	const storyModelEl = document.getElementById("story-model");
	const scriptModelEl = document.getElementById("script-model");
	const imageModelEl = document.getElementById("image-model");

	let savedStoryModel = localStorage
		? localStorage.getItem("story-model-select")
		: null;
	let savedScriptModel = localStorage
		? localStorage.getItem("script-model-select")
		: null;
	let savedImageModel = localStorage
		? localStorage.getItem("image-model-select")
		: null;

	storyModelEl.value = savedStoryModel || defaultConceptSelection;
	scriptModelEl.value = savedScriptModel || defaultSelection;
	imageModelEl.value = savedImageModel || defaultImageSelection;
	// Fire a change event on the selections
	storyModelEl.dispatchEvent(new Event("change"));
	scriptModelEl.dispatchEvent(new Event("change"));
	imageModelEl.dispatchEvent(new Event("change"));
}

/**
 * Attaches event listeners to various UI elements in the application.
 * This function sets up click, keyup, change, and paste event handlers for
 * elements like the "generate" button, "save" button, "query" input field,
 * and the "image-model" dropdown. The event handlers call functions like
 * `GenerateStrip`, `SaveStrip`, and `SetCharCount` to handle user interactions.
 */
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
			selector: "#story-model",
			event: "change",
			handler: (e) => {
				let {value} = e.target;
				// set localStorage value `story-model-select` to the selected value
				if(localStorage)
					localStorage.setItem("story-model-select", value);
			}
		},
		{
			selector: "#script-model",
			event: "change",
			handler: (e) => {
				let {value} = e.target;
				// set localStorage value `script-model-select` to the selected value
				if(localStorage)
					localStorage.setItem("script-model-select", value);
			}
		},
		{
			selector: "#image-model",
			event: "change",
			handler: (e) => {
				let {value} = e.target;
				// set localStorage value `image-model-select` to the selected value
				if(localStorage)
					localStorage.setItem("image-model-select", value);
				const styleSelectGroup =
					document.getElementById("image-style-label");
				if (value === "sdf")
					styleSelectGroup.style.display = "block";
				else styleSelectGroup.style.display = "none";
			},
		},
		{
			selector: ".dialog .close",
			event: "click",
			handler: (e) => {
				e.target.parentElement.parentElement.classList.remove("active");
				e.target.parentElement.parentElement.setAttribute("aria-hidden", "true");
				document.querySelector("#query").focus();
			},
		},
	];

	for (const event of UIevents) {
		document.querySelectorAll(event.selector).forEach((el) => {
			el.addEventListener(event.event, event.handler);
		});
	}
}

/**
 * Generates a comic strip based on the user's input query.
 *
 * This function is responsible for the entire comic generation process, including:
 * - Retrieving the user's input query
 * - Clearing any existing comic data and elements
 * - Updating the progress and status
 * - Generating the script, background, image, and action for the comic
 * - Clearing the input query and displaying the save button
 *
 * @returns {Promise<void>} A Promise that resolves when the comic generation is complete.
 */
async function GenerateStrip() {
	const query = document.getElementById("query");
	if (!query || !query.value || query.value.length > 140) return;

	comicRenderer.clear();
	api.ClearComicData();
	ClearElements();
	UpdateProgress(0);
	SetStatus("generating");

	let safeQuery = query.value
		.replace(/[\\"]/g, "\\$&")
		.replace(/\u0000/g, "\\0");
	const conceptModel = document.getElementById("story-model").value;
	const textModel = document.getElementById("script-model").value;
	const imageModel = document.getElementById("image-model").value;
	const imageStyle = document.getElementById("image-style").value;

	// Step 1: Generate the story concept
	let concept = await api.WriteConcept(safeQuery, { model: conceptModel });
	if (!concept || concept.error) {
		SetStatus(concept.error == "ratelimit" ? concept.error : "error");
		return;
	}

	// Step 2: Generate the script
	let script = await api.WriteScript(safeQuery, { model: textModel });
	if (!script || script.error) {
		SetStatus(script.error == "ratelimit" ? script.error : "error");
		return;
	}

	// Step 3: Generate the background descriptions
	let background = await api.WriteBackground({ model: textModel });
	if (!background || background.error) {
		SetStatus("error");
		return;
	}

	// Step 4: Render the background images
	let image = await api.DrawBackgrounds({ model: imageModel, style: imageStyle });
	if (!image || image.error) {
		SetStatus("error");
		return;
	}

	// Step 5: Add the character images
	await api.DrawAction();
	// Note: drawAction does not call onUpdate, need to call manually if this is the last step. No longer needed because it is covered in continuity step now.

	// Step 6: Generate new story continuity
	await api.WriteContinuity({ model: textModel });

	//TODO: Check the renderer progress. Handle error if <100 at this point.

	document.getElementById("query").value = "";
	document.getElementById("save").style.display = "initial";
	SetStatus("complete");
}

/**
 * Saves the current comic strip and redirects the user to the detail page for the saved comic.
 *
 * This function is responsible for handling the save functionality for the current comic strip.
 * It disables the save button, calls the `SaveStrip` API to save the strip, and then redirects the
 * user to the detail page for the saved comic if the save is successful. If there is a problem
 * saving the strip, it re-enables the save button and displays an alert.
 */
async function SaveStrip() {
	document.getElementById("save").setAttribute("disabled", "true");
	let data = await api.SaveStrip();

	if (!data || !data.response || !data.response.comicId) {
		document.getElementById("save").style.display = "initial";
		document.getElementById("save").removeAttribute("disabled");
		alert("There was a problem saving.");
	}
	console.log("Success:", data);
	document.getElementById("save").removeAttribute("disabled");
	// window.location.replace("/detail/" + data.response.permalink);
}

/**
 * Clears the content of various elements on the page, including the "script" and 
 * ".strip-container" elements. It also resets the display of the "save" and 
 * "permalink" elements, and removes the "disabled" attribute from the "save" element.
 */
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

/**
 * Sets the status of the application and updates the UI accordingly.
 *
 * @param {string} status - The status to set Currently uses: "ready", "generating" or "error".
 */
function SetStatus(status) {
	if (status === "error") {
		alert(
			"There was a problem generating the strip. Please try again. If the problem persists, try again in a little while."
		);
	} else if (status === "ratelimit") {
		alert("The daily limit for generating comics has been reached. Please try again tomorrow.");
	}

	document.body.dataset.status = status;

	["generate", "query"].forEach((id) => {
		document
			.getElementById(id)
			[status === "generating" ? "setAttribute" : "removeAttribute"](
				"disabled",
				""
			);
	});

	const statusDlg = document.getElementById("statusdialog");
	
	statusDlg.classList[status === "generating" ? "add" : "remove"]("active");

	if(status === "generating") {
		statusDlg.focus();
	} else if(status === "complete") {
		document.getElementById("strip").focus();
	}

	const el = document.getElementById("status");
	el.innerHTML = status;
}

/**
 * Updates the progress display element with the given amount.
 *
 * @param {number} amount - The progress amount to display, as a percentage.
 */
function UpdateProgress(amount) {
	amount = amount || 0;
	console.log("Update:", amount);
	const el = document.getElementById("progress");
	el.setAttribute("value", amount);
	el.innerHTML = amount + "%";
	el.setAttribute("aria-valuetext", `${amount}% complete.`);

	// const elStatus = document.getElementById("status");
	// elStatus.setAttribute("aria-label", `Generating: ${amount}% complete.`);
}

/**
 * Updates the character count display for the "premise" text input field.
 *
 * It calculates the number of characters remaining before a 140 character 
 * limit is reached, and updates the display in the "character-count" 
 * element. The display element's color is also updated to indicate
 * when the character limit has been exceeded.
 *
 * @returns {boolean} Always returns true.
 */
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
