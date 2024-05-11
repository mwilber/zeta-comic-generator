export class ScriptRenderer {
	constructor (params) {

		const {el, script, size} = params;

		this.el = el;
		if (script)
			this.script = this.LoadScript(params.script)

		console.log("GZ ScriptRenderer created");
	}

    async render () {
		if (!this.validate()) return;

		const {title, panels, credits} = this.script;

        this.el.innerHTML = `<li><h2>${title}</h2></li>`;

        if (credits && credits.script)	{
            // Add the credits
            this.el.innerHTML += `<li>
                <ul class="credits">
                    <li><span>Script: </span><span>${credits.script}</span></li>
                    <li><span>Images: </span><span>${credits.image}</span></li>
                    <li><span>Backgrounds: </span><span>${credits.background}</span></li>
                    <li><span>Actions: </span><span>${credits.action}</span></li>
                </ul>
            </li>`;
        }

        for (const [idx, panel] of panels.entries()) {
            let dialogHtml = "";
            for (const [idx, dialog] of panel.dialog.entries()) {
                dialogHtml += `<strong>${dialog.character}</strong>: ${dialog.text}`;
            }

            this.el.innerHTML += `
					<li>
						<h3>Panel ${idx + 1}</h3>
						<ul>
							<li>
								<table>
									<tr><td>Description</td> <td>${panel.scene}</td></tr>
									<tr><td>Action</td> <td>${panel.action}</td></tr>
									<tr><td>Dialog</td> <td>${dialogHtml}</td></tr>
									<tr><td>Background</td> <td>${panel.background}</td></tr>
								</table>
							</li>
						</ul>
					</li>
					`;
        }
    }

    validate (script) {
		script = script || this.script;

		if (!this.el) {
			console.error("ScriptRenderer: Container element not set.", this.container);
			return false;
		}

		if (!script) {
			console.error("ScriptRenderer: Valid script object not provided.");
			return false;
		}

		if (!script.panels || !script.panels.length) {
			console.error("ScriptRenderer: script.panels missing.", this.script);
			return false;
		}

		return true;
	}

    /**
	 * 
	 * @param {object} script 
	 */
	LoadScript (script) {
		if (!this.validate(script)) return;

		if (!script.title) {
			// Convenience warning. Title is not required.
			console.warn("ScriptRenderer: script.title missing.", this.script);
		}

		this.script = script;
		this.render();
	}
}