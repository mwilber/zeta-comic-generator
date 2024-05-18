import { CharacterAction } from "../CharacterAction.js";
import { DialogBalloon } from "./DialogBalloon.js";

/**
 * @file ComicRenderer.js
 * @author Matthew Wilber
 * @license GPL-3.0
 * @version 1.0.0
 *
 * Renders comic from Zeta Comic Generator (comicgenerator.greenzeta.com) as html.
 *
 * @param {Object} params - The parameters for rendering the script.
 * @param {HTMLElement} params.el - The HTML element to render the script in.
 * @param {Object} params.script - Zeta Comic Generator script object containing the title, and panels.
 * @param {number} [params.size=512] - The size of the comic panels.
 */
export class ComicRenderer {
	constructor(params) {
		const { el, script, size } = params;

		this.el = el;
		this.size = size || 512;
		if (script) this.script = this.LoadScript(params.script);

		console.log("GZ ComicRenderer created");
	}

	clear() {
		this.el.innerHTML = "";
		this.script = null;
	}

	/**
	 * Renders the comic script by creating panel elements, adding images and dialog to each panel, and rendering the title.
	 *
	 * @returns {void}
	 */
	async render() {
		if (!this.validate()) return;

		// Clear out the container element
		this.el.innerHTML = "";

		const { title, panels } = this.script;

		// Render the panels
		for (const [idx, panel] of panels.entries()) {
			const { images, dialog } = panel || {};

			// Create the panel element
			const panelEl = this.AddPanelElement(panel, "panel" + (idx + 1));

			if (images && images.length) {
				for (const image of images) {
					this.AddLinkedImageToPanel(
						panel,
						image.url,
						image.type
					);
				}

				// Add dialog to characters, where applicable.
				let characterImages = images.filter(
					(image) => image.type === "character"
				);
				for (const cImage of characterImages) {
					// Find dialog attached to the character image
					let {action, character} = cImage;

					let characterDialog = dialog.find (
						(line) => line.character === character
					);
					let {text: line} = characterDialog || {};

					if (action && character && line) {
						let balloonData = CharacterAction.GetDialogBalloonData(
							action,
							character
						);
						this.AddImageElementToPanel(
							panel,
							await DialogBalloon.RenderImage(
								line,
								balloonData
							)
						);
					}
				}
			}

			// if (dialog && dialog.length && images && images.length) {
			// 	for (const line of dialog) {
			// 		if (!line || !line.text) continue;
			// 		let characterImage = panel.images.find(
			// 			(image) => image.character === line.character
			// 		);
			// 		if (characterImage && characterImage.action) {
			// 			let balloonData = CharacterAction.GetDialogBalloonData(
			// 				characterImage.action,
			// 				characterImage.character
			// 			);
			// 			this.AddImageElementToPanel(
			// 				panel,
			// 				await DialogBalloon.RenderImage(
			// 					line.text,
			// 					balloonData
			// 				)
			// 			);
			// 		}
			// 	}
			// }
		}
		// Render the title
		this.AddTitleElement(title);

		console.log("post render script", this.script);
	}

	/**
	 * Validates the provided script object to ensure it has the required properties.
	 *
	 * @param {object} script - The script object to validate.
	 * @returns {boolean} - True if the script object is valid, false otherwise.
	 */
	validate(script) {
		script = script || this.script;

		if (!this.el) {
			console.error(
				"ComicRenderer: Container element not set.",
				this.container
			);
			return false;
		}

		if (!script) {
			console.error("ComicRenderer: Valid script object not provided.");
			return false;
		}

		if (!script.panels || !script.panels.length) {
			console.error("ComicRenderer: script.panels missing.", this.script);
			return false;
		}

		return true;
	}

	/**
	 * Adds an image element to the specified panel.
	 *
	 * @param {object} panel - The panel to add the image to.
	 * @param {HTMLImageElement} image - The image element to add to the panel.
	 */
	AddImageElementToPanel(panel, image) {
		if (!panel.panelEl) {
			console.error("Comic Renderer: Panel element unavailable.", panel);
			return;
		}
		if (!image) return;
		panel.panelEl.appendChild(image);
	}

	/**
	 * Adds a linked image element to the specified panel.
	 *
	 * @param {HTMLElement} panel - The panel element to add the image to.
	 * @param {string} url - The URL of the image to add.
	 * @param {string} className - The CSS class name to apply to the image element.
	 */
	AddLinkedImageToPanel(panel, url, type) {
		console.log("type", type);
		if (!panel.panelEl) {
			console.error("Comic Renderer: Panel element unavailable.", panel);
			return;
		}
		panel.panelEl.innerHTML += `
			<img class="${type}" src="${url}"/>
		`;
	}

	/**
	 * Adds a panel element to the ComicRenderer.
	 *
	 * @param {object} panel - The panel object corresponding to the panel element.
	 * @param {string} id - The unique identifier for the panel.
	 * @returns {HTMLDivElement} - The created panel element.
	 */
	AddPanelElement(panel, id) {
		const panelEl = document.createElement("div");
		panelEl.id = id;
		panelEl.className = "panel";
		//panelEl.innerHTML = `Rendering...`;
		panel.panelEl = panelEl;
		this.el.appendChild(panelEl);

		return panelEl;
	}

	/**
	 * Adds a title element to the ComicRenderer's main element.
	 *
	 * @param {string} title - The title to display.
	 */
	AddTitleElement(title) {
		const titleEl = document.createElement("h3");
		titleEl.id = "strip-title";
		titleEl.innerText = title || "";
		this.el.appendChild(titleEl);
	}

	/**
	 * Loads and validates a script, then renders it.
	 *
	 * @param {Object} script - The script object to load and render.
	 * @param {string} [script.title] - The title of the script. Optional.
	 * @returns {void}
	 */
	LoadScript(script) {
		if (!this.validate(script)) return;

		if (!script.title) {
			// Convenience warning. Title is not required.
			console.warn("ComicRenderer: script.title missing.", this.script);
		}

		this.script = script;
		this.render();
	}
}
