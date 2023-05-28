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

ClearElements();
SetStatus('ready');
if(comicId) {
	fetch('/api/detail/'+comicId)
		.then((response) => response.json())
		.then((data) => {
			if(!data || !data.script){
				SetStatus('error');
				return;
			}
			window.stripData = data;
			const script = data.script;
			console.log("response", script);

			document.getElementById("query").innerHTML = `${data.prompt}`;
			document.getElementById("script").innerHTML = `<li><h2>${script.title}</h2></li>`;

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

document.getElementById('download').addEventListener("click", () => {
	const strip = document.getElementById('strip');
	const output = document.getElementById('output');
	if(!strip || !output) return;

	strip.style.boxShadow = 'none';
	html2canvas(strip).then(canvas => {
		//output.appendChild(canvas);
		let ctx = canvas.getContext("2d");
		window.ctxt = ctx;
		ctx.resetTransform();
		ctx.fillStyle = 'white';
		ctx.fillRect(10, 318, 940, 57);

		ctx.fillStyle = 'black';
		ctx.textAlign = 'right';
		ctx.font = 'bold 20px sans-serif';
		ctx.fillText(window.location.host, 945, 340);

		ctx.textAlign = 'left';
		ctx.fillText(window.stripData.script.title, 15, 340);

		ctx.font = 'normal 14px sans-serif';
		ctx.fillText('\u201C' + window.stripData.prompt + '\u201D', 15, 360);

		let uri = canvas.toDataURL();
		var link = document.createElement('a');
		link.download = window.stripData.script.title.replaceAll(' ', '_') + '.png';
		link.href = uri;
		link.click();
	});
});

document.getElementById('share').addEventListener("click", () => {
	const dialog = document.getElementById('sharedialog');
	dialog.classList[dialog.classList.contains('active') ? 'remove' : 'add']('active');
});

document.getElementById('closedialog').addEventListener("click", () => {
	document.getElementById('sharedialog').classList.remove('active');
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
    window.open("https://twitter.com/share?url="+encodeURIComponent(document.getElementById('shareurl').value));
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