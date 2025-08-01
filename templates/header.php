<header>
	<a href="/">
		<img class="brand" src="/assets/images/brand.svg" alt="<?php echo $meta->siteTitle ?> logo. Alpha Zeta character in thumbs up pose." />
	</a>
	<nav class="collapsed">
		<button class="expand-button">
			<div>
			<span class="icon">
				<span class="bar1"></span>
				<span class="bar2"></span>
				<span class="bar3"></span>
			</span>
			MENU
			</div>
		</button>
		<a href="/generate" class="cartoon-button">
			<img class="burst" src="/assets/images/burst.svg" alt="Cartoon burst icon" />
			<span class="cartoon-font">Create</span>
		</a>
		<a href="/gallery" class="cartoon-button">
			<img class="burst" src="/assets/images/burst.svg" alt="Cartoon burst icon" />
			<span class="cartoon-font">Gallery</span>
		</a>
		<a href="/about" class="cartoon-button">
			<img class="burst" src="/assets/images/burst.svg" alt="Cartoon burst icon" />
			<span class="cartoon-font">About</span>
		</a>
		<a href="/about" class="cartoon-button">
			<img class="burst" src="/assets/images/burst.svg" alt="Cartoon burst icon" />
			<span class="cartoon-font">About</span>
		</a>
	</nav>
	<a href="/"><h1>Zeta Comic Generator</h1></a>
</header>
<script>
	const expandBtn = document.querySelector(".expand-button");
	expandBtn.addEventListener("click", (e) => {
		console.log(e.target);
		expandBtn.parentElement.classList.toggle("collapsed");
	});
</script>