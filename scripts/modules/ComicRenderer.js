import { DialogBalloon } from "./DialogBalloon.js";

export class ComicRenderer {
	constructor (params) {

		const {el, script, size} = params;

		this.el = el;
		this.size = size || 512;
		if (script)
			this.script = this.LoadScript(params.script)

		console.log("GZ ComicRenderer created");
	}

	async render () {
		if (!this.validate()) return;

		const {title, panels} = this.script;

		// Render the panels
		for (const [idx, panel] of panels.entries()) {

			const {images, dialog} = panel || {};

			// Create the panel element
			const panelEl = this.AddPanelElement(panel, "panel" + (idx+1));
			
			if (images && images.length){
				for (const image of images) {
					this.AddLinkedImageToPanel(panel, image.url, image.className);
				}
			}

			if (dialog) {
				for (const line of dialog) {
					if (!line || !line.text) continue;
					let characterImage = panel.images.find(image => image.balloon && image.balloon.character === line.character);
					let {center, pointer} = characterImage.balloon.location || {};
					this.AddImageElementToPanel(
						panel, 
						await DialogBalloon.RenderImage(
							line.text, 
							{size: this.size, center,pointer}
						)
					);
				}
			}
		}

		// Render the title
		this.AddTitleElement(title);

		console.log("post render script", this.script);
	}

	validate (script) {
		script = script || this.script;

		if (!this.el) {
			console.error("ComicRenderer: Container element not set.", this.container);
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

	AddImageElementToPanel (panel, image) {
		if (!panel.panelEl) {
			console.error("Comic Renderer: Panel element unavailable.", panel);
			return;
		}
		if (!image) return;
		panel.panelEl.appendChild(image);
	}

	AddLinkedImageToPanel (panel, url, className) {
		if (!panel.panelEl) {
			console.error("Comic Renderer: Panel element unavailable.", panel);
			return;
		}
		panel.panelEl.innerHTML += `
			<img class="${className}" src="${url}"/>
		`;
	}

	AddPanelElement (panel, id) {
		const panelEl = document.createElement("div");
		panelEl.id = id;
		panelEl.className = "panel";
		//panelEl.innerHTML = `Rendering...`;
		panel.panelEl = panelEl;
		this.el.appendChild(panelEl);

		return panelEl;
	}

	AddTitleElement (title) {
		const titleEl = document.createElement("h3");
		titleEl.id = "strip-title";
		titleEl.innerText = title || "";
		this.el.appendChild(titleEl);
	}

	/**
	 * 
	 * @param {object} script 
	 */
	LoadScript (script) {
		if (!this.validate(script)) return;

		if (!script.title) {
			// Convenience warning. Title is not required.
			console.warn("ComicRenderer: script.title missing.", this.script);
		}

		this.script = script;
		this.render();
	}
}