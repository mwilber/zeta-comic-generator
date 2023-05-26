<div id="statusdialog">
	<div id="status"></div>
	<progress id="progress" value="50" max="100"></progress>
</div>
<div id="interface">
	<div class="query-wrapper">
		<h2>Premise</h2>
		<div id="input">
			<input id="query" type="text" placeholder="ex. An explanation of the distance between the earth and the sun."/>
			<button id="generate">Generate</button>
		</div>
	</div>
	<div class="strip-wrapper">
		<h2>Composite</h2>
		<button id="save">Save</button>
		<div id="permalink"></div>
		<div id="strip">
			<div id="panel1" class="panel"></div>
			<div id="panel2" class="panel"></div>
			<div id="panel3" class="panel"></div>
		</div>
	</div>
	<div class="script-wrapper">
		<h2 class="script">Script</h2>
		<ul id="script"></ul>
	</div>
</div>

<script type="text/javascript" src="/scripts/generate.js"></script>