/**
 * Fetches and displays a page of comic strip thumbnails in a gallery.
 *
 * @param {number} [pageNum=1] - The page number to fetch. Defaults to 1 if not provided.
 * @returns {void}
 */
function PopGallery(pageNum) {
	pageNum = pageNum || 1;
	fetch(
		"/api/gallery/?page=" +
			pageNum +
			"c=" +
			Math.floor(Math.random() * 1000000)
	)
		.then((response) => response.json())
		.then((listing) => {
			if (!listing.gallery) return;
			console.log(listing.gallery);
			const galleryEl = document.getElementById("gallery");
			const removeEl = document.querySelector(".action-buttons");
			if (removeEl) removeEl.remove();
			for (let strip of listing.gallery) {
				let frameEl = document.createElement("div");
				frameEl.className = "frame";
				frameEl.innerHTML += `
					<a class="strip" href="/detail/${strip.id}">
						<img src="${strip.thumbnail}"/>
						<h3>${strip.title}</h3>
					</a>
				`;
				galleryEl.appendChild(frameEl);
			}
			if (24 * pageNum < listing.count) {
				let actionRow = document.createElement("div");
				actionRow.className = "action-buttons";
				let moreBtn = document.createElement("a");
				moreBtn.id = "btn-more";
				moreBtn.href = "#";
				moreBtn.className = "cartoon-button";
				moreBtn.addEventListener("click", (e) => {
					e.preventDefault();
					PopGallery(pageNum + 1);
				});
				moreBtn.innerHTML = `
					<img class="burst" src="/assets/images/speech_bubble.svg">
					<span class="cartoon-font">More</span>
				`;
				actionRow.appendChild(moreBtn);
				galleryEl.appendChild(actionRow);
			}
		});
}

PopGallery();
