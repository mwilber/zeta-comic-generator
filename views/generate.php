<div id="statusdialog" class="dialog-wrapper">
	<div class="dialog">
		<div id="status"></div>
		<div class="lds-grid"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
		<progress id="progress" value="0" max="100"></progress>
	</div>
</div>
<div id="interface">
	<div class="query-wrapper">
		<h2>Premise</h2>
		<div id="input">
			<input id="query" type="text" placeholder="ex. An explanation of the distance between the earth and the sun."/>
			<button id="generate" class="cartoon-button">
				<img class="burst" src="/assets/images/speech_bubble.svg" />
				<span class="cartoon-font">Start</span>
            </button>
			<span id="character-count">140 characters left</span>
		</div>
	</div>
	<div class="strip-wrapper">
		<h2>Composite</h2>
		<div id="strip">
			<div id="panel1" class="panel"></div>
			<div id="panel2" class="panel"></div>
			<div id="panel3" class="panel"></div>
            <h3 id="strip-title"></h3>
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

<script type="text/javascript" src="/scripts/generate.js"></script>