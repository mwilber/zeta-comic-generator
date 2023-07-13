function ClearElements() {
	[
		'script',
		'panel1',
		'panel2',
		'panel3'
	].forEach((id) => document.getElementById(id).innerHTML = '');
}

function SetStatus(status) {
	document.body.dataset.status = status;

	['query'].forEach((id) => {
		document.getElementById(id)[status === 'generating' ? 'setAttribute' : 'removeAttribute']('disabled', '');
	});

	if(status === 'generating'){
		
	}
}

function GetShareMessage() {
	return `Check out my comic strip "${comicTitle}" from Zeta Comic Generator. Easily create unique comic strips with the help of OpenAI models and hand drawn character art.`;
}

async function RenderStripForDownload() {
	const strip = document.getElementById('strip');
	const output = document.getElementById('strip-output');
	if(!strip || !output) return;

	output.innerHTML = strip.outerHTML;
	output.querySelector('#strip-title').remove();
	output.querySelectorAll('.strip-controls button').forEach((button) => button.remove());

	let link = document.createElement('a');
	let canvas = await html2canvas(output, {scale: 1});

	// document.getElementById('output').appendChild(canvas);
	let ctx = canvas.getContext("2d");
	ctx.resetTransform();

	ctx.fillStyle = 'black';
	ctx.textAlign = 'right';
	ctx.font = 'bold 20px sans-serif';
	ctx.fillText(window.location.host, 1028, 370);
	// ctx.fillText("greenzeta.com/project/zetacomicgenerator", 905, 335);

	ctx.textAlign = 'left';
	ctx.fillText(window.stripData.script.title, 15, 370);

	ctx.font = 'normal 14px sans-serif';
	ctx.fillText('\u201C' + window.stripData.prompt + '\u201D', 15, 390);

	let uri = canvas.toDataURL();
	link.download = window.stripData.script.title.replaceAll(' ', '_') + '.png';
	link.href = uri;
	link.click();
	output.innerHTML = "";
}

async function RenderPanelsForDownload() {
	const output = document.getElementById('strip-output');
	if(!output) return;

	for(let idx = 1; idx <= 3; idx++){
		output.innerHTML = document.getElementById('panel'+idx).outerHTML;
		let link = document.createElement('a');
		let canvas = await html2canvas(output, {scale: 1});
		
		// document.getElementById('output').appendChild(canvas);
		let ctx = canvas.getContext("2d");
		ctx.resetTransform();
		ctx.strokeStyle = 'black';
		ctx.fillStyle = 'white';
		ctx.lineWidth = 4;
		ctx.font = 'bold 18px sans-serif';

		if(idx === 1){
			ctx.textAlign = 'left';
			ctx.strokeText(window.stripData.script.title, 10, 498);
			ctx.fillText(window.stripData.script.title, 10, 498);
		}else if(idx === 3){
			ctx.textAlign = 'right';
			ctx.strokeText(window.location.host, 498, 498);
			ctx.fillText(window.location.host, 498, 498);
		}

		let uri = canvas.toDataURL();
		link.download = window.stripData.script.title.replaceAll(' ', '_') + '_' + idx + '.png';
		link.href = uri;
		link.click();
		output.innerHTML = "";
	}
}

ClearElements();
SetStatus('ready');
if(comicId) {
	fetch('/api/detail/'+comicId+'/?c='+(Math.floor(Math.random()*1000000)))
		.then((response) => response.json())
		.then((data) => {
			if(!data || !data.script){
				SetStatus('error');
				return;
			}
			window.stripData = data;
			const script = data.script;
			console.log("response", script);

			window['comicTitle'] = script.title;

			document.getElementById("query").innerHTML = `${data.prompt}`;
			document.getElementById("script").innerHTML = `<li><h2>${script.title}</h2></li>`;
            document.getElementById("strip-title").innerText = script.title;

			if(script.panels && script.panels.length){
				script.panels.forEach((panel, idx) => {
					// Support older scripts that had character property
					if(!panel.action && panel.character) panel.action = panel.character;

					document.getElementById("script").innerHTML += `
					<li>
						<h3>Panel ${idx + 1}</h3>
						<ul>
							<li>
								<table>
									<tr><td>Description</td> <td>${panel.scene}</td></tr>
									<tr><td>Action</td> <td>${panel.action}</td></tr>
									<tr><td>Dialog</td> <td>${panel.dialog}</td></tr>
									<tr><td>Background</td> <td>${panel.background}</td></tr>
								</table>
							</li>
						</ul>
					</li>
					`;

					document.getElementById('panel' + (idx + 1)).innerHTML = `Rendering...`;

					document.getElementById('panel' + (idx + 1)).innerHTML = `
						<img class="background" src="/assets/backgrounds/${data.backgrounds[idx]}"/>
						<img class="character" src="/assets/character_art/${panel.action.toLowerCase()}.png"/>
						`;
					if(panel.dialog)
						document.getElementById('panel' + (idx + 1)).innerHTML += `
							<div class="bubble-container">
							<div class="bubble speech" title="Speech Balloon">${panel.dialog}</div>
							</div>
							`;

				});
				SetStatus('');
			}
		});
} else {
	SetStatus('error');
}

document.getElementById('download-ig').addEventListener('click', RenderPanelsForDownload);
document.getElementById('download-strip').addEventListener('click', RenderStripForDownload);

document.getElementById('share').addEventListener("click", () => {
	const dialog = document.getElementById('sharedialog');
	dialog.classList[dialog.classList.contains('active') ? 'remove' : 'add']('active');
});

document.getElementById('download').addEventListener("click", () => {
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

