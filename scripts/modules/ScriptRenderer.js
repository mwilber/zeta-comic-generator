/**
 * @file ScriptRenderer.js
 * @author Matthew Wilber
 * @license GPL-3.0
 *
 * Renders script from Zeta Comic Generator (comicgenerator.greenzeta.com) as html.
 *
 * @param {Object} params - The parameters for rendering the script.
 * @param {HTMLElement} params.el - The HTML element to render the script in.
 * @param {Object} params.script - Zeta Comic Generator script object containing the title, panels, and credits.
 */
export class ScriptRenderer {
	constructor(params) {
		const { el, script } = params;

		this.el = el;
		if (script) this.script = this.LoadScript(params.script);

		console.log("GZ ScriptRenderer created");
	}

	/**
	 * Renders the script content to the container element.
	 *
	 * This method is responsible for generating the HTML markup for the script, including the title, credits, and individual panels with their scene descriptions, actions, dialogs, and backgrounds.
	 *
	 * @returns {void}
	 */
	async render() {
		if (!this.validate()) return;

		const { title, panels, credits } = this.script;

		this.el.setAttribute("aria-label", "Script");

		this.el.innerHTML = `<li aria-label="Title"><h2>${title}</h2></li>`;

		if (credits && credits.script) {
			// Add the credits
			this.el.innerHTML += `<li aria-label="Credits">
				<ul class="credits">
					<li><span>Script: </span><span>${credits.script}</span></li>
					<li><span>Images: </span><span>${credits.image}</span></li>
					<li><span>Backgrounds: </span><span>${credits.background}</span></li>
					<li><span>Actions: </span><span>${credits.action}</span></li>
				</ul>
			</li>`;
		}

		for (const [idx, panel] of panels.entries()) {
			let dialogHtml = "";
			for (const [idx, dialog] of panel.dialog.entries()) {
				dialogHtml += `<strong>${dialog.character}</strong>: ${dialog.text}`;
			}

			let scene = panel.scene
				? `<tr><td>Description</td> <td>${panel.scene}</td></tr>`
				: "";
			let action = panel.action
				? `<tr><td>Action</td> <td>${panel.action}</td></tr>`
				: "";
			let background = panel.background
				? `<tr><td>Background</td> <td>${panel.background}</td></tr>`
				: "";
			let dialog = dialogHtml
				? `<tr><td>Dialog</td> <td>${dialogHtml}</td></tr>`
				: "";
			this.el.innerHTML += `
					<li aria-label="Panel">
						<h3>Panel ${idx + 1}</h3>
						<ul>
							<li>
								<table>
									${scene}
									${action}
									${dialog}
									${background}
								</table>
							</li>
						</ul>
					</li>
					`;
		}
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
				"ScriptRenderer: Container element not set.",
				this.container
			);
			return false;
		}

		if (!script) {
			console.error("ScriptRenderer: Valid script object not provided.");
			return false;
		}

		if (!script.panels || !script.panels.length) {
			console.error(
				"ScriptRenderer: script.panels missing.",
				this.script
			);
			return false;
		}

		return true;
	}

	/**
	 * Loads and renders a script.
	 *
	 * @param {Object} script - The script to load and render.
	 * @param {string} [script.title] - The title of the script. This is optional.
	 * @returns {void}
	 */
	LoadScript(script) {
		if (!this.validate(script)) return;

		if (!script.title) {
			// Convenience warning. Title is not required.
			console.warn("ScriptRenderer: script.title missing.", this.script);
		}

		this.script = script;
		this.render();
	}
}
