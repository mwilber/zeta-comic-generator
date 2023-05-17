fetch('/api/gallery')
	.then((response) => response.json())
	.then((listing)=>{
		if(!listing.gallery) return;
		console.log(listing.gallery);
		for(let strip of listing.gallery) {
			document.getElementById('gallery').innerHTML += `
			<div class="frame">
				<a class="strip" href="/detail/${strip.id}">
					<img src="/assets/thumbnails/thumb_${strip.id}.png"/>
					<h2>${strip.title}</h2>
				</a>
			</div>
			`;
		}
	});