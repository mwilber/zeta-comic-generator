<script>
	const comicId = '<?php echo $path[2] ?>';
	const characterActions = <?php echo json_encode($GLOBALS['characterActions']); ?>;
	<?php if($isGemini): ?>
		if(localStorage) {
			localStorage.setItem('story-model-select', 'gemthink');
			localStorage.setItem('script-model-select', 'gem');
			localStorage.setItem('image-model-select', 'imagen');
		}
	<?php endif; ?>
</script>
<div id="sharedialog" class="dialog-wrapper" aria-modal="true" role="dialog" aria-hidden="true" aria-labelledby="sharedialog">
	<div class="dialog">
		<button id="closedialog" class="close" aria-label="Close"></button>
		<p>Share</p>
		<input id="shareurl" value="<?php echo $meta->url ?>"/>
			<button id="cpshare" title="Copy to Clipboard">
				<svg id="copy-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Pro 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M192 0c-41.8 0-77.4 26.7-90.5 64H64C28.7 64 0 92.7 0 128V448c0 35.3 28.7 64 64 64H320c35.3 0 64-28.7 64-64V128c0-35.3-28.7-64-64-64H282.5C269.4 26.7 233.8 0 192 0zm0 64a32 32 0 1 1 0 64 32 32 0 1 1 0-64zM112 192H272c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16s7.2-16 16-16z"/></svg>	
				<svg id="copied-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Pro 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M192 0c-41.8 0-77.4 26.7-90.5 64H64C28.7 64 0 92.7 0 128V448c0 35.3 28.7 64 64 64H320c35.3 0 64-28.7 64-64V128c0-35.3-28.7-64-64-64H282.5C269.4 26.7 233.8 0 192 0zm0 64a32 32 0 1 1 0 64 32 32 0 1 1 0-64zM305 273L177 401c-9.4 9.4-24.6 9.4-33.9 0L79 337c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L271 239c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
			</button>
		<div class="share-buttons">
			<button id="twshare" class="cartoon-button share-button" title="Share on Twitter">
				<img class="burst" src="/assets/images/speech_bubble.svg" alt="Cartoon speech bubble icon" />
				<span class="cartoon-font">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M459.37 151.716c.325 4.548.325 9.097.325 13.645 0 138.72-105.583 298.558-298.558 298.558-59.452 0-114.68-17.219-161.137-47.106 8.447.974 16.568 1.299 25.34 1.299 49.055 0 94.213-16.568 130.274-44.832-46.132-.975-84.792-31.188-98.112-72.772 6.498.974 12.995 1.624 19.818 1.624 9.421 0 18.843-1.3 27.614-3.573-48.081-9.747-84.143-51.98-84.143-102.985v-1.299c13.969 7.797 30.214 12.67 47.431 13.319-28.264-18.843-46.781-51.005-46.781-87.391 0-19.492 5.197-37.36 14.294-52.954 51.655 63.675 129.3 105.258 216.365 109.807-1.624-7.797-2.599-15.918-2.599-24.04 0-57.828 46.782-104.934 104.934-104.934 30.213 0 57.502 12.67 76.67 33.137 23.715-4.548 46.456-13.32 66.599-25.34-7.798 24.366-24.366 44.833-46.132 57.827 21.117-2.273 41.584-8.122 60.426-16.243-14.292 20.791-32.161 39.308-52.628 54.253z"/></svg>
				</span>
			</button>
			<button id="fbshare" class="cartoon-button share-button" title="Share on Facebook">
				<img class="burst" src="/assets/images/speech_bubble.svg" alt="Cartoon speech bubble icon" />
				<span class="cartoon-font">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><!--! Font Awesome Pro 6.4.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M279.14 288l14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S260.43 0 225.36 0c-73.22 0-121.08 44.38-121.08 124.72v70.62H22.89V288h81.39v224h100.17V288z"/></svg>
				</span>
			</button>
		</div>
	</div>
</div>
<div id="downloaddialog" class="dialog-wrapper" aria-modal="true" role="dialog" aria-hidden="true" aria-labelledby="downloaddialog">
	<div class="dialog">
		<button id="closedialog" class="close" aria-label="Close"></button>
		<p>Download</p>
		<div class="download-buttons">
			<div class="button-group">
				<button id="download-strip">
					<img class="burst" src="/assets/images/icon_full_strip.svg" style="background: rgba(255,255,255,0.5);" alt="Complete comic strip icon" />
				</button>
				<span>Complete Strip</span>
			</div>
			<div class="button-group">
				<button id="download-ig">
					<img class="burst" src="/assets/images/icon_indiv_panels.svg" alt="Individual panels icon" />
				</button>
				<span>Individual Panels</span>
			</div>
		</div>
	</div>
</div>
<h2>Premise</h2>
<div id="query" class="premise"></div>
<h2>Composite</h2>
<div class="story-controls">
	<div id="story-title"></div>
	<div id="story-nav"></div>
</div>
<div id="strip">
	<!-- <div id="panel1" class="panel"></div>
	<div id="panel2" class="panel"></div>
	<div id="panel3" class="panel"></div>
	<h3 id="strip-title"></h3> -->
	<div class="strip-container"></div>
	<div class="strip-controls">
		<button id="download" class="cartoon-button">
			<img class="burst" src="/assets/images/speech_bubble.svg" alt="Cartoon speech bubble icon" />
			<span class="cartoon-font">Download</span>
		</button>
		<button id="share" class="cartoon-button">
			<img class="burst" src="/assets/images/speech_bubble.svg" alt="Cartoon speech bubble icon" />
			<span class="cartoon-font">Share</span>
		</button>
		<?php if(defined("DEV_SITE") && DEV_SITE === true): ?>
		<button id="edit" class="cartoon-button">
			<img class="burst" src="/assets/images/speech_bubble.svg" alt="Cartoon speech bubble icon" />
			<span class="cartoon-font">Edit</span>
		</button>
		<?php endif; ?>
	</div>
</div>
<div id="output"></div>

<div id="continuity"></div>

<h2>Script</h2>
<ul id="script"></ul>

<script async type="text/javascript" src="/scripts/html2canvas.min.js"></script>
<script defer type="module" src="/scripts/detail.js?v=<?php echo $version ?>"></script>