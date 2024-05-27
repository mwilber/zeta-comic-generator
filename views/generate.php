<div id="statusdialog" class="dialog-wrapper">
	<div class="dialog">
		<div id="status"></div>
		<div class="lds-grid"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
		<progress id="progress" value="0" max="100"></progress>
	</div>
</div>
<div id="interface">
	<div class="query-wrapper">
		<h2>Setup</h2>
		<div id="input">
			<div class="row selections">
				<label> 
					Script Model
					<div class="select">
						<select name="script-model" id="script-model">
							<option value="gem">Gemini 1.0</option>
							<option value="oai" selected>GPT 4o</option>
							<!-- Titan Text Express v1 disabled because it can't handle the new prompt format -->
							<!-- <option value="ttn">Titan Text Express v1</option> -->
						</select>
					</div>
				</label>
				<label>
					Image Model
					<div class="select">
						<select name="image-model" id="image-model">
							<option value="oai" selected>Dall-E 3</option>
							<option value="sdf">Stable Diffusion XL</option>
							<option value="ttn">Titan Image (preview)</option>
						</select>
					</div>
				</label>
				<label id="image-style-label" style="display:none;">
					Image Style
					<div class="select">
						<select name="image-style" id="image-style">
							<option value="" selected>(default)</option>
							<option value="anime">Anime</option>
							<option value="cinematic">Cinematic</option>
							<option value="comic-book">Comic Book</option>
							<option value="fantasy-art">Fantasy</option>
							<option value="low-poly">Low Poly</option>
							<option value="neon-punk">Neon Punk</option>
							<option value="origami">Origami</option>
							<option value="photographic">Photographic</option>
						</select>
					</div>
				</label>
			</div>
			<div class="row">
				<label for="query">
					Enter a story premise. Keep it short and simple. Then press the "Start" button.
				</label>
			</div>
			<div class="row">
				<input id="query" type="text" placeholder="ex. An explanation of the distance between the earth and the sun."/>
				<button id="generate" class="cartoon-button" disabled>
					<img class="burst" src="/assets/images/speech_bubble.svg" />
					<span class="cartoon-font">Start</span>
				</button>
			</div>
			<div class="row">
				<span id="character-count">140 characters left</span>
			</div>
		</div>
	</div>
	<div class="strip-wrapper">
		<h2>Composite</h2>
		<div id="strip">
			<div class="strip-container">
				<div id="panel1" class="panel"></div>
				<div id="panel2" class="panel"></div>
				<div id="panel3" class="panel"></div>
			</div>
			<div class="strip-controls">
				<button id="save" class="cartoon-button">
					<img class="burst" src="/assets/images/speech_bubble.svg" />
					<span class="cartoon-font">Save</span>
				</button>
				<div id="permalink" class="cartoon-button"></div>
			</div>	
		</div>
	</div>
	<div class="script-wrapper">
		<h2 class="script">Script</h2>
		<ul id="script"></ul>
	</div>
</div>

<script defer type="module" src="/scripts/generate.js?v=<?php echo $version ?>"></script>