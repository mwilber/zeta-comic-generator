/**
 * @file ComicExporter.js
 * @author Matthew Wilber
 * @license GPL-3.0
 *
 * A static class with functions to export a rendered Zeta Comic Generator comic as images.
 * This class requires html2canvas (html2canvas.hertzen.com) available in global scope.
 */
export class ComicExporter {
	/**
	 * Renders a comic panel or strip as a data URL for download.
	 *
	 * @param {ComicRenderer} renderer - The comic renderer instance to render.
	 * @param {string} format - The format to render, either "panel" or "strip".
	 * @returns {Promise<void>} - A promise that resolves when the comic has been downloaded.
	 */
	static async DownloadComic(renderer, format) {
		if (!renderer.el) return;
		format = format === "panel" ? "panel" : "strip";

		const url = "comicgenerator.greenzeta.com";
		const { script } = renderer;
		const { title, panels, prompt } = script;
		const fileName = title.replaceAll(" ", "_");

		switch (format) {
			case "panel":
				for (const [idx, panel] of panels.entries()) {
					let download = await this.RenderPanelAsDataUrl(
						panel.panelEl,
						{
							title: idx === 0 ? title : "",
							url: idx === 2 ? url : "",
						}
					);
					this.DownloadImageData(download, fileName, "_" + idx);
				}
				break;
			case "strip":
				let download = await this.RenderStripAsDataUrl(renderer.el, {
					prompt,
					title,
					url,
				});
				this.DownloadImageData(download, fileName, "");
				break;
			default:
				console.error("ComicExporter: Invalid format", format);
				return;
		}
	}

	/**
	 * Downloads an image using the provided data URL.
	 *
	 * @param {string} dataUrl - The data URL of the image to download.
	 * @param {string} title - The title of the image, used as the file name.
	 * @param {string} suffix - The file name suffix to append to the title. This is not the file dot suffix, this will go before `.png`.
	 */
	static DownloadImageData(dataUrl, title, suffix) {
		let link = document.createElement("a");
		link.download = title + suffix + ".png";
		link.href = dataUrl;
		link.click();
	}

	/**
	 * Renders a comic panel as a data URL.
	 *
	 * @param {HTMLElement} panel - The comic panel element to render.
	 * @param {Object} params - Parameters for rendering the panel.
	 * @param {string} params.title - The title to display on the rendered panel.
	 * @param {string} params.url - The URL to display on the rendered panel.
	 * @returns {Promise<string>} - A data URL representing the rendered panel.
	 */
	static async RenderPanelAsDataUrl(panel, params) {
		if (!panel || !html2canvas) return;

		const { title, url } = params;
		// Copy the comic into a fixed width element for consistent sizing
		const output = document.createElement("div");
		output.className = "strip-output";
		document.body.appendChild(output);

		output.innerHTML = panel.outerHTML;

		let canvas = await html2canvas(output, { scale: 1 });

		// Cleanup the output copy
		setTimeout(() => output.remove(), 1000);

		let ctx = canvas.getContext("2d");
		ctx.resetTransform();
		ctx.strokeStyle = "black";
		ctx.fillStyle = "white";
		ctx.lineWidth = 4;
		ctx.font = "bold 18px sans-serif";

		if (title) {
			ctx.textAlign = "left";
			ctx.strokeText(title, 10, 498);
			ctx.fillText(title, 10, 498);
		} else if (url) {
			ctx.textAlign = "right";
			ctx.strokeText(url, 498, 498);
			ctx.fillText(url, 498, 498);
		}

		return canvas.toDataURL();
	}

	/**
	 * Renders a comic strip as a data URL.
	 *
	 * @param {HTMLElement} strip - The comic strip element to render.
	 * @param {Object} params - Parameters for rendering the strip.
	 * @param {string} params.title - The title of the comic strip.
	 * @param {string} params.prompt - The prompt or caption for the comic strip.
	 * @param {string} params.url - The URL of the comic strip.
	 * @returns {Promise<string>} - A data URL representing the rendered comic strip.
	 */
	static async RenderStripAsDataUrl(strip, params) {
		if (!strip || !html2canvas) return;

		const { title, prompt, url } = params;

		// Replace any double quotes in prompt with single quotes. Because propmt will be rendered with double quotes around it.
		let displayPrompt = prompt.replaceAll('"', "'");

		// Copy the comic into a fixed width element for consistent sizing
		const output = document.createElement("div");
		output.className = "strip-output";
		document.body.appendChild(output);

		output.innerHTML = strip.outerHTML;
		output.querySelector("#strip-title").remove();
		output
			.querySelectorAll(".strip-controls button")
			.forEach((button) => button.remove());

		let canvas = await html2canvas(output, { scale: 1 });

		// Cleanup the output copy
		setTimeout(() => output.remove(), 1000);

		let ctx = canvas.getContext("2d");
		ctx.resetTransform();

		ctx.fillStyle = "black";
		ctx.textAlign = "right";
		ctx.font = "bold 20px sans-serif";
		ctx.fillText(url, 1028, 370);

		ctx.textAlign = "left";
		ctx.fillText(title, 15, 370);

		ctx.font = "normal 14px sans-serif";
		ctx.fillText("\u201C" + displayPrompt + "\u201D", 15, 390);

		return canvas.toDataURL();
	}
}
