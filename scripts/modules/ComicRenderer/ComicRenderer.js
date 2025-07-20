/**
 * @file ComicRenderer.js
 * @author Matthew Wilber
 * @license GPL-3.0
 *
 * Renders comic from Zeta Comic Generator (comicgenerator.greenzeta.com) as html.
 *
 * @param {Object} params - The parameters for rendering the script.
 * @param {HTMLElement} params.el - The HTML element to render the script in.
 * @param {Object} params.script - Zeta Comic Generator script object containing the title, and panels.
 * @param {number} [params.size=512] - The size of the comic panels.
 */
import { CharacterAction } from "./CharacterAction.js";
import { DialogBalloon } from "./DialogBalloon.js";

export class ComicRenderer {
	constructor(params) {
		const { el, script, size } = params;

		this.el = el;
		this.size = size || 512;
		if (script) this.script = this.LoadScript(params.script);
		this.panelCount = 0;

		console.log("GZ ComicRenderer created");

		// Set the css variable --mobile-panel-width to the width of this.el
		const maxPanelWidth = 450;
		this.setMobilePanelWidth = () => {
			this.panelWidth = Math.min(this.el.offsetWidth, maxPanelWidth) - 4;
			this.el.style.setProperty(
				"--mobile-panel-width",
				this.panelWidth + "px"
			);
			this.el.style.setProperty(
				"--mobile-max-panel-width",
				maxPanelWidth + "px"
			);
		};
		this.setMobilePanelWidth();
		window.addEventListener("resize", this.setMobilePanelWidth);
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
		this.panelCount = 0;

		this.panelContainer = document.createElement("div");
		this.panelContainer.className = "panel-container";
		this.el.appendChild(this.panelContainer);

		this.AddPanelNavigation(this.panelContainer);

		const { title, panels } = this.script;
		// Set the container aria-label attribute to the comic title
		this.el.setAttribute("role", "region");
		this.el.setAttribute("aria-label", "Comic Strip title: " + title);

		// Render the panels
		for (const [idx, panel] of panels.entries()) {
			const { images, dialog } = panel || {};

			// Create the panel element
			const panelEl = this.AddPanelElement(panel, "panel" + (idx + 1));
			panelEl.setAttribute("role", "region");
			panelEl.setAttribute("aria-label", "Panel " + (idx + 1));

			if (images && images.length) {
				for (const image of images) {
					this.AddLinkedImageToPanel(
						panel,
						image.url,
						image.type,
						image.alt
					);
				}

				// Add dialog to characters, where applicable.
				let characterImages = images.filter(
					(image) => image.type === "character"
				);
				for (const cImage of characterImages) {
					// Find dialog attached to the character image
					let { action, character } = cImage;

					let characterDialog = dialog.find(
						(line) => line.character === character
					);
					let { text: line } = characterDialog || {};

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
			console.warn("ComicRenderer: script.panels missing.", this.script);
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
	 * @param {string} [alt] - The alternative text to use for the image element.
	 */
	AddLinkedImageToPanel(panel, url, type, alt) {
		if (!panel.panelEl) {
			console.error("Comic Renderer: Panel element unavailable.", panel);
			return;
		}
		panel.panelEl.innerHTML += `
			<img class="${type}" src="${url}" ${alt ? ` alt="${alt}"` : ""} />
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
		this.panelContainer.appendChild(panelEl);

		this.panelCount++;

		return panelEl;
	}

	AddPanelNavigation(target) {

		["previous", "next"].forEach((direction) => {
			const navBtn = document.createElement("button");
			navBtn.title = direction;
			navBtn.className = "nav-button " + direction;
			if (direction == "previous") navBtn.setAttribute("disabled", "true");
			navBtn.innerText = direction == "next" ? ">" : "<";
			navBtn.addEventListener("click", () => {
				target.scrollLeft += direction == "next" ? this.panelWidth : -this.panelWidth;
			});
			target.addEventListener("scroll", () => {
				if (this.panelCount <= 1) return;
				const currPos = Math.floor(target.scrollLeft / this.panelWidth);
				const buttons = target.parentElement.querySelectorAll(".nav-button");

				for (const button of buttons) {
					button.removeAttribute("disabled");
					if (
						(currPos + 1 >= this.panelCount &&
							button.classList.contains("next")) ||
						(currPos == 0 &&
							button.classList.contains("previous")))
						button.setAttribute("disabled", "true");

				}
			});
			target.parentElement.appendChild(navBtn);
		});
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
		console.log("GZ ComicRenderer loading script");
		console.log(script);
		if (!this.validate(script)) return;

		if (!script.title) {
			// Convenience warning. Title is not required.
			console.warn("ComicRenderer: script.title missing.", this.script);
		}

		this.script = script;
		this.render();
	}
}
