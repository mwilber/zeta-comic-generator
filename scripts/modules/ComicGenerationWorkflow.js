/**
 * Shared orchestration for the public Generate page and the MCP comic app.
 * Individual model choices can still be supplied by the site's advanced UI.
 */
export const COMIC_WORKFLOWS = Object.freeze({
	openai: Object.freeze({ story: "gpt", script: "gpt5", image: "gptimage" }),
	google: Object.freeze({ story: "gemthink", script: "gem", image: "nanobanana" }),
	xai: Object.freeze({ story: "grokadv", script: "grok", image: "grokimg" }),
});

export function NormalizeComicWorkflow(workflow) {
	const normalized = String(workflow || "openai").toLowerCase();
	return Object.hasOwn(COMIC_WORKFLOWS, normalized) ? normalized : "openai";
}

export class ComicGenerationWorkflow {
	constructor({ api, onStatus = null }) {
		this.api = api;
		this.onStatus = onStatus;
	}

	async Generate(premise, options = {}) {
		const workflow = NormalizeComicWorkflow(options.workflow);
		const defaults = COMIC_WORKFLOWS[workflow];
		const storyModel = options.storyModel || defaults.story;
		const scriptModel = options.scriptModel || defaults.script;
		const imageModel = options.imageModel || defaults.image;
		const imageStyle = options.imageStyle || "";
		const seriesId = options.seriesId || "";

		this.api.ClearComicData();
		this.Report("concept");
		let result = await this.api.WriteConcept(premise, { model: storyModel, seriesId });
		if (!result || result.error) return this.Failed(result);

		this.Report("script");
		result = await this.api.WriteScript(premise, { model: scriptModel });
		if (!result || result.error) return this.Failed(result);

		this.Report("backgrounds");
		result = await this.api.WriteBackground({ model: scriptModel });
		if (!result || result.error) return this.Failed(result);

		this.Report("images");
		result = await this.api.DrawBackgrounds({ model: imageModel, style: imageStyle });
		if (!result || result.error) return this.Failed(result);

		this.Report("character");
		await this.api.DrawAction();

		this.Report("continuity");
		result = await this.api.WriteContinuity({ model: scriptModel });
		if (!result || result.error) return this.Failed(result);

		this.Report("complete");
		return { comic: this.api.comic, workflow };
	}

	Report(status) {
		if (this.onStatus) this.onStatus(status);
	}

	Failed(result) {
		const error = result && result.error === "ratelimit" ? "ratelimit" : "generation";
		this.Report(error);
		return { error };
	}
}
