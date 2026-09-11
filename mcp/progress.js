import { GenerationProgressDialog } from "../scripts/modules/GenerationProgressDialog.js";

/** Adapts the shared dialog to workflow events and the embedded frame. */
export class McpGenerationProgress {
	constructor() {
		this.dialog = new GenerationProgressDialog(document.getElementById("statusdialog"));
		this.strip = document.getElementById("strip");
		this.summary = document.getElementById("app-status");
	}

	Start() {
		document.body.classList.add("is-generating");
		this.strip.inert = true;
		this.strip.setAttribute("aria-busy", "true");
		this.summary.classList.add("visually-hidden");
		this.dialog.Update(0);
		this.dialog.Show("Preparing comic generation…");
	}

	Stage(stage) {
		const messages = {
			concept: "Writing the concept…",
			script: "Writing the script…",
			backgrounds: "Planning backgrounds…",
			images: "Drawing backgrounds…",
			character: "Adding Alpha Zeta…",
			continuity: "Writing continuity…",
			complete: "Preparing your finished comic…",
		};
		if (messages[stage]) this.dialog.SetMessage(messages[stage]);
	}

	Update(amount) {
		this.dialog.Update(amount);
	}

	Finish(message) {
		const hadFocus = this.dialog.el.contains(document.activeElement);
		this.dialog.Hide();
		document.body.classList.remove("is-generating");
		this.strip.inert = false;
		this.strip.setAttribute("aria-busy", "false");
		this.strip.setAttribute("aria-label", "Comic strip");
		this.summary.textContent = message;
		this.summary.classList.remove("visually-hidden");
		if (hadFocus) this.strip.focus();
	}
}
