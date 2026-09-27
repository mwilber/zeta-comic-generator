import { ComicRenderer } from "../scripts/modules/ComicRenderer/ComicRenderer.js";

/**
 * Loads permanent comic data using the same permalink endpoint as the detail page.
 * No generation or save endpoints are used while restoring a comic.
 *
 * @param {string} permalink Saved comic identifier, not a URL.
 * @param {string} siteBaseUrl Website origin for the detail API and character art.
 * @returns {Promise<void>}
 */
export async function loadSavedComic(permalink, siteBaseUrl) {
	if (typeof permalink !== "string" || permalink.length !== 32 || !/^[a-f0-9]{32}$/.test(permalink)) {
		throw new Error("Invalid saved comic permalink.");
	}
	const baseUrl = siteBaseUrl.replace(/\/$/, "");
	const response = await fetch(`${baseUrl}/api/detail/${permalink}/`, { cache: "no-store" });
	if (!response.ok) throw new Error("Saved comic request failed.");
	const data = await response.json();
	if (data.error || !Array.isArray(data.script?.panels) || data.script.panels.length !== 3
		|| !Array.isArray(data.backgrounds) || data.backgrounds.length !== 3
		|| data.backgrounds.some((url) => typeof url !== "string" || !url)) {
		throw new Error("Saved comic data is incomplete.");
	}

	const script = data.script;
	script.prompt = data.prompt;
	script.series = data.series;
	for (const [index, panel] of script.panels.entries()) {
		if (!Array.isArray(panel.dialog)) panel.dialog = [{ character: "alpha", text: panel.dialog }];
		panel.images = [
			{ url: data.backgrounds[index], type: "background", alt: `Background image: ${panel.background}` },
			{
				url: `${baseUrl}/assets/character_art/${encodeURIComponent(panel.action)}.png`,
				type: "character", character: "alpha", action: panel.action,
				alt: `Character image: alpha in a ${panel.action} pose`,
			},
		];
	}
	const container = document.querySelector(".strip-container");
	container.hidden = false;
	const renderer = new ComicRenderer({ el: container });
	renderer.LoadScript(script);
}
