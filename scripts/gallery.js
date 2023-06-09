fetch('/api/gallery/?c='+(Math.floor(Math.random()*10000000000000000)))
	.then((response) => response.json())
	.then((listing)=>{
		if(!listing.gallery) return;
		console.log(listing.gallery);
		for(let strip of listing.gallery) {
			document.getElementById('gallery').innerHTML += `
			<div class="frame">
				<a class="strip" href="/detail/${strip.id}">
					<img src="/assets/thumbnails/thumb_${strip.id}.png"/>
					<h3>${strip.title}</h3>
				</a>
			</div>
			`;
		}
	});