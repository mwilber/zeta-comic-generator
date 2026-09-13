/** Shared progress controls for the website and the embedded MCP app. */
export class GenerationProgressDialog {
	constructor(el) {
		this.el = el;
		this.status = el.querySelector("#status");
		this.progress = el.querySelector("progress");
	}

	Show(message = "Preparing comic generation…") {
		this.SetMessage(message);
		this.el.classList.add("active");
		this.el.setAttribute("aria-hidden", "false");
		this.el.focus();
	}

	Hide() {
		this.el.classList.remove("active");
		this.el.setAttribute("aria-hidden", "true");
	}

	SetMessage(message) {
		this.status.textContent = message;
	}

	/** Displays a workflow stage; unknown stages leave the current message intact. */
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
		if (Object.hasOwn(messages, stage)) this.SetMessage(messages[stage]);
	}

	Update(amount) {
		amount = Number.isFinite(amount) ? Math.max(0, Math.min(100, amount)) : 0;
		this.progress.setAttribute("value", amount);
		this.progress.textContent = amount + "%";
		this.progress.setAttribute("aria-valuetext", `${amount}% complete.`);
	}
}
