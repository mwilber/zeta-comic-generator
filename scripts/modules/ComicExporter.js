export class ComicExporter {

	static async DownloadComic(renderer, format) {
		if (!renderer.el) return;
		format = format === "panel" ? "panel" : "strip";

		const url = "comicgenerator.greenzeta.com";
		const {script} = renderer;
		let downloads = [];

		switch (format) {
			case "panel":
				for (const [idx, panel] of script.panels.entries()) {
					let params = {
						title: idx === 0 ? script.title : "",
						url: idx === 2 ? url : "",
					};
					downloads.push(await this.RenderPanelAsDataUrl(panel.panelEl, params));
				}
				break;
			case "strip":
				downloads.push(await this.RenderStripAsDataUrl(renderer.el, {
					prompt: script.prompt,
					title: script.title,
					url
				}));
				break;
			default:
				console.error("ComicExporter: Invalid format", format);
				return;
		}

		for (const [idx, download] of downloads.entries()) {
			let link = document.createElement("a");
			let suffix = downloads.length ? ("_" + idx) : "";
			link.download = script.title.replaceAll(" ", "_") + suffix + ".png";
			link.href = download;
			link.click();
		}
	}


	static async RenderPanelAsDataUrl(panel, params) {
		if(!panel || !html2canvas) return;
			
		const {title, url} = params;
		// Copy the comic into a fixed width element for consistent sizing
		const output = document.createElement("div");
		output.className = "strip-output";
		document.body.appendChild(output);
		
		output.innerHTML = panel.outerHTML;
		
		// TODO: Need to push this part of the function off to the event queue to give the dom time to render the output element.
		let canvas = await html2canvas(
			output,
			{scale: 1}
		);

		// Cleanup the output copy
		setTimeout(() => output.remove(), 1000);
		
		let ctx = canvas.getContext("2d");
		ctx.resetTransform();
		ctx.strokeStyle = "black";
		ctx.fillStyle = "white";
		ctx.lineWidth = 4;
		ctx.font = "bold 18px sans-serif";

		if(title){
			ctx.textAlign = "left";
			ctx.strokeText(title, 10, 498);
			ctx.fillText(title, 10, 498);
		}else if(url){
			ctx.textAlign = "right";
			ctx.strokeText(url, 498, 498);
			ctx.fillText(url, 498, 498);
		}

		return canvas.toDataURL();
	}

	static async RenderStripAsDataUrl(strip, params) {
		if(!strip || !html2canvas) return;

		const {title, prompt, url} = params;

		// Copy the comic into a fixed width element for consistent sizing
		const output = document.createElement("div");
		output.className = "strip-output";
		document.body.appendChild(output);
	
		output.innerHTML = strip.outerHTML;
		output.querySelector("#strip-title").remove();
		output.querySelectorAll(".strip-controls button").forEach((button) => button.remove());

		let canvas = await html2canvas(
			output,
			{scale: 1}
		);

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
		ctx.fillText("\u201C" + prompt + "\u201D", 15, 390);
	
		return canvas.toDataURL();
	}
}