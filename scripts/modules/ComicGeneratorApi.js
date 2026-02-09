/**
 * @file ComicGeneratorApi.js
 * @author Matthew Wilber
 * @license GPL-3.0
 * 
 * The ComicGeneratorApi class is responsible for generating comic strips using AI-powered writing and drawing capabilities.
 * It provides all of the methods necessary to interact with the Zeta Comic Generator php back-end: write the script, 
 * generate background descriptions, draw the backgrounds, write the character actions, and draw the character actions.
 * The class also handles saving the generated comic strip to the server.
 *
 * @param {object} params - An object containing parameters for the ComicGeneratorApi instance.
 * @param {string} params.defaultTextModel - The default text model to use when writing the script. Uses "oai" if not provided.
 * @param {string} params.defaultImageModel - The default image model to use when generating background descriptions. Uses "oai" if not provided.
 * @param {function} params.onUpdate - A callback function that is called whenever the comic data is updated.
 */
import { CharacterAction } from "./ComicRenderer/CharacterAction.js";

export class ComicGeneratorApi {
	constructor(params) {
		this.defaultTextModel = params.defaultTextModel || "oai";
		this.defaultImageModel = params.defaultImageModel || "oai";
		this.onUpdate = params.onUpdate || null;
		this.ClearComicData();
	}

	/**
	 * Clears the comic and premise data.
	 */
	ClearComicData() {
		this.comic = null;
		this.premise = null;
		this.workflowId = null;
		this.messages = [];
		this.credits = {
			concept: "",
			script: "",
			image: "",
			background: "",
			action: "",
		};
	}

	async GetMetrics() {
		const result = await this.fetchApi("metrics", {});
		return result ? result.json : {};
	}

	async WriteConcept(premise, params) {
		const { model, seriesId } = params || {};
		let result = await this.fetchApi("concept", {
			query: premise,
			model: model || this.defaultTextModel,
			seriesId
		});

		if (result && result.error == "ratelimit")
			return { error: "ratelimit" };
		if (
			!result ||
			!result.json ||
			!result.json.concept
		)
			return { error: "Story concept not returned." };

		this.premise = premise;
		this.seriesId = seriesId;
		if (!this.comic) this.comic = {};
		this.comic.concept = result.json.concept;
		this.comic.memory = result.json.memory || [];
		// Add the credits to the script
		this.comic.credits = this.credits;
		this.credits.concept = result.model;

		this.onUpdate(this.comic, this.PercentComplete());
		return this.comic;
	}

	/**
	 * Generates a comic script using the api /script endpoint, using the provided premise and model parameters.
	 *
	 * @param {string} premise - The premise or topic for the comic script.
	 * @param {object} params - An object containing the parameters for the comic script generation.
	 * @param {string} [params.model] - The model to use for the comic script generation. If not provided, the default text model will be used.
	 * @returns {Promise<Object>} - The generated comic script, or an error object if the script could not be generated.
	 */
	async WriteScript(premise, params) {
		const { model } = params || {};
		let result = await this.fetchApi("script", {
			query: premise,
			model: model || this.defaultTextModel,
		});

		if (result && result.error == "ratelimit")
			return { error: "ratelimit" };
		if (
			!result ||
			!result.json ||
			!result.json.panels ||
			!result.json.panels.length
		)
			return { error: "Script object not returned." };

		for (const panel of result.json.panels) {
			if (typeof panel.dialog === "string") {
				panel.dialog = [
					{
						character: "alpha",
						text: panel.dialog,
					},
				];
			}
		}

		if (result.json.summary) {
			this.summary = result.json.summary;
			delete result.json.summary;
		}

		this.premise = premise;
		this.comic = {...this.comic, ...result.json};
		// Add the credits to the script
		this.comic.credits = this.credits;
		this.credits.script = result.model;

		this.onUpdate(this.comic, this.PercentComplete());
		return this.comic;
	}

	/**
	 * Generates background descriptions for each panel in the comic, using the /backgrounds api endpoint.
	 * Background descriptios are passed as the prompt when generating background images.
	 * 
	 * @param {Object} params - The parameters for writing the background.
	 * @param {string} [params.model] - The text model to use for generating the background descriptions.
	 * @returns {Promise<Object>} - The updated comic object with the background descriptions.
	 */
	async WriteBackground(params) {
		const { model } = params || {};
		if (this.PercentComplete < 10) {
			console.log(
				"ComicGenerator: Scene descriptions not written yet. Call WriteScript first."
			);
		}

		let fetchParams = {
			model: model || this.defaultTextModel,
		};

		for (const [idx, panel] of this.comic.panels.entries()) {
			fetchParams["panel" + (idx + 1)] = panel.scene || "";
		}

		const result = await this.fetchApi("background", fetchParams);
		if (
			!result ||
			result.error ||
			!result.json ||
			!result.json.descriptions ||
			!result.json.descriptions.length
		)
			return { error: "Background object not returned." };

		for (const [idx, background] of result.json.descriptions.entries()) {
			this.comic.panels[idx].background = background;
		}
		this.credits.background = result.model;

		this.onUpdate(this.comic, this.PercentComplete());
		return this.comic;
	}

	/**
	 * Helper function to draw the backgrounds for each panel in the comic.
	 * Asynchronously calls DrawBackground() for each panel.
	 *
	 * @param {Object} params - The parameters for drawing the backgrounds.
	 * @returns {Promise<Object>} The updated comic object.
	 */
	async DrawBackgrounds(params) {
		for (const [idx, panel] of this.comic.panels.entries()) {
			let result = await this.DrawBackground(params, panel, idx);
			if (result.error) return result;
			// Update after each image render so we can see the panels appear as they come in.
			this.onUpdate(this.comic, this.PercentComplete());
		}

		return this.comic;
	}

	/**
	 * Draws the background image for a panel in the comic using the api /image endpoint.
	 *
	 * @param {object} params - The parameters for the comic generation.
	 * @param {object} panel - The panel object containing the background description.
	 * @param {number} idx - The index of the panel in the comic.
	 * @returns {Promise<Object>} - An object with an `error` property indicating whether the background image was successfully drawn.
	 */
	async DrawBackground(params, panel, idx) {
		const { model, style } = params || {};
		if (this.PercentComplete < 30) {
			console.log(
				"ComicGenerator: Scene backgrounds not written yet. Call WriteBackground first."
			);
		}

		// If the panel has a background description, go ahead with the AI render
		if (panel.background) {
			const result = await this.fetchApi("image", {
				model: model || this.defaultImageModel,
				query: panel.background,
				style: style || "",
			});
			if (!result || result.error || !result.json || !result.json.url)
				return { error: "Background image not returned." };

			// Ensure there's an images array in the panel
			if (
				!this.comic.panels[idx].images ||
				!this.comic.panels[idx].images.length
			)
				this.comic.panels[idx].images = [];
			// Add the background image to the panel images array
			this.comic.panels[idx].images.push({
				type: "background",
				url: result.json.url,
				alt: "Background image: " + panel.background,
			});
			this.credits.image = result.model;
			return { error: false };
		}
	}

	/**
	 * Writes the action for each panel in the comic using the api /action endpoint.
	 * Available actions are single words associated with individual character art images.
	 * The action word is determined via the api pased on available character art.
	 * 
	 * This function calls DrawAction().
	 *
	 * @param {Object} params - The parameters for the write action.
	 * @param {Object} [params.model] - The text model to use for the action.
	 * @returns {Promise<Object>} - The updated comic object.
	 */
	async WriteAction(params) {
		const { model } = params || {};
		if (this.PercentComplete < 10) {
			console.log(
				"ComicGenerator: Scene descriptions not written yet. Call WriteScript first."
			);
		}

		let fetchParams = {
			model: model || this.defaultTextModel,
		};

		for (const [idx, panel] of this.comic.panels.entries()) {
			fetchParams["panel" + (idx + 1)] = panel.scene || "";
		}

		const result = await this.fetchApi("action", fetchParams);
		if (
			!result ||
			result.error ||
			!result.json ||
			!result.json.panels ||
			!result.json.panels.length
		)
			return { error: "Action object not returned." };

		for (const [idx, panel] of result.json.panels.entries()) {
			this.comic.panels[idx].action = panel.action;
			this.comic.panels[idx].altAction = panel.altAction;
		}

		await this.DrawAction();

		this.credits.action = result.model;

		this.onUpdate(this.comic, this.PercentComplete());
		return this.comic;
	}

	/**
	 * Inserts character art image into the comic script based on the action keyword 
	 * associated with each panel. Defaults to the "standing" action if the panel
	 * does not condain a valid action.
	 * 
	 * This method is called automatically in the `WriteAction()` method.
	 */
	async DrawAction() {
		if (this.PercentComplete < 95) {
			console.log(
				"ComicGenerator: Scene actions not written yet. Call WriteAction first."
			);
		}

		for (const [idx, panel] of this.comic.panels.entries()) {
			if (!panel.action) continue;
			let action = CharacterAction.GetValidAction(panel.action);
			let actionImage = CharacterAction.GetImageUrl(panel.action);
			if (actionImage) {
				panel.images.push({
					type: "character",
					action,
					character: "alpha",
					url: actionImage,
					alt: "Character image: " + "alpha " + "in a " + panel.action + " pose"
				});
			}
		}
	}

	async WriteContinuity(params) {
		const { model } = params || {};

		let fetchParams = {
			model: model || this.defaultTextModel,
		};

		const result = await this.fetchApi("continuity", fetchParams);

		this.comic.continuity = {
			alpha: [...(this.comic.continuity?.alpha || []), ...(result?.json?.alpha || [])],
			event: [...(this.comic.continuity?.event || []), ...(result?.json?.event || [])],
		};
		this.onUpdate(this.comic, this.PercentComplete());
		return this.comic;
	}

	/**
	 * Gets the URL of the panel image for the specified panel index and image type.
	 *
	 * @param {number} panelIdx - The index of the panel.
	 * @param {string} type - The type of the image (e.g. "background").
	 * @returns {string} The URL of the panel image, or an empty string if the image is not found.
	 */
	GetPanelImageUrl(panelIdx, type) {
		if (
			!this.comic ||
			!this.comic.panels[panelIdx] ||
			!this.comic.panels[panelIdx].images
		)
			return "";

		let backgroundImage = this.comic.panels[panelIdx].images.find(
			(image) => image.type === type
		);

		if (backgroundImage) return backgroundImage.url;

		return "";
	}

	/**
	 * Saves the current comic strip to the server using the /save endpoint.
	 * @returns {Promise<Object>} The result of the save operation.
	 */
	async SaveStrip() {
		let scriptExport = JSON.parse(JSON.stringify(this.comic));
		console.log("🚀 ~ ComicGeneratorApi ~ SaveStrip ~ scriptExport:", scriptExport)
		// Clear out images, they'll be saved seperately.
		for (let panel of scriptExport.panels) {
			panel.images = [];
		}
		
		const fetchParams = {
			prompt: this.premise,
			title: this.comic.title,
			script: JSON.stringify(scriptExport),
			summary: this.summary,
			seriesId: this.seriesId,
			continuity: JSON.stringify(scriptExport.continuity),
			memory: JSON.stringify(scriptExport.memory),
			bkg1: this.GetPanelImageUrl(0, "background"),
			bkg2: this.GetPanelImageUrl(1, "background"),
			bkg3: this.GetPanelImageUrl(2, "background"),
			//TODO: remove the split/pop and handle the complete path on the server side
			fg1: this.GetPanelImageUrl(0, "character").split("/").pop(),
			fg2: this.GetPanelImageUrl(1, "character").split("/").pop(),
			fg3: this.GetPanelImageUrl(2, "character").split("/").pop(),
		};

		console.log("Saving comic", fetchParams);

		const result = await this.fetchApi("save", fetchParams);

		return result;
	}

	/**
	 * Calculates the overall progress of the comic generation process based on
	 * populated properties of the comic object.
	 * 
	 * @returns {number} The overall progress as a percentage.
	 */
	PercentComplete() {
		let progress = 0;
		if (!this.comic) return progress;

		if (this.comic.concept) progress += 15;

		if (this.comic.panels) {
			// Each panel has a total progress of 25
			for (const panel of this.comic.panels) {
				if (panel.scene) progress += 5;
				if (panel.images && panel.images.length) progress += 10;
				if (panel.dialog && panel.dialog.length) progress += 5;
				if (panel.background) progress += 5;
				//if (panel.action) progress += 3;
			}
		}

		if (this.comic.continuity) progress += 10;

		return progress;
	}

	/**
	 * Fetches data from the API with the given action and data.
	 *
	 * @param {string} action - The API action to perform.
	 * @param {Object} data - The data to send with the API request.
	 * @returns {Promise<Object>} - The response data from the API, or an error object if the request fails.
	 */
	async fetchApi(action, data) {
		let uri = "/api/" + action + "/?c=" + Math.floor(Math.random() * 100);

		const formData = new FormData();
		Object.keys(data).forEach((key) => {
			formData.append(key, data[key]);
		});

		// If the workflowId is not set, generate a new one
		if (!this.workflowId) {
			this.workflowId = await crypto.subtle
				.digest('SHA-256', new TextEncoder().encode(action + Math.random()))
				.then(hash => 
					Array.from(new Uint8Array(hash))
						.map(b => b.toString(16).padStart(2, '0'))
						.join('')
				);
		}

		formData.append("messages", JSON.stringify(this.messages));
		formData.append("workflowId", this.workflowId);

		let retry = 3;
		// Sometimes GPT returns a null, retry up to 2 times to get a usable result.
		while (retry > 0) {
			retry--;
			try {
				const response = await fetch(uri, {
					method: "POST",
					body: formData,
				});
				const data = await response.json();
				if (!data || (!data.json && !data.response) || data.error) {
					throw data;
				} else {
					console.log("ComicGenerator: API response", data);

					// Store the messages for the next request
					if (data.messages) this.messages = data.messages;
					return data;
				}
			} catch (error) {
				console.error("Error fetching API:", error);
				if (error && error.error == "ratelimit")
					return { error: "ratelimit" };
				else
					return { error };
			}
		}
		return false;
	}
}
