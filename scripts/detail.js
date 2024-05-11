import { ComicRenderer } from "./modules/ComicRenderer.js";
import { CharacterAction } from "./modules/CharacterAction.js";
import { ComicExporter } from "./modules/ComicExporter.js";

document.addEventListener("DOMContentLoaded", () => {
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
	
				const {id, prompt, script, backgrounds} = data;
	
				script.prompt = prompt;
				document.getElementById("query").innerHTML = `${prompt}`;
	
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
							url: "https://zeta-comic-generator.s3.us-east-2.amazonaws.com/backgrounds/" + backgrounds[idx],
							className: "background"
						});
					panel.images.push(CharacterAction.GetActionImageData(panel.action, "alpha"));
				}
	
				comicRenderer.LoadScript(script);

				AttachUiEvents();
			});
	}

	// TODO: remove this
	window.comicRenderer = comicRenderer;
	window.comicExporter = ComicExporter;
});

function AttachUiEvents() {
	document.getElementById('download-ig').addEventListener('click', () => ComicExporter.DownloadComic(comicRenderer, "panel"));
	document.getElementById('download-strip').addEventListener('click', () => ComicExporter.DownloadComic(comicRenderer, "strip"));

	document.getElementById('share').addEventListener("click", () => {
		const dialog = document.getElementById('sharedialog');
		dialog.classList[dialog.classList.contains('active') ? 'remove' : 'add']('active');
	});

	document.getElementById('download').addEventListener("click", () => {
		// First, reload the background images via proxy so html2canvas can use them.
		const backgrounds = document.querySelectorAll('.background');
		backgrounds.forEach((background) => {
			background.src = '/api/imgproxy/?url=' + background.src;
		});
		// Open the dialog.
		const dialog = document.getElementById('downloaddialog');
		dialog.classList[dialog.classList.contains('active') ? 'remove' : 'add']('active');
	});

	document.querySelectorAll('.dialog-wrapper').forEach((wrapper) => {
		wrapper.querySelector('.dialog').addEventListener('click', (e) => e.stopPropagation());
		
		const closeDialog = (el) => el.classList.remove('active');
		wrapper.addEventListener('click', closeDialog.bind(null, wrapper));
		wrapper.querySelector('.close').addEventListener('click', closeDialog.bind(null, wrapper));
	});

	document.querySelector('.dialog-wrapper').addEventListener("click", () => {
		document.getElementById('sharedialog').classList.remove('active');
	});

	document.querySelector('.dialog').addEventListener("click", (e) => {
		e.stopPropagation();
	});

	document.getElementById('cpshare').addEventListener("click", () => {
		let btnEl = document.getElementById('cpshare');
		btnEl.setAttribute('disabled', '');
		navigator.clipboard.writeText(document.getElementById('shareurl').value);
		setTimeout(() => btnEl.removeAttribute('disabled'), 3000);
	});

	document.getElementById('twshare').addEventListener('click',function(event){
		event.preventDefault();
		window.open("https://twitter.com/share?text=" + GetShareMessage() + "&url="+encodeURIComponent(document.getElementById('shareurl').value) + "&hashtags=ai,AIart,generativeart,dalle2,openai");
	},false);

	document.getElementById('fbshare').addEventListener('click',function(event){
		event.preventDefault();
		window.open(
			"https://www.facebook.com/sharer/sharer.php?u="+encodeURIComponent(document.getElementById('shareurl').value),
			'Facebook',
			`scrollbars=no,resizable=no,status=no,location=no,toolbar=no,menubar=no,width=600,height=300,left=100,top=100`
		);
	},false);

	document.getElementById('shareurl').addEventListener('focus', function(event){
		event.target.select();
	});
}