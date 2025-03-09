import { ScriptRenderer } from "./modules/ScriptRenderer.js";
import { ComicRenderer } from "./modules/ComicRenderer/ComicRenderer.js";
import { ComicExporter } from "./modules/ComicExporter.js";

/**
 * Initializes the comic rendering and script rendering components, and attaches event handlers to various UI elements in the application.
 *
 * This function is called when the DOM content has finished loading. It performs the following tasks:
 *
 * 1. Creates a new `ComicRenderer` instance and a new `ScriptRenderer` instance, passing in the appropriate DOM elements.
 * 2. Fetches the comic data from the server using the `comicId` variable.
 * 3. Processes the fetched data, including setting the prompt, loading the script and comic into the respective renderers, and attaching event handlers to various UI elements.
 */
document.addEventListener("DOMContentLoaded", () => {
	const comicRenderer = new ComicRenderer({
		el: document.querySelector(".strip-container"),
	});
	const scriptRenderer = new ScriptRenderer({
		el: document.querySelector("#script"),
	});

	if (comicId) {
		fetch(
			"/api/detail/" + comicId + "/?c=" + Math.floor(Math.random() * 100)
		)
			.then((response) => response.json())
			.then((data) => {
				console.log("Detail fetch result", data);

				if (
					!data.script ||
					!data.script.panels ||
					!data.script.panels.length ||
					!data.backgrounds ||
					!data.backgrounds.length
				) {
					console.error("Data missing required fields.", data);
					return;
				}

				const { id, prompt, script, backgrounds, continuity } = data;

				script.prompt = prompt;
				document.getElementById("query").innerHTML = `${prompt}`;

				for (const [idx, panel] of script.panels.entries()) {
					if (!Array.isArray(panel.dialog)) {
						panel.dialog = [
							{ character: "alpha", text: panel.dialog },
						];
					}
					panel.images = [];
					if (backgrounds[idx])
						panel.images.push({
							url: backgrounds[idx],
							type: "background",
							alt: "Background image: " + panel.background,
						});
					panel.images.push({
						url: "/assets/character_art/" + panel.action + ".png",
						type: "character",
						character: "alpha",
						action: panel.action,
						alt: "Character image: " + "alpha " + "in a " + panel.action + " pose"
					});
				}

				scriptRenderer.LoadScript(script);
				comicRenderer.LoadScript(script);

				let continuityEl = document.getElementById("continuity");
				if (continuity?.length) {
					let continuitySorted = {categories: []};
					for (const item of continuity) {
						if (!continuitySorted[item.alias]) {
							continuitySorted[item.alias] = {
								heading: item.heading,
								items: [],
							};
						}
						continuitySorted[item.alias].items.push(item);
					}

					if (continuitySorted.alpha) {
						let alphaEl = document.createElement("div");
						alphaEl.classList.add("character");
						alphaEl.innerHTML = `<h3>${continuitySorted.alpha.heading}:</h3> ${continuitySorted.alpha.items.map(item => item.description).join(", ")}`;
						continuityEl.appendChild(alphaEl);
					}
					if (continuitySorted.event) {
						let eventEl = document.createElement("div");
						eventEl.classList.add("event");
						eventEl.innerHTML = `<h3>${continuitySorted.event.heading}:</h3> ${continuitySorted.event.items.map(item => {
							return `<a href="/gallery/${item.permalink}">${item.description}</a>`
						}).join(", ")}`;
						continuityEl.appendChild(eventEl);
					}

					continuityEl.insertAdjacentHTML("afterbegin", `<h2 class="continuity-title">Notes</h2>`);
				} else {
					continuityEl.style.display = "none";
				}

				AttachUiEvents();
			});
	}

	// TODO: remove this
	window.comicRenderer = comicRenderer;
	window.comicExporter = ComicExporter;
});

/**
 * Attaches event handlers to various UI elements in the application.
 * This function sets up click and focus event handlers for elements
 * such as download buttons, share buttons, and dialog close buttons.
 * The event handlers perform actions like downloading the comic,
 * opening and closing dialogs, copying the share URL to the clipboard,
 * and opening social media share windows.
 */
function AttachUiEvents() {
	const UIevents = [
		{
			selector: "#download-ig",
			event: "click",
			handler: () => ComicExporter.DownloadComic(comicRenderer, "panel"),
		},
		{
			selector: "#download-strip",
			event: "click",
			handler: () => ComicExporter.DownloadComic(comicRenderer, "strip"),
		},
		{
			selector: "#share",
			event: "click",
			handler: () => {
				const dialog = document.getElementById("sharedialog");
				dialog.classList[
					dialog.classList.contains("active") ? "remove" : "add"
				]("active");
				dialog.setAttribute("aria-hidden", "false");
				// Focus the first child of dialog element
				const closeBtn = dialog.querySelector(".close");
				if (closeBtn) {
					closeBtn.focus();
				}
			},
		},
		{
			selector: "#download",
			event: "click",
			handler: () => {
				// First, reload the background images via proxy so html2canvas can use them.
				const backgrounds = document.querySelectorAll(".background");
				backgrounds.forEach((background) => {
					// console.log("changing background to", '/api/imgproxy/?url=' + background.src)
					background.src = "/api/imgproxy/?url=" + background.src;
				});
				// Open the dialog.
				const dialog = document.getElementById("downloaddialog");
				dialog.classList[
					dialog.classList.contains("active") ? "remove" : "add"
				]("active");
				dialog.setAttribute("aria-hidden", "false");
				// Focus the first child of dialog element
				const closeBtn = dialog.querySelector(".close");
				if (closeBtn) {
					closeBtn.focus();
				}
			},
		},
		{
			selector: ".dialog",
			event: "click",
			handler: (e) => e.stopPropagation(),
		},
		{
			selector: ".dialog-wrapper",
			event: "click",
			handler: (e) => {
				e.stopPropagation();
				e.target.classList.remove("active");
				e.target.setAttribute("aria-hidden", "true");
				document.querySelector("#download").focus();
			},
		},
		{
			selector: ".dialog .close",
			event: "click",
			handler: (e) => {
				e.target.parentElement.parentElement.classList.remove("active");
				e.target.parentElement.parentElement.setAttribute("aria-hidden", "true");
				document.querySelector("#download").focus();
			},
		},
		{
			selector: "#shareurl",
			event: "focus",
			handler: (e) => e.target.select(),
		},
		{
			selector: "#cpshare",
			event: "click",
			handler: (e) => {
				let btnEl = document.getElementById("cpshare");
				btnEl.setAttribute("disabled", "");
				navigator.clipboard.writeText(
					document.getElementById("shareurl").value
				);
				setTimeout(() => btnEl.removeAttribute("disabled"), 3000);
			},
		},
		{
			selector: "#twshare",
			event: "click",
			handler: (e) => {
				event.preventDefault();
				window.open(
					"https://twitter.com/share?text=" +
						GetShareMessage() +
						"&url=" +
						encodeURIComponent(
							document.getElementById("shareurl").value
						) +
						"&hashtags=ai,AIart,generativeart,dalle2,openai"
				);
			},
		},
		{
			selector: "#fbshare",
			event: "click",
			handler: (e) => {
				event.preventDefault();
				window.open(
					"https://www.facebook.com/sharer/sharer.php?u=" +
						encodeURIComponent(
							document.getElementById("shareurl").value
						),
					"Facebook",
					`scrollbars=no,resizable=no,status=no,location=no,toolbar=no,menubar=no,width=600,height=300,left=100,top=100`
				);
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
 * Returns a share message for the current comic strip.
 * @returns {string} The share message.
 */
function GetShareMessage() {
	return `Check out my comic strip "${comicRenderer.script.title}" from Zeta Comic Generator. Easily create unique comic strips with the help of OpenAI models and hand drawn character art.`;
}
