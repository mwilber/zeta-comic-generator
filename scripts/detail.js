import { ComicRenderer } from "./modules/ComicRenderer.js";
import { CharacterAction } from "./modules/CharacterAction.js";

const comicRenderer = new ComicRenderer({el: document.querySelector(".strip-container")});

if(comicId) {
	fetch('/api/detail/'+comicId+'/?c='+(Math.floor(Math.random()*100)))
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

			const {script, backgrounds} = data;

			for (const [idx, panel] of script.panels.entries()) {
				let dialog = panel.dialog;
				panel.dialog = [];
				panel.dialog.push({
					character: "alpha",
					text: dialog,
				});
				panel.images = [];
				if (backgrounds[idx])
					panel.images.push({
						url: "https://zeta-comic-generator.s3.us-east-2.amazonaws.com/backgrounds/" + backgrounds[idx]
					});
				panel.images.push(CharacterAction.GetActionImageData(panel.action, "alpha"));
			}

			if(data && data.script)
				comicRenderer.LoadScript(data.script);
		});
}